#!/usr/bin/env python3
"""
Test the Enhanced RAG Search with Preferences
"""
import requests
import json
import time

def test_enhanced_search():
    print("🧪 Testing Enhanced RAG Search with Preferences")
    print("=" * 60)
    
    # Test data
    test_cases = [
        {
            "query": "casual blue shirt for office",
            "preferences": {
                "style_preferences": ["casual", "formal"],
                "color_preferences": ["blue", "white"],
                "budget_min": 1000,
                "budget_max": 8000,
                "occasion": "office"
            },
            "user_id": 1
        },
        {
            "query": "party dress red",
            "preferences": {
                "style_preferences": ["party", "western"],
                "color_preferences": ["red", "black"],
                "budget_min": 2000,
                "budget_max": 15000,
                "occasion": "party"
            },
            "user_id": 2
        },
        {
            "query": "sports wear for men",
            "preferences": {
                "style_preferences": ["sporty", "casual"],
                "color_preferences": ["black", "grey"],
                "budget_min": 500,
                "budget_max": 5000,
                "occasion": "sports"
            },
            "user_id": 1
        }
    ]
    
    base_url = "http://localhost:5000"
    
    for i, test_case in enumerate(test_cases, 1):
        print(f"\n🔍 Test Case {i}: {test_case['query']}")
        print(f"👤 User ID: {test_case['user_id']}")
        print(f"⚙️ Preferences: {json.dumps(test_case['preferences'], indent=2)}")
        
        try:
            # Test enhanced search endpoint
            response = requests.post(
                f"{base_url}/search_with_preferences",
                json=test_case,
                timeout=30
            )
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    print(f"✅ Success!")
                    print(f"   📦 Product IDs: {data.get('product_ids', [])}")
                    print(f"   📊 Matching Scores: {data.get('matching_scores', [])}")
                    print(f"   🔍 Enhanced Query: {data.get('enhanced_query', '')}")
                    print(f"   ⏱️  Processing Time: {data.get('processing_time', 0)}s")
                    print(f"   🤖 Provider: {data.get('provider_used', 'unknown')}")
                    print(f"   📈 Results: {data.get('results_count', 0)}")
                else:
                    print(f"❌ Failed: {data.get('message', 'Unknown error')}")
            else:
                print(f"❌ HTTP {response.status_code}: {response.text}")
                
        except requests.exceptions.ConnectionError:
            print("❌ Connection failed - make sure RAG service is running on localhost:5000")
        except Exception as e:
            print(f"❌ Error: {e}")
        
        time.sleep(1)  # Brief pause between tests

def test_regular_search_comparison():
    print("\n\n🔄 Comparing Regular vs Enhanced Search")
    print("=" * 60)
    
    query = "casual blue shirt"
    user_id = 1
    
    # Regular search
    print(f"\n📝 Regular Search: {query}")
    try:
        response = requests.post(
            "http://localhost:5000/search",
            json={"query": query, "user_id": user_id},
            timeout=15
        )
        
        if response.status_code == 200:
            data = response.json()
            print(f"   📦 Products: {data.get('product_ids', [])}")
            print(f"   ⏱️  Time: {data.get('processing_time', 0)}s")
        else:
            print(f"   ❌ Failed: {response.status_code}")
    except Exception as e:
        print(f"   ❌ Error: {e}")
    
    # Enhanced search
    print(f"\n✨ Enhanced Search: {query}")
    try:
        response = requests.post(
            "http://localhost:5000/search_with_preferences",
            json={
                "query": query,
                "user_id": user_id,
                "preferences": {
                    "style_preferences": ["casual"],
                    "color_preferences": ["blue"],
                    "budget_min": 1000,
                    "budget_max": 5000,
                    "occasion": "casual"
                }
            },
            timeout=15
        )
        
        if response.status_code == 200:
            data = response.json()
            print(f"   📦 Products: {data.get('product_ids', [])}")
            print(f"   📊 Scores: {data.get('matching_scores', [])}")
            print(f"   🔍 Enhanced: {data.get('enhanced_query', '')}")
            print(f"   ⏱️  Time: {data.get('processing_time', 0)}s")
        else:
            print(f"   ❌ Failed: {response.status_code}")
    except Exception as e:
        print(f"   ❌ Error: {e}")

if __name__ == "__main__":
    print("🚀 Enhanced RAG Search Testing Suite")
    print("🎯 Make sure the RAG service is running: python app.py")
    print("🔗 Service should be available at: http://localhost:5000")
    
    input("\n Press Enter to start testing...")
    
    test_enhanced_search()
    test_regular_search_comparison()
    
    print("\n\n🎉 Testing completed!")
    print("💡 Check the RAG service logs for detailed processing information")
