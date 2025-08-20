import os
import time
import mysql.connector
from dotenv import load_dotenv
from flask import Flask, request, jsonify

# LangChain imports
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.runnables import RunnablePassthrough, RunnableParallel
from langchain_core.output_parsers import StrOutputParser
from langchain_community.vectorstores import FAISS
from langchain_openai import ChatOpenAI, OpenAIEmbeddings

# Load environment variables
load_dotenv()

# Initialize Flask app
app = Flask(__name__)

# Database configuration
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_NAME', 'ecommerce_sl')
}

# Global LangChain components (initialized on startup)
vector_store = None
retriever = None
rag_chain = None

def initialize_rag_system():
    """Initialize the LangChain RAG system components"""
    global vector_store, retriever, rag_chain
    
    try:
        print("🚀 Initializing RAG system...")
        
        # Load embeddings model
        embeddings = OpenAIEmbeddings(
            model=os.getenv('EMBEDDING_MODEL', 'text-embedding-ada-002')
        )
        
        # Load the local FAISS vector store
        vector_store_path = os.getenv('VECTOR_STORE_PATH', 'faiss_index')
        if not os.path.exists(vector_store_path):
            raise FileNotFoundError(f"Vector store not found at {vector_store_path}. Please run create_vector_store.py first.")
        
        vector_store = FAISS.load_local(
            vector_store_path, 
            embeddings, 
            allow_dangerous_deserialization=True
        )
        
        # Create retriever with configurable parameters
        max_docs = int(os.getenv('MAX_RETRIEVED_DOCS', 20))
        retriever = vector_store.as_retriever(
            search_type="similarity",
            search_kwargs={'k': max_docs}
        )
        
        # Initialize the LLM
        model = ChatOpenAI(
            model=os.getenv('LLM_MODEL', 'gpt-3.5-turbo'),
            temperature=float(os.getenv('TEMPERATURE', 0)),
            max_tokens=int(os.getenv('MAX_TOKENS', 150))
        )
        
        # Define the RAG prompt template
        template = """You are a helpful fashion assistant for StyleMe e-commerce store. Based on the retrieved product information and the user's search history, provide relevant product recommendations.

CONTEXT (Product Information):
{context}

USER SEARCH HISTORY:
{history}

CURRENT SEARCH QUERY: {question}

INSTRUCTIONS:
1. Analyze the user's current query and search history to understand their preferences
2. From the retrieved products, select the most relevant ones that match the user's intent
3. Consider factors like: category match, price range, brand preference, color, size, occasion, and gender
4. Prioritize products that align with the user's search patterns
5. Return ONLY a comma-separated list of product IDs (numbers only)
6. Limit to maximum 10 most relevant product IDs
7. Order by relevance (most relevant first)

RESPONSE FORMAT: Only return product IDs separated by commas (e.g., 12, 45, 8, 92)

Product IDs:"""

        prompt = ChatPromptTemplate.from_template(template)
        
        # Create the LangChain Expression Language (LCEL) chain
        rag_chain = (
            RunnableParallel({
                "context": lambda x: format_context(retriever.invoke(x["question"])),
                "history": lambda x: x["history"],
                "question": lambda x: x["question"]
            })
            | prompt
            | model
            | StrOutputParser()
        )
        
        print("✅ RAG system initialized successfully!")
        return True
        
    except Exception as e:
        print(f"❌ Failed to initialize RAG system: {e}")
        return False

def format_context(retrieved_docs):
    """Format retrieved documents for the prompt context"""
    if not retrieved_docs:
        return "No relevant products found."
    
    context_parts = []
    for i, doc in enumerate(retrieved_docs, 1):
        metadata = doc.metadata
        context_part = f"{i}. Product ID: {metadata.get('product_id', 'Unknown')}"
        context_part += f" | Content: {doc.page_content[:200]}..."
        if metadata.get('price'):
            context_part += f" | Price: Rs. {metadata['price']}"
        context_parts.append(context_part)
    
    return "\n".join(context_parts)

def get_user_search_history(user_id, limit=5):
    """Retrieve recent search history for a user"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        cursor.execute('''
            SELECT search_query 
            FROM user_search_history 
            WHERE user_id = %s 
            ORDER BY created_at DESC 
            LIMIT %s
        ''', (user_id, limit))
        
        history = [row[0] for row in cursor.fetchall()]
        cursor.close()
        conn.close()
        
        return ', '.join(history) if history else "No previous searches"
        
    except Exception as e:
        print(f"Error fetching search history: {e}")
        return "No previous searches"

def store_search_history(user_id, query):
    """Store user search query in history"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT INTO user_search_history (user_id, search_query) 
            VALUES (%s, %s)
        ''', (user_id, query))
        
        conn.commit()
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Error storing search history: {e}")

def log_search(user_id, query, results_count, processing_time, enhanced_query=None):
    """Log search details for analytics"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT INTO search_logs (user_id, query, results_count, enhanced_query, processing_time) 
            VALUES (%s, %s, %s, %s, %s)
        ''', (user_id, query, results_count, enhanced_query, processing_time))
        
        conn.commit()
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Error logging search: {e}")

