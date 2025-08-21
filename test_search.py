#!/usr/bin/env python3
"""Quick test script for RAG search functionality"""

import requests
import json

def test_simple_search():
    """Test the simple search endpoint"""
    url = "http://localhost:5000/search"
    data = {"query": "casual blue shirt"}
    
    try:
        print("🔍 Testing simple search...")
        response = requests.post(url, json=data)
        print(f"Status Code: {response.status_code}")
        print(f"Response: {json.dumps(response.json(), indent=2)}")
        return response.json()
    except Exception as e:
        print(f"❌ Error: {e}")
        return None

def test_enhanced_search():
    """Test the enhanced search with preferences"""
    url = "http://localhost:5000/search_with_preferences"
    data = {
        "query": "casual shirt", 
        "user_id": 1,
        "preferences": {
            "color": ["blue", "navy"],
            "category": ["shirts"],
            "size": ["M", "L"],
            "price_range": "mid"
        }
    }
    
    try:
        print("\n🎯 Testing enhanced search with preferences...")
        response = requests.post(url, json=data)
        print(f"Status Code: {response.status_code}")
        print(f"Response: {json.dumps(response.json(), indent=2)}")
        return response.json()
    except Exception as e:
        print(f"❌ Error: {e}")
        return None

if __name__ == "__main__":
    print("🧪 RAG Search Testing Script")
    print("=" * 50)
    
    # Test simple search
    simple_result = test_simple_search()
    
    # Test enhanced search
    enhanced_result = test_enhanced_search()
    
    print("\n✅ Testing completed!")
