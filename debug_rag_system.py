#!/usr/bin/env python3
"""Debug script to check vector store and RAG functionality"""

import os
import json
from langchain_community.vectorstores import FAISS
from langchain_huggingface import HuggingFaceEmbeddings

def test_vector_store():
    """Test if vector store exists and has data"""
    print("🔍 Testing Vector Store...")
    
    try:
        # Load embeddings
        embeddings = HuggingFaceEmbeddings(
            model_name='all-MiniLM-L6-v2',
            model_kwargs={'device': 'cpu'},
            encode_kwargs={'normalize_embeddings': True}
        )
        
        # Load vector store
        vector_store_path = 'faiss_index'
        if not os.path.exists(vector_store_path):
            print(f"❌ Vector store not found at {vector_store_path}")
            return False
            
        vector_store = FAISS.load_local(
            vector_store_path, 
            embeddings, 
            allow_dangerous_deserialization=True
        )
        
        # Test search
        test_queries = [
            "green color formal shirt for office",
            "casual blue shirt", 
            "formal wear",
            "shirt"
        ]
        
        print(f"✅ Vector store loaded successfully")
        print(f"📊 Total vectors: {vector_store.index.ntotal}")
        
        for query in test_queries:
            print(f"\n🔎 Testing query: '{query}'")
            docs = vector_store.similarity_search(query, k=5)
            
            if docs:
                print(f"   Found {len(docs)} results:")
                for i, doc in enumerate(docs, 1):
                    metadata = doc.metadata
                    content = doc.page_content[:100]
                    product_id = metadata.get('product_id', 'Unknown')
                    print(f"   {i}. ID: {product_id} | Content: {content}...")
            else:
                print("   ❌ No results found")
        
        return True
        
    except Exception as e:
        print(f"❌ Error testing vector store: {e}")
        return False

def test_database_products():
    """Test if we can connect to database and get products"""
    print("\n🗄️ Testing Database Connection...")
    
    try:
        import mysql.connector
        
        DB_CONFIG = {
            'host': 'localhost',
            'database': 'ecommerce_sl',
            'user': 'root',
            'password': ''  # Add password if needed
        }
        
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        # Count products
        cursor.execute("SELECT COUNT(*) FROM products")
        count = cursor.fetchone()[0]
        print(f"✅ Database connected. Found {count} products")
        
        # Get sample products
        cursor.execute("SELECT product_id, name, price FROM products LIMIT 5")
        products = cursor.fetchall()
        
        print("📦 Sample products:")
        for product_id, name, price in products:
            print(f"   ID: {product_id} | {name} | Rs.{price}")
        
        cursor.close()
        conn.close()
        return True
        
    except Exception as e:
        print(f"❌ Database error: {e}")
        return False

if __name__ == "__main__":
    print("🧪 RAG System Debug Test")
    print("=" * 50)
    
    # Test vector store
    vector_ok = test_vector_store()
    
    # Test database
    db_ok = test_database_products()
    
    print("\n" + "=" * 50)
    if vector_ok and db_ok:
        print("✅ Both vector store and database are working!")
    else:
        print("❌ Issues found that need fixing:")
        if not vector_ok:
            print("   - Vector store issues")
        if not db_ok:
            print("   - Database connectivity issues")