@app.route('/')
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'StyleMe RAG Service',
        'version': '2.0.0-langchain',
        'rag_system': 'initialized' if rag_chain else 'not_initialized'
    })

@app.route('/search', methods=['POST'])
def handle_search():
    """Handle search requests using LangChain RAG pipeline"""
    if not rag_chain:
        return jsonify({
            'error': 'RAG system not initialized. Please check server logs.'
        }), 500
    
    start_time = time.time()
    
    try:
        # Parse request data
        data = request.json
        if not data:
            return jsonify({'error': 'No JSON data provided'}), 400
            
        user_id = data.get('user_id')
        query = data.get('query')
        
        # Validate input
        if not all([user_id, query]):
            return jsonify({'error': 'Missing user_id or query'}), 400
        
        if not query.strip():
            return jsonify({'error': 'Query cannot be empty'}), 400
        
        # Store search history
        store_search_history(user_id, query)
        
        # Get user's search history for context
        history = get_user_search_history(user_id, int(os.getenv('HISTORY_LIMIT', 5)))
        
        # Execute RAG chain
        result_str = rag_chain.invoke({
            'question': query.strip(),
            'history': history
        })
        
        # Parse the comma-separated string of IDs into a list of integers
        product_ids = []
        try:
            if result_str and result_str.strip():
                # Clean and parse the result
                cleaned_result = result_str.strip()
                # Remove any non-numeric characters except commas and spaces
                import re
                cleaned_result = re.sub(r'[^\d,\s]', '', cleaned_result)
                
                # Split by comma and convert to integers
                id_parts = [part.strip() for part in cleaned_result.split(',') if part.strip()]
                product_ids = []
                
                for part in id_parts:
                    if part.isdigit():
                        product_ids.append(int(part))
                
                # Remove duplicates while preserving order
                seen = set()
                product_ids = [x for x in product_ids if not (x in seen or seen.add(x))]
                
        except (ValueError, AttributeError) as e:
            print(f"Error parsing product IDs from result: {result_str}, Error: {e}")
            product_ids = []
        
        # Calculate processing time
        processing_time = round(time.time() - start_time, 3)
        
        # Log the search for analytics
        log_search(user_id, query, len(product_ids), processing_time, result_str)
        
        # Return results
        return jsonify({
            'success': True,
            'product_ids': product_ids,
            'query': query,
            'results_count': len(product_ids),
            'processing_time': processing_time,
            'history_considered': history != "No previous searches"
        })
        
    except Exception as e:
        processing_time = round(time.time() - start_time, 3)
        error_message = f"Error processing search: {str(e)}"
        print(error_message)
        
        return jsonify({
            'error': 'Internal server error during search processing',
            'processing_time': processing_time
        }), 500

@app.route('/vector-store/stats', methods=['GET'])
def vector_store_stats():
    """Get statistics about the vector store"""
    if not vector_store:
        return jsonify({'error': 'Vector store not initialized'}), 500
    
    try:
        # Get basic statistics
        index = vector_store.index
        total_vectors = index.ntotal
        
        return jsonify({
            'total_vectors': total_vectors,
            'vector_store_type': 'FAISS',
            'embedding_model': os.getenv('EMBEDDING_MODEL', 'text-embedding-ada-002'),
            'status': 'ready'
        })
        
    except Exception as e:
        return jsonify({'error': f'Error getting vector store stats: {str(e)}'}), 500

@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    print("🔧 Starting StyleMe RAG Service...")
    
    # Initialize RAG system
    if not initialize_rag_system():
        print("❌ Failed to initialize RAG system. Exiting.")
        exit(1)
    
    print("🌟 RAG Service is ready to serve requests!")
    print(f"🔗 Health check: http://localhost:{os.getenv('FLASK_PORT', 5000)}/")
    print(f"🔍 Search endpoint: http://localhost:{os.getenv('FLASK_PORT', 5000)}/search")
    
    # Start Flask application
    app.run(
        host=os.getenv('FLASK_HOST', '0.0.0.0'),
        port=int(os.getenv('FLASK_PORT', 5000)),
        debug=os.getenv('FLASK_DEBUG', 'True').lower() == 'true'
    )
