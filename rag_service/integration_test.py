#!/usr/bin/env python3
"""
Complete Integration Test for StyleMe Enhanced RAG System
"""
import requests
import json
import sys

def test_service_health():
    """Test if the RAG service is running"""
    print("🔍 Testing RAG Service Health...")
    try:
        response = requests.get('http://localhost:5000/', timeout=10)
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Service is healthy - Status: {data.get('status', 'unknown')}")
            print(f"   Provider: {data.get('provider', 'unknown')}")
            print(f"   Version: {data.get('version', 'unknown')}")
            return True
        else:
            print(f"❌ Service returned status {response.status_code}")
            return False
    except requests.exceptions.ConnectionError:
        print("❌ Cannot connect to RAG service at localhost:5000")
        print("   Make sure to run: python app.py")
        return False
    except Exception as e:
        print(f"❌ Health check failed: {e}")
        return False

def test_regular_search():
    """Test the regular search functionality"""
    print("\n📝 Testing Regular Search...")
    try:
        response = requests.post(
            'http://localhost:5000/search',
            json={
                "query": "casual blue shirt",
                "user_id": 1
            },
            timeout=30
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print(f"✅ Regular search successful")
                print(f"   Product IDs: {data.get('product_ids', [])}")
                print(f"   Results: {data.get('results_count', 0)}")
                print(f"   Processing time: {data.get('processing_time', 0)}s")
                return True
            else:
                print(f"❌ Search failed: {data.get('message', 'Unknown error')}")
        else:
            print(f"❌ HTTP {response.status_code}: {response.text}")
        return False
        
    except Exception as e:
        print(f"❌ Regular search error: {e}")
        return False

def test_enhanced_search():
    """Test the enhanced search with preferences"""
    print("\n✨ Testing Enhanced Search with Preferences...")
    
    test_data = {
        "query": "casual blue shirt for office",
        "user_id": 1,
        "preferences": {
            "style_preferences": ["casual", "formal"],
            "color_preferences": ["blue", "white"],
            "budget_min": 1000,
            "budget_max": 8000,
            "occasion": "office"
        },
        "context": {
            "season": "summer",
            "location": "Sri Lanka"
        }
    }
    
    try:
        response = requests.post(
            'http://localhost:5000/search_with_preferences',
            json=test_data,
            timeout=30
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print(f"✅ Enhanced search successful")
                print(f"   Product IDs: {data.get('product_ids', [])}")
                print(f"   Matching Scores: {data.get('matching_scores', [])}")
                print(f"   Enhanced Query: {data.get('enhanced_query', '')}")
                print(f"   Results: {data.get('results_count', 0)}")
                print(f"   Processing time: {data.get('processing_time', 0)}s")
                print(f"   Provider: {data.get('provider_used', 'unknown')}")
                return True
            else:
                print(f"❌ Enhanced search failed: {data.get('message', 'Unknown error')}")
        else:
            print(f"❌ HTTP {response.status_code}: {response.text}")
        return False
        
    except Exception as e:
        print(f"❌ Enhanced search error: {e}")
        return False

def test_php_integration():
    """Test PHP integration (if PHP server is running)"""
    print("\n🐘 Testing PHP Integration...")
    
    # Test if PHP server is running (assuming it's on port 8000 or 80)
    php_urls = ['http://localhost:8000', 'http://localhost:80', 'http://localhost']
    
    for url in php_urls:
        try:
            response = requests.get(f"{url}/StyleMe/api/rag_search.php", 
                                  params={"query": "test"}, timeout=5)
            if response.status_code != 404:
                print(f"✅ PHP server detected at {url}")
                return True
        except:
            continue
    
    print("⚠️  PHP server not detected - manual testing required")
    print("   To test PHP integration:")
    print("   1. Start your PHP server (XAMPP/WAMP/etc.)")
    print("   2. Navigate to your products page")
    print("   3. Try the enhanced search interface")
    return False

def run_complete_test():
    """Run all tests"""
    print("🧪 StyleMe Enhanced RAG Integration Test Suite")
    print("=" * 60)
    
    # Test 1: Service Health
    if not test_service_health():
        print("\n❌ RAG service is not running. Please start it first:")
        print("   cd rag_service && python app.py")
        return False
    
    # Test 2: Regular Search
    regular_works = test_regular_search()
    
    # Test 3: Enhanced Search
    enhanced_works = test_enhanced_search()
    
    # Test 4: PHP Integration
    php_works = test_php_integration()
    
    # Summary
    print("\n" + "=" * 60)
    print("🎯 TEST RESULTS SUMMARY")
    print("=" * 60)
    print(f"Service Health:      {'✅ PASS' if True else '❌ FAIL'}")
    print(f"Regular Search:      {'✅ PASS' if regular_works else '❌ FAIL'}")
    print(f"Enhanced Search:     {'✅ PASS' if enhanced_works else '❌ FAIL'}")
    print(f"PHP Integration:     {'✅ READY' if php_works else '⚠️  MANUAL TEST REQUIRED'}")
    
    if regular_works and enhanced_works:
        print("\n🎉 RAG System is fully operational!")
        print("💡 Next steps:")
        print("   1. Start your PHP server")
        print("   2. Open your products page")
        print("   3. Test the enhanced search interface")
        print("   4. Try searching with: 'casual blue shirt for office'")
    else:
        print("\n❌ Some tests failed. Check the errors above.")
    
    return regular_works and enhanced_works

if __name__ == "__main__":
    success = run_complete_test()
    sys.exit(0 if success else 1)
