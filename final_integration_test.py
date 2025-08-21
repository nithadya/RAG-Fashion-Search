#!/usr/bin/env python3
"""
Final Integration Verification for StyleMe Enhanced RAG Search
Complete end-to-end testing to verify all components are working
"""

import requests
import json
import time
import webbrowser
from datetime import datetime

def test_complete_integration():
    print("🎨 StyleMe Enhanced RAG Search - Final Integration Test")
    print("=" * 65)
    
    # Configuration
    rag_url = "http://localhost:5000"
    php_url = "http://localhost:8080"
    
    results = {
        'rag_service': False,
        'php_server': False,
        'products_api': False,
        'preferences_api': False,
        'enhanced_search': False,
        'web_interface': False
    }
    
    # Test 1: RAG Service
    print("\n🤖 Testing RAG Service...")
    try:
        response = requests.get(f"{rag_url}/", timeout=5)
        if response.status_code == 200:
            data = response.json()
            if data.get('rag_system') == 'initialized':
                results['rag_service'] = True
                print("✅ RAG Service: ONLINE")
                print(f"   Provider: {data.get('provider_info', {}).get('current_provider', 'Unknown').upper()}")
            else:
                print("❌ RAG Service: Not initialized")
        else:
            print(f"❌ RAG Service: HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ RAG Service: {str(e)[:50]}...")
    
    # Test 2: PHP Server
    print("\n🐘 Testing PHP Server...")
    try:
        response = requests.get(f"{php_url}/products.html", timeout=5)
        if response.status_code == 200:
            results['php_server'] = True
            print("✅ PHP Server: ONLINE")
            print(f"   Port: 8080")
        else:
            print(f"❌ PHP Server: HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ PHP Server: {str(e)[:50]}...")
    
    # Test 3: Products API
    print("\n📦 Testing Products API...")
    try:
        # Test regular products API
        response = requests.get(f"{php_url}/api/products.php?limit=3", timeout=10)
        if response.status_code == 200:
            data = response.json()
            if 'products' in data and len(data['products']) > 0:
                results['products_api'] = True
                print("✅ Products API: WORKING")
                print(f"   Sample products: {len(data['products'])}")
                
                # Test products by IDs API
                product_ids = [str(p.get('id', p.get('product_id', 1))) for p in data['products'][:2]]
                ids_response = requests.get(f"{php_url}/api/products.php?ids={','.join(product_ids)}", timeout=10)
                if ids_response.status_code == 200:
                    ids_data = ids_response.json()
                    if ids_data.get('success') and len(ids_data.get('products', [])) > 0:
                        print("✅ Products by IDs API: WORKING")
                    else:
                        print("⚠️  Products by IDs API: Limited functionality")
                else:
                    print("❌ Products by IDs API: Failed")
            else:
                print("❌ Products API: No products returned")
        else:
            print(f"❌ Products API: HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Products API: {str(e)[:50]}...")
    
    # Test 4: User Preferences API
    print("\n⚙️ Testing User Preferences API...")
    try:
        test_preferences = {
            "user_id": 999,
            "preferences": {
                "style_preferences": ["casual", "formal"],
                "color_preferences": ["blue", "black"],
                "budget_min": 1000,
                "budget_max": 8000,
                "occasion": "office",
                "season": "summer"
            }
        }
        
        # Save preferences
        response = requests.post(
            f"{php_url}/api/save_preferences.php",
            json=test_preferences,
            headers={'Content-Type': 'application/json'},
            timeout=10
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                results['preferences_api'] = True
                print("✅ Save Preferences API: WORKING")
                
                # Test get preferences
                get_response = requests.get(f"{php_url}/api/get_user_preferences.php?user_id=999", timeout=10)
                if get_response.status_code == 200:
                    get_data = get_response.json()
                    if get_data.get('success'):
                        print("✅ Get Preferences API: WORKING")
                    else:
                        print("⚠️  Get Preferences API: Limited functionality")
                else:
                    print("❌ Get Preferences API: Failed")
            else:
                print(f"❌ Save Preferences API: {data.get('message', 'Failed')}")
        else:
            print(f"❌ Save Preferences API: HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Preferences API: {str(e)[:50]}...")
    
    # Test 5: Enhanced Search Integration
    print("\n🧠 Testing Enhanced Search Integration...")
    try:
        search_payload = {
            "query": "casual shirt for office work",
            "user_preferences": {
                "style_preferences": ["casual", "formal"],
                "color_preferences": ["blue", "white"],
                "budget_min": 1000,
                "budget_max": 5000,
                "occasion": "office"
            },
            "user_id": 999
        }
        
        response = requests.post(
            f"{rag_url}/search_with_preferences",
            json=search_payload,
            headers={'Content-Type': 'application/json'},
            timeout=15
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('product_ids') and len(data['product_ids']) > 0:
                results['enhanced_search'] = True
                print("✅ Enhanced Search Integration: WORKING")
                print(f"   Found products: {len(data['product_ids'])}")
                print(f"   Processing time: {data.get('processing_time', 0):.2f}s")
                print(f"   AI provider: {data.get('provider', 'Unknown').upper()}")
            else:
                print("❌ Enhanced Search: No products found")
        else:
            print(f"❌ Enhanced Search: HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Enhanced Search: {str(e)[:50]}...")
    
    # Test 6: Web Interface
    print("\n🌐 Testing Web Interface Access...")
    try:
        response = requests.get(f"{php_url}/products.html", timeout=5)
        if response.status_code == 200:
            content = response.text
            if 'intelligent-search.js' in content and 'intelligent-search.css' in content:
                results['web_interface'] = True
                print("✅ Web Interface: READY")
                print("   Intelligent search interface integrated")
            else:
                print("⚠️  Web Interface: Missing intelligent search files")
        else:
            print(f"❌ Web Interface: HTTP {response.status_code}")
    except Exception as e:
        print(f"❌ Web Interface: {str(e)[:50]}...")
    
    # Final Results
    print("\n" + "=" * 65)
    print("🎯 FINAL INTEGRATION RESULTS")
    print("=" * 65)
    
    total_tests = len(results)
    passed_tests = sum(results.values())
    
    for test_name, status in results.items():
        icon = "✅" if status else "❌"
        name = test_name.replace('_', ' ').title()
        print(f"{icon} {name}")
    
    print(f"\nScore: {passed_tests}/{total_tests} tests passed")
    
    if passed_tests == total_tests:
        print("🎉 INTEGRATION COMPLETE!")
        print("🚀 Your StyleMe application is ready for intelligent search!")
        print(f"🌐 Open: {php_url}/products.html")
        print("🔍 Try searching: 'casual blue shirt for office work'")
        return True
    elif passed_tests >= 4:  # RAG + PHP + APIs working
        print("⚠️  MOSTLY WORKING!")
        print("💡 Minor issues detected but core functionality is operational")
        print(f"🌐 Open: {php_url}/products.html")
        return True
    else:
        print("❌ INTEGRATION INCOMPLETE")
        print("💡 Please fix the failed tests above")
        return False

if __name__ == "__main__":
    test_complete_integration()
