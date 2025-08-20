import os
import mysql.connector
from dotenv import load_dotenv
from langchain.docstore.document import Document
from langchain_community.vectorstores import FAISS
from langchain_openai import OpenAIEmbeddings

# Load environment variables
load_dotenv()

def create_vector_store():
    """
    Create FAISS vector store from product data in MySQL database.
    This replaces the traditional approach of storing embeddings in the database.
    Note: Currently uses OpenAI embeddings regardless of LLM provider choice.
    """
    print("🚀 Starting vector store creation process...")
    print("📝 Note: Using OpenAI embeddings (works with both OpenAI and Groq LLM providers)")
    
    # Check if OpenAI API key is available for embeddings
    if not os.getenv('OPENAI_API_KEY'):
        print("❌ OPENAI_API_KEY is required for embeddings generation")
        print("💡 Please set your OpenAI API key in the .env file")
        return False
    
    # Database connection
    try:
        conn = mysql.connector.connect(
            host=os.getenv('DB_HOST'),
            user=os.getenv('DB_USER'),
            password=os.getenv('DB_PASSWORD'),
            database=os.getenv('DB_NAME')
        )
        cursor = conn.cursor(dictionary=True)
        print("✅ Connected to database successfully")
    except Exception as e:
        print(f"❌ Database connection failed: {e}")
        return False

    # Fetch products with category information
    try:
        cursor.execute('''
            SELECT p.id, p.name, p.description, p.brand, p.color, p.size, 
                   p.occasion, p.gender, p.price, p.discount_price,
                   c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.stock > 0
            ORDER BY p.id
        ''')
        products = cursor.fetchall()
        print(f"📦 Fetched {len(products)} products from database")
    except Exception as e:
        print(f"❌ Failed to fetch products: {e}")
        return False

    # Convert products to LangChain Document format
    documents = []
    for product in products:
        # Create rich product description for better semantic search
        page_content_parts = [
            f"Product: {product['name']}",
            f"Category: {product.get('category_name', 'Unknown')}",
            f"Description: {product.get('description', 'No description available')}"
        ]
        
        # Add optional details if they exist
        if product.get('brand'):
            page_content_parts.append(f"Brand: {product['brand']}")
        if product.get('color'):
            page_content_parts.append(f"Color: {product['color']}")
        if product.get('size'):
            page_content_parts.append(f"Size: {product['size']}")
        if product.get('occasion'):
            page_content_parts.append(f"Occasion: {product['occasion']}")
        if product.get('gender'):
            page_content_parts.append(f"Gender: {product['gender']}")
        
        # Price information
        price = product.get('discount_price') or product.get('price')
        if price:
            page_content_parts.append(f"Price: Rs. {price}")
        
        page_content = ". ".join(page_content_parts)
        
        # Metadata for retrieval
        metadata = {
            'product_id': product['id'],
            'category': product.get('category_name', 'Unknown'),
            'brand': product.get('brand', ''),
            'price': float(price) if price else 0.0,
            'gender': product.get('gender', ''),
            'color': product.get('color', ''),
            'occasion': product.get('occasion', '')
        }
        
        documents.append(Document(page_content=page_content, metadata=metadata))

    if not documents:
        print("❌ No products found to create vector store")
        return False

    print(f"📝 Created {len(documents)} documents for indexing")

    # Initialize OpenAI embeddings
    try:
        embeddings_config = {
            'model': os.getenv('OPENAI_EMBEDDING_MODEL', 'text-embedding-ada-002'),
            'api_key': os.getenv('OPENAI_API_KEY')
        }
        
        # Add project ID if provided (for sk-proj-... keys)
        project_id = os.getenv('OPENAI_PROJECT_ID')
        if project_id and project_id.strip():
            embeddings_config['project'] = project_id.strip()
            print(f"🔑 Using OpenAI project: {project_id.strip()}")
        
        embeddings = OpenAIEmbeddings(**embeddings_config)
        print("✅ Initialized OpenAI embeddings (compatible with all LLM providers)")
    except Exception as e:
        print(f"❌ Failed to initialize embeddings: {e}")
        print("💡 Make sure your OPENAI_API_KEY is set correctly in .env file")
        return False

    # Create FAISS vector store
    try:
        print("🔄 Creating FAISS vector store (this may take a few minutes)...")
        vector_store = FAISS.from_documents(documents, embeddings)
        print("✅ FAISS vector store created successfully")
    except Exception as e:
        print(f"❌ Failed to create vector store: {e}")
        return False

    # Save vector store locally
    try:
        vector_store_path = os.getenv('VECTOR_STORE_PATH', 'faiss_index')
        vector_store.save_local(vector_store_path)
        print(f"💾 Vector store saved to '{vector_store_path}' directory")
    except Exception as e:
        print(f"❌ Failed to save vector store: {e}")
        return False

    # Cleanup
    cursor.close()
    conn.close()
    
    print("🎉 Vector store creation completed successfully!")
    print(f"📊 Indexed {len(documents)} product documents")
    print("🔍 Your RAG system is now ready to provide intelligent product search")
    
    return True

if __name__ == '__main__':
    success = create_vector_store()
    if not success:
        print("\n❌ Vector store creation failed. Please check the errors above.")
        exit(1)
    
    print("\n✨ Next steps:")
    print("1. Start the Flask RAG service: python app.py")
    print("2. Test the search functionality through your web application")
