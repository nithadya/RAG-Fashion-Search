#!/usr/bin/env python3
"""
Test script for the StyleMe RAG service
This script tests the RAG service functionality
"""

import requests
import json
import time

# Configuration
RAG_SERVICE_URL = "http://localhost:5000"
TEST_USER_ID = 1

def test_health_check():
    """Test the health check endpoint"""
    print("🔍 Testing health check...")
    try:
        response = requests.get(f"{RAG_SERVICE_URL}/")
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Health check passed:")
            print(f"   📊 Status: {data.get('status')}")
            print(f"   🤖 Provider: {data.get('provider_info', {}).get('current_provider', 'unknown')}")
            print(f"   🔧 Version: {data.get('version')}")
            print(f"   🧠 RAG System: {data.get('rag_system')}")
            
            # Show provider availability
            provider_info = data.get('provider_info', {})
            print(f"   🔑 OpenAI Available: {provider_info.get('openai_available', False)}")
            print(f"   ⚡ Groq Available: {provider_info.get('groq_available', False)}")
            
            return True
        else:
            print(f"❌ Health check failed: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Health check error: {e}")
        return False

def test_providers_endpoint():
    """Test the providers information endpoint"""
    print("\n🤖 Testing providers endpoint...")
    try:
        response = requests.get(f"{RAG_SERVICE_URL}/providers")
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Providers info retrieved:")
            print(f"   🎯 Current provider: {data.get('current_provider')}")
            
            providers = data.get('available_providers', {})
            for name, info in providers.items():
                status = "✅" if info.get('available') else "❌"
                print(f"   {status} {name.upper()}: {info.get('models', {}).get('llm', 'unknown')}")
            
            return True
        else:
            print(f"❌ Providers endpoint failed: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Providers endpoint error: {e}")
        return False

def test_vector_store_stats():
    """Test the vector store statistics endpoint"""
    print("\n📊 Testing vector store stats...")
    try:
        response = requests.get(f"{RAG_SERVICE_URL}/vector-store/stats")
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Vector store stats: {data}")
            return True
        else:
            print(f"❌ Vector store stats failed: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Vector store stats error: {e}")
        return False

def test_search(query, expected_results=True):
    """Test a search query"""
    print(f"\n🔍 Testing search: '{query}'")
    try:
        payload = {
            "user_id": TEST_USER_ID,
            "query": query
        }
        
        start_time = time.time()
        response = requests.post(
            f"{RAG_SERVICE_URL}/search",
            json=payload,
            headers={'Content-Type': 'application/json'}
        )
        end_time = time.time()
        
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Search successful:")
            print(f"   📦 Product IDs: {data.get('product_ids', [])}")
            print(f"   📊 Results count: {data.get('results_count', 0)}")
            print(f"   ⏱️  Processing time: {data.get('processing_time', 0)}s")
            print(f"   🕐 Total request time: {end_time - start_time:.3f}s")
            print(f"   📚 History considered: {data.get('history_considered', False)}")
            print(f"   🤖 Provider used: {data.get('provider_used', 'unknown').upper()}")
            
            if expected_results and data.get('results_count', 0) == 0:
                print("⚠️  Warning: No results returned (might be expected)")
            
            return True
        else:
            print(f"❌ Search failed: {response.status_code}")
            try:
                error_data = response.json()
                print(f"   Error: {error_data}")
            except:
                print(f"   Raw response: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Search error: {e}")
        return False

def run_comprehensive_tests():
    """Run a comprehensive set of tests"""
    print("🚀 Starting comprehensive Multi-Provider RAG service tests...\n")
    
    # Test cases
    test_queries = [
        "men's shirt",
        "women's dress red color",
        "casual wear under 3000 rupees",
        "formal trouser black",
        "saree traditional",
        "oversized tee white",
        "sportswear for men",
        "blue jeans women",
        "party dress",
        "affordable shirts"
    ]
    
    results = {
        'health_check': False,
        'providers_check': False,
        'vector_store_stats': False,
        'successful_searches': 0,
        'total_searches': len(test_queries)
    }
    
    # Run tests
    results['health_check'] = test_health_check()
    results['providers_check'] = test_providers_endpoint()
    results['vector_store_stats'] = test_vector_store_stats()
    
    print(f"\n🔍 Running {len(test_queries)} search tests...")
    
    for query in test_queries:
        if test_search(query):
            results['successful_searches'] += 1
        time.sleep(0.5)  # Small delay between requests
    
    # Summary
    print("\n" + "="*70)
    print("📋 MULTI-PROVIDER RAG SERVICE TEST SUMMARY")
    print("="*70)
    print(f"✅ Health Check: {'PASS' if results['health_check'] else 'FAIL'}")
    print(f"🤖 Providers Check: {'PASS' if results['providers_check'] else 'FAIL'}")
    print(f"📊 Vector Store Stats: {'PASS' if results['vector_store_stats'] else 'FAIL'}")
    print(f"🔍 Search Tests: {results['successful_searches']}/{results['total_searches']} passed")
    
    success_rate = (results['successful_searches'] / results['total_searches']) * 100
    print(f"� Success Rate: {success_rate:.1f}%")
    
    if success_rate >= 80:
        print("🎉 Multi-Provider RAG service is working excellently!")
    elif success_rate >= 60:
        print("⚠️  RAG service has some issues but is functional")
    else:
        print("❌ RAG service needs attention")
    
    print("="*70)

if __name__ == "__main__":
    print("StyleMe RAG Service Test Suite")
    print("=" * 40)
    
    # Quick test option
    import sys
    if len(sys.argv) > 1 and sys.argv[1] == "quick":
        print("⚡ Running quick multi-provider test...")
        test_health_check()
        test_providers_endpoint()
        test_search("men's shirt")
    else:
        run_comprehensive_tests()
