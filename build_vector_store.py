#!/usr/bin/env python3
"""Build the FAISS vector store from product data"""

import os
import sys
import json
import time
from datetime import datetime
import mysql.connector
from sentence_transformers import SentenceTransformer
import faiss
import numpy as np
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

def connect_to_database():
    """Connect to MySQL database"""
    try:
        connection = mysql.connector.connect(
            host=os.getenv('DB_HOST', 'localhost'),
            user=os.getenv('DB_USER', 'root'),
            password=os.getenv('DB_PASSWORD', '1488@@Mihisara'),
            database=os.getenv('DB_NAME', 'ecommerce_sl'),
            charset='utf8mb4',
            collation='utf8mb4_unicode_ci'
        )
        return connection
    except Exception as e:
        print(f"❌ Database connection failed: {e}")
        return None

def fetch_products():
    """Fetch all products from database"""
    connection = connect_to_database()
    if not connection:
        return []
    
    try:
        cursor = connection.cursor(dictionary=True)
        query = """
        SELECT 
            p.id as product_id,
            p.name,
            p.description,
            p.price,
            p.discount_price,
            p.brand,
            p.color,
            p.size,
            p.gender,
            p.occasion,
            p.stock,
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.stock > 0
        ORDER BY p.id
        """
        
        cursor.execute(query)
        products = cursor.fetchall()
        print(f"✅ Fetched {len(products)} products from database")
        return products
        
    except Exception as e:
        print(f"❌ Error fetching products: {e}")
        return []
    finally:
        if connection:
            connection.close()

def create_product_text(product):
    """Create searchable text representation of product"""
    parts = []
    
    if product['name']:
        parts.append(product['name'])
    if product['description']:
        parts.append(product['description'])
    if product['brand']:
        parts.append(f"Brand: {product['brand']}")
    if product['category_name']:
        parts.append(f"Category: {product['category_name']}")
    if product['color']:
        parts.append(f"Color: {product['color']}")
    if product['size']:
        parts.append(f"Size: {product['size']}")
    if product['gender']:
        parts.append(f"Gender: {product['gender']}")
    if product['occasion']:
        parts.append(f"Occasion: {product['occasion']}")
    
    # Add price information
    if product['discount_price']:
        parts.append(f"Price: Rs.{product['discount_price']} (discounted from Rs.{product['price']})")
    else:
        parts.append(f"Price: Rs.{product['price']}")
    
    return " ".join(parts)

def build_vector_store():
    """Build FAISS vector store from product data"""
    print("🏗️ Building FAISS Vector Store...")
    print("=" * 50)
    
    # Fetch products
    products = fetch_products()
    if not products:
        print("❌ No products found!")
        return False
    
    # Load embedding model
    print("🤗 Loading SentenceTransformer model...")
    model = SentenceTransformer('all-MiniLM-L6-v2')
    
    # Create text representations
    print("📝 Creating product text representations...")
    product_texts = []
    product_metadata = []
    
    for product in products:
        text = create_product_text(product)
        product_texts.append(text)
        
        # Store metadata
        metadata = {
            'product_id': product['product_id'],
            'name': product['name'],
            'price': product['discount_price'] if product['discount_price'] else product['price'],
            'category': product['category_name'],
            'brand': product['brand'],
            'color': product['color'],
            'size': product['size'],
            'gender': product['gender'],
            'occasion': product['occasion']
        }
        product_metadata.append(metadata)
    
    # Generate embeddings
    print("🔢 Generating embeddings...")
    embeddings = model.encode(product_texts, show_progress_bar=True)
    
    # Create FAISS index
    print("🗂️ Creating FAISS index...")
    dimension = embeddings.shape[1]
    index = faiss.IndexFlatIP(dimension)  # Inner product for similarity
    
    # Normalize embeddings for cosine similarity
    faiss.normalize_L2(embeddings.astype('float32'))
    
    # Add to index
    index.add(embeddings.astype('float32'))
    
    # Save index and metadata
    print("💾 Saving vector store...")
    faiss.write_index(index, 'faiss_index')
    
    with open('product_metadata.json', 'w', encoding='utf-8') as f:
        json.dump(product_metadata, f, ensure_ascii=False, indent=2)
    
    print(f"✅ Vector store built successfully!")
    print(f"   - Index dimension: {dimension}")
    print(f"   - Total products: {len(products)}")
    print(f"   - Index size: {index.ntotal} vectors")
    
    return True

def test_vector_store():
    """Test the created vector store"""
    print("\n🧪 Testing Vector Store...")
    print("=" * 30)
    
    try:
        # Load index and metadata
        index = faiss.read_index('faiss_index')
        
        with open('product_metadata.json', 'r', encoding='utf-8') as f:
            metadata = json.load(f)
        
        # Load model
        model = SentenceTransformer('all-MiniLM-L6-v2')
        
        # Test search
        test_query = "casual blue shirt"
        print(f"🔍 Testing search: '{test_query}'")
        
        query_embedding = model.encode([test_query])
        faiss.normalize_L2(query_embedding.astype('float32'))
        
        # Search
        similarities, indices = index.search(query_embedding.astype('float32'), 5)
        
        print("📋 Top 5 Results:")
        for i, (similarity, idx) in enumerate(zip(similarities[0], indices[0])):
            product = metadata[idx]
            print(f"  {i+1}. Product ID: {product['product_id']}")
            print(f"     Name: {product['name']}")
            print(f"     Similarity: {similarity:.3f}")
            print()
        
        return True
        
    except Exception as e:
        print(f"❌ Test failed: {e}")
        return False

if __name__ == "__main__":
    start_time = time.time()
    
    print("🚀 RAG Vector Store Builder")
    print("=" * 50)
    print(f"📅 Started at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()
    
    # Build vector store
    success = build_vector_store()
    
    if success:
        # Test the vector store
        test_vector_store()
        
        elapsed_time = time.time() - start_time
        print(f"🎉 Vector store build completed in {elapsed_time:.2f} seconds!")
        print("\n💡 Next steps:")
        print("   1. Restart your RAG service")
        print("   2. Test searches on your web application")
        print("   3. Try queries like 'casual blue shirt' or 'formal wear'")
    else:
        print("❌ Vector store build failed!")
        sys.exit(1)
