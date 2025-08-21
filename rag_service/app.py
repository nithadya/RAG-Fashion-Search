import os
import time
import json
import mysql.connector
from dotenv import load_dotenv
from flask import Flask, request, jsonify
from flask_cors import CORS

# LangChain imports
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.runnables import RunnablePassthrough, RunnableParallel
from langchain_core.output_parsers import StrOutputParser
from langchain_community.vectorstores import FAISS
from langchain_huggingface import HuggingFaceEmbeddings

# Groq imports
try:
    from langchain_groq import ChatGroq
    GROQ_AVAILABLE = True
except ImportError:
    GROQ_AVAILABLE = False
    print("⚠️ Groq not available. Install with: pip install langchain-groq groq")

# Load environment variables
load_dotenv()

# Initialize Flask app
app = Flask(__name__)

# Enable CORS for all routes
CORS(app, origins=['http://localhost:8080', 'http://localhost:3000', 'http://127.0.0.1:8080'])

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
current_provider = None

def get_llm_provider():
    """Determine which LLM provider to use - Groq only"""
    if not GROQ_AVAILABLE:
        raise ValueError("Groq is not available. Install with: pip install langchain-groq groq")
    
    if not os.getenv('GROQ_API_KEY'):
        raise ValueError("GROQ_API_KEY is required but not found in environment")
    
    return 'groq'

def create_llm():
    """Create Groq LLM instance"""
    return ChatGroq(
        model=os.getenv('GROQ_LLM_MODEL', 'llama-3.1-8b-instant'),
        temperature=float(os.getenv('TEMPERATURE', 0)),
        max_tokens=int(os.getenv('MAX_TOKENS', 150)),
        groq_api_key=os.getenv('GROQ_API_KEY')
    )

def initialize_rag_system():
    """Initialize the LangChain RAG system components"""
    global vector_store, retriever, rag_chain, current_provider
    
    try:
        print("🚀 Initializing RAG system...")
        
        # Determine provider (Groq only)
        current_provider = get_llm_provider()
        print(f"🤖 Using LLM provider: {current_provider.upper()}")
        
        # Load HuggingFace embeddings (local, free)
        embedding_model = os.getenv('EMBEDDING_MODEL', 'all-MiniLM-L6-v2')
        print(f"🤗 Using HuggingFace model: {embedding_model}")
        print("⏳ Loading HuggingFace embeddings model (this may take a moment)...")
        
        embeddings = HuggingFaceEmbeddings(
            model_name=embedding_model,
            model_kwargs={'device': 'cpu'},  # Use 'cuda' if you have GPU
            encode_kwargs={'normalize_embeddings': True}
        )
        print("✅ HuggingFace embeddings loaded successfully")
        
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
        
        # Initialize the Groq LLM
        model = create_llm()
        
        # Define the RAG prompt template (optimized for Groq)
        template = """You are a helpful fashion assistant for StyleMe e-commerce store. Analyze the context and provide relevant product recommendations.

PRODUCT CONTEXT:
{context}

USER SEARCH HISTORY:
{history}

CURRENT QUERY: {question}

TASK: Return only a comma-separated list of the most relevant product IDs (numbers only) from the context above. Consider the user's query and search history to provide personalized recommendations. Maximum 10 product IDs, ordered by relevance.

RESPONSE FORMAT: Only product IDs separated by commas (example: 12, 45, 8)

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
        
        print(f"✅ RAG system initialized successfully with {current_provider.upper()}!")
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

def format_search_history(history):
    """Format search history for the prompt"""
    if not history or history == "No previous searches":
        return "No previous searches"
    return history

def parse_product_ids(result_str):
    """Parse product IDs from LLM result string"""
    try:
        if isinstance(result_str, str):
            # Clean the result string
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
            
            return product_ids
            
    except (ValueError, AttributeError) as e:
        print(f"Error parsing product IDs from result: {result_str}, Error: {e}")
        
    return []

def log_user_search(user_id, query, product_ids, preferences):
    """Log enhanced search with preferences for learning"""
    try:
        # Skip logging for guest users (None or invalid user_id)
        if not user_id or user_id <= 0:
            return
            
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT INTO search_preferences_log (user_id, query, preferences, recommended_products) 
            VALUES (%s, %s, %s, %s)
        ''', (user_id, query, json.dumps(preferences), json.dumps(product_ids)))
        
        conn.commit()
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Error logging user search: {e}")

def log_search(user_id, query, results_count, processing_time, enhanced_query=None):
    """Log search details for analytics"""
    try:
        # Allow logging for guest users, but convert None to 0 for database
        user_id_for_db = user_id if user_id and user_id > 0 else 0
        
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT INTO search_logs (user_id, query, results_count, enhanced_query, processing_time) 
            VALUES (%s, %s, %s, %s, %s)
        ''', (user_id_for_db, query, results_count, enhanced_query, processing_time))
        
        conn.commit()
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"Error logging search: {e}")

@app.route('/')
def health_check():
    """Health check endpoint"""
    provider_info = {
        'current_provider': current_provider or 'not_initialized',
        'openai_available': bool(os.getenv('OPENAI_API_KEY')),
        'groq_available': bool(os.getenv('GROQ_API_KEY')) and GROQ_AVAILABLE,
        'groq_sdk_installed': GROQ_AVAILABLE
    }
    
    return jsonify({
        'status': 'healthy',
        'service': 'StyleMe RAG Service',
        'version': '2.1.0-multi-provider',
        'rag_system': 'initialized' if rag_chain else 'not_initialized',
        'provider_info': provider_info
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
        
        # Return results with provider information
        return jsonify({
            'success': True,
            'product_ids': product_ids,
            'query': query,
            'results_count': len(product_ids),
            'processing_time': processing_time,
            'history_considered': history != "No previous searches",
            'provider_used': current_provider,
            'service_version': '2.1.0-multi-provider'
        })
        
    except Exception as e:
        processing_time = round(time.time() - start_time, 3)
        error_message = f"Error processing search: {str(e)}"
        print(error_message)
        
        return jsonify({
            'error': 'Internal server error during search processing',
            'processing_time': processing_time,
            'provider_used': current_provider
        }), 500

@app.route('/search_with_preferences', methods=['POST'])
def search_with_preferences():
    """Enhanced search endpoint with user preferences and matching scores"""
    try:
        data = request.get_json()
        query = data.get('query', '').strip()
        user_id = data.get('user_id', 0)
        preferences = data.get('preferences', {})
        context = data.get('context', {})
        
        if not query:
            return jsonify({'success': False, 'message': 'Query is required'}), 400
            
        print(f"🔍 Enhanced search: '{query}' for user {user_id}")
        print(f"📋 Preferences: {preferences}")
        
        # Get user search history
        history = get_user_search_history(user_id)
        
        # Create enhanced query incorporating preferences
        enhanced_query = create_enhanced_query(query, preferences, context)
        
        start_time = time.time()
        
        # Get search results
        print(f"🤖 Invoking RAG chain with enhanced query: {enhanced_query}")
        results = rag_chain.invoke({
            "question": enhanced_query,
            "history": format_search_history(history)
        })
        
        print(f"🔍 RAG chain raw results: {results}")
        
        processing_time = time.time() - start_time
        
        # Parse and validate product IDs
        product_ids = parse_product_ids(results)
        print(f"📦 Parsed product IDs: {product_ids}")
        
        # Calculate preference-based matching scores
        matching_scores = calculate_preference_scores(product_ids, preferences, query)
        print(f"📊 Matching scores: {matching_scores}")
        
        # Log search for learning
        log_user_search(user_id, query, product_ids, preferences)
        
        response_data = {
            'success': True,
            'product_ids': product_ids,
            'matching_scores': matching_scores,
            'query': query,
            'enhanced_query': enhanced_query,
            'preferences_applied': preferences,
            'results_count': len(product_ids),
            'processing_time': round(processing_time, 3),
            'provider_used': current_provider.upper(),
            'service_version': '2.1.0-enhanced',
            'history_considered': len(history) > 0
        }
        
        print(f"✅ Returning response: {response_data}")
        
        return jsonify(response_data)
        
    except Exception as e:
        print(f"❌ Enhanced search error: {e}")
        return jsonify({
            'success': False,
            'message': f'Enhanced search failed: {str(e)}',
            'error_type': type(e).__name__
        }), 500

def create_enhanced_query(query, preferences, context):
    """Create enhanced query incorporating user preferences"""
    enhanced_parts = [query]
    
    # Add style preferences
    style_prefs = preferences.get('style_preferences', [])
    if style_prefs:
        enhanced_parts.append(f"style: {', '.join(style_prefs)}")
    
    # Add color preferences
    color_prefs = preferences.get('color_preferences', [])
    if color_prefs:
        enhanced_parts.append(f"colors: {', '.join(color_prefs)}")
    
    # Add budget context
    budget_min = preferences.get('budget_min', 0)
    budget_max = preferences.get('budget_max', 0)
    if budget_max > 0:
        enhanced_parts.append(f"budget: Rs.{budget_min}-{budget_max}")
    
    # Add occasion context
    occasion = preferences.get('occasion', '')
    if occasion:
        enhanced_parts.append(f"occasion: {occasion}")
    
    # Add seasonal context
    season = context.get('season', '')
    if season:
        enhanced_parts.append(f"season: {season}")
    
    return ' | '.join(enhanced_parts)

def calculate_preference_scores(product_ids, preferences, query):
    """Calculate preference-based matching scores for products"""
    scores = []
    
    for product_id in product_ids:
        # Base score from retrieval ranking
        base_score = max(0.3, 1.0 - (len(scores) * 0.1))  # Decreasing score by position
        
        # This is a simplified version - in production, you'd fetch product details
        # and calculate more sophisticated matching based on actual product attributes
        preference_boost = 0.0
        
        # Style preference boost
        if preferences.get('style_preferences'):
            preference_boost += 0.1
        
        # Color preference boost
        if preferences.get('color_preferences'):
            preference_boost += 0.1
        
        # Budget consideration boost
        if preferences.get('budget_max', 0) > 0:
            preference_boost += 0.05
        
        # Occasion boost
        if preferences.get('occasion'):
            preference_boost += 0.05
        
        final_score = min(1.0, base_score + preference_boost)
        scores.append(round(final_score, 3))
    
    return scores

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
            'embedding_model': os.getenv('OPENAI_EMBEDDING_MODEL', 'text-embedding-ada-002'),
            'llm_provider': current_provider,
            'llm_model': get_current_model_name(),
            'status': 'ready'
        })
        
    except Exception as e:
        return jsonify({'error': f'Error getting vector store stats: {str(e)}'}), 500

@app.route('/providers', methods=['GET'])
def list_providers():
    """List available providers and their status"""
    providers = {
        'openai': {
            'available': bool(os.getenv('OPENAI_API_KEY')),
            'models': {
                'llm': os.getenv('OPENAI_LLM_MODEL', 'gpt-3.5-turbo'),
                'embeddings': os.getenv('OPENAI_EMBEDDING_MODEL', 'text-embedding-ada-002')
            }
        },
        'groq': {
            'available': bool(os.getenv('GROQ_API_KEY')) and GROQ_AVAILABLE,
            'sdk_installed': GROQ_AVAILABLE,
            'models': {
                'llm': os.getenv('GROQ_LLM_MODEL', 'llama-3.1-8b-instant'),
                'embeddings': 'Uses OpenAI embeddings'
            }
        }
    }
    
    return jsonify({
        'current_provider': current_provider,
        'available_providers': providers,
        'provider_priority': ['groq', 'openai'] if current_provider == 'groq' else ['openai', 'groq']
    })

def get_current_model_name():
    """Get the current model name based on provider"""
    if current_provider == 'openai':
        return os.getenv('OPENAI_LLM_MODEL', 'gpt-3.5-turbo')
    elif current_provider == 'groq':
        return os.getenv('GROQ_LLM_MODEL', 'llama-3.1-8b-instant')
    return 'unknown'

@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    print("🔧 Starting StyleMe Multi-Provider RAG Service...")
    print(f"🤖 Available providers: OpenAI{'✓' if os.getenv('OPENAI_API_KEY') else '✗'}, Groq{'✓' if GROQ_AVAILABLE and os.getenv('GROQ_API_KEY') else '✗'}")
    
    # Initialize RAG system
    if not initialize_rag_system():
        print("❌ Failed to initialize RAG system. Exiting.")
        exit(1)
    
    print("🌟 RAG Service is ready to serve requests!")
    print(f"🔗 Health check: http://localhost:{os.getenv('FLASK_PORT', 5000)}/")
    print(f"🔍 Search endpoint: http://localhost:{os.getenv('FLASK_PORT', 5000)}/search")
    print(f"🤖 Providers endpoint: http://localhost:{os.getenv('FLASK_PORT', 5000)}/providers")
    print(f"⚡ Using {current_provider.upper()} as LLM provider")
    
    # Start Flask application
    app.run(
        host=os.getenv('FLASK_HOST', '0.0.0.0'),
        port=int(os.getenv('FLASK_PORT', 5000)),
        debug=os.getenv('FLASK_DEBUG', 'True').lower() == 'true'
    )
