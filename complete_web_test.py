#!/usr/bin/env python3
"""
Complete Web Integration Test for StyleMe Enhanced RAG Search
Tests the full web application integration with intelligent search
"""

import requests
import json
import time
import subprocess
import os
import webbrowser
from datetime import datetime

class WebIntegrationTester:
    def __init__(self):
        self.rag_service_url = "http://localhost:5000"
        self.php_app_url = "http://localhost:8080"  # Updated to correct port
        self.api_url = f"{self.php_app_url}/api"
        
    def run_complete_test(self):
        """Run complete integration test"""
        print("🎨 StyleMe Enhanced RAG Search - Complete Web Integration Test")
        print("=" * 70)
        
        # Step 1: Check RAG Service
        if not self.check_rag_service():
            return False
            
        # Step 2: Test PHP APIs
        if not self.test_php_apis():
            return False
            
        # Step 3: Test Intelligent Search Integration
        if not self.test_intelligent_search():
            return False
            
        # Step 4: Test User Preferences
        if not self.test_user_preferences():
            return False
            
        # Step 5: Demo Web Interface
        self.demo_web_interface()
        
        return True
    
    def check_rag_service(self):
        """Check if RAG service is running"""
        print("\n🔍 Step 1: Checking RAG Service Status")
        print("-" * 40)
        
        try:
            response = requests.get(f"{self.rag_service_url}/", timeout=5)
            if response.status_code == 200:
                data = response.json()
                print(f"✅ RAG Service: Online")
                print(f"🤖 Provider: {data.get('provider_info', {}).get('current_provider', 'unknown').upper()}")
                print(f"📊 Status: {data.get('rag_system', 'unknown')}")
                return True
            else:
                print(f"❌ RAG Service: HTTP {response.status_code}")
                return False
        except requests.exceptions.ConnectionError:
            print("❌ RAG Service: Not running")
            print("💡 Please start RAG service: python app.py")
            return False
        except Exception as e:
            print(f"❌ RAG Service Error: {e}")
            return False
    
    def test_php_apis(self):
        """Test PHP API endpoints"""
        print("\n🐘 Step 2: Testing PHP APIs")
        print("-" * 40)
        
        success = True
        
        # Test products API
        try:
            response = requests.get(f"{self.api_url}/products.php?limit=5", timeout=10)
            if response.status_code == 200:
                data = response.json()
                print(f"✅ Products API: {len(data.get('products', []))} products loaded")
            else:
                print(f"❌ Products API: HTTP {response.status_code}")
                success = False
        except Exception as e:
            print(f"❌ Products API Error: {e}")
            success = False
        
        # Test products by IDs API
        try:
            response = requests.get(f"{self.api_url}/products.php?ids=1,2,3", timeout=10)
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    print(f"✅ Products by IDs API: {data.get('total', 0)} products found")
                else:
                    print(f"❌ Products by IDs API: {data.get('message', 'Unknown error')}")
                    success = False
            else:
                print(f"❌ Products by IDs API: HTTP {response.status_code}")
                success = False
        except Exception as e:
            print(f"❌ Products by IDs API Error: {e}")
            success = False
        
        return success
    
    def test_intelligent_search(self):
        """Test the intelligent search integration"""
        print("\n🧠 Step 3: Testing Intelligent Search Integration")
        print("-" * 40)
        
        test_queries = [
            {
                "query": "comfortable casual shirt for office work",
                "preferences": {
                    "style_preferences": ["casual", "formal"],
                    "color_preferences": ["blue", "white"],
                    "budget_min": 2000,
                    "budget_max": 6000,
                    "occasion": "office"
                }
            },
            {
                "query": "party dress for evening events",
                "preferences": {
                    "style_preferences": ["party", "western"],
                    "color_preferences": ["red", "black"],
                    "budget_min": 5000,
                    "budget_max": 15000,
                    "occasion": "party"
                }
            }
        ]
        
        for i, test in enumerate(test_queries, 1):
            print(f"\n🎯 Test Query {i}: '{test['query']}'")
            
            try:
                payload = {
                    "query": test["query"],
                    "user_preferences": test["preferences"],
                    "user_id": 999
                }
                
                start_time = time.time()
                response = requests.post(
                    f"{self.rag_service_url}/search_with_preferences",
                    json=payload,
                    timeout=15
                )
                end_time = time.time()
                
                if response.status_code == 200:
                    data = response.json()
                    product_ids = data.get('product_ids', [])
                    scores = data.get('matching_scores', [])
                    
                    print(f"   ✅ Success: Found {len(product_ids)} products")
                    print(f"   ⏱️ Response time: {end_time - start_time:.2f}s")
                    print(f"   📊 Top scores: {scores[:3] if scores else 'N/A'}")
                    
                    # Test if we can fetch these products from PHP API
                    if product_ids:
                        try:
                            ids_str = ','.join(map(str, product_ids[:5]))
                            php_response = requests.get(
                                f"{self.api_url}/products.php?ids={ids_str}",
                                timeout=10
                            )
                            
                            if php_response.status_code == 200:
                                php_data = php_response.json()
                                if php_data.get('success'):
                                    print(f"   ✅ PHP Integration: Retrieved {php_data.get('total', 0)} product details")
                                else:
                                    print(f"   ⚠️ PHP Integration: {php_data.get('message', 'Unknown error')}")
                            else:
                                print(f"   ❌ PHP Integration: HTTP {php_response.status_code}")
                        except Exception as e:
                            print(f"   ❌ PHP Integration Error: {e}")
                else:
                    print(f"   ❌ RAG Search failed: HTTP {response.status_code}")
                    print(f"   Response: {response.text[:100]}...")
                    return False
                    
            except Exception as e:
                print(f"   ❌ Search Error: {e}")
                return False
        
        return True
    
    def test_user_preferences(self):
        """Test user preferences functionality"""
        print("\n👤 Step 4: Testing User Preferences")
        print("-" * 40)
        
        test_user_id = 12345
        test_preferences = {
            "style_preferences": ["casual", "business"],
            "color_preferences": ["blue", "grey", "white"],
            "budget_min": 3000,
            "budget_max": 12000,
            "occasion": "office",
            "season": "all-season"
        }
        
        # Test saving preferences
        try:
            save_payload = {
                "user_id": test_user_id,
                "preferences": test_preferences
            }
            
            response = requests.post(
                f"{self.api_url}/save_preferences.php",
                json=save_payload,
                timeout=10
            )
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    print("✅ Save Preferences: Success")
                else:
                    print(f"❌ Save Preferences: {data.get('message', 'Unknown error')}")
                    return False
            else:
                print(f"❌ Save Preferences: HTTP {response.status_code}")
                return False
                
        except Exception as e:
            print(f"❌ Save Preferences Error: {e}")
            return False
        
        # Test retrieving preferences
        try:
            response = requests.get(
                f"{self.api_url}/get_user_preferences.php?user_id={test_user_id}",
                timeout=10
            )
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    retrieved_prefs = data.get('preferences', {})
                    print("✅ Get Preferences: Success")
                    print(f"   📝 Styles: {retrieved_prefs.get('style_preferences', [])}")
                    print(f"   🎨 Colors: {retrieved_prefs.get('color_preferences', [])}")
                    print(f"   💰 Budget: Rs.{retrieved_prefs.get('budget_min', 0)}-{retrieved_prefs.get('budget_max', 0)}")
                    return True
                else:
                    print(f"❌ Get Preferences: {data.get('message', 'Unknown error')}")
                    return False
            else:
                print(f"❌ Get Preferences: HTTP {response.status_code}")
                return False
                
        except Exception as e:
            print(f"❌ Get Preferences Error: {e}")
            return False
    
    def demo_web_interface(self):
        """Demo the web interface"""
        print("\n🌐 Step 5: Web Interface Demo")
        print("-" * 40)
        
        products_url = f"{self.php_app_url}/products.html"
        
        print(f"🚀 Opening web interface: {products_url}")
        print("📋 Manual Test Instructions:")
        print("   1. Look for the enhanced search bar at the top")
        print("   2. Try searching: 'casual blue shirt for office meetings'")
        print("   3. Click the preferences button (⚙️) to adjust preferences")
        print("   4. Check for matching percentages on product cards")
        print("   5. Notice AI-powered recommendations")
        
        try:
            # Try to open in browser (may not work in all environments)
            # webbrowser.open(products_url)
            print(f"💡 Manually open: {products_url}")
        except Exception:
            print(f"💡 Please manually open: {products_url}")
        
        print("\n🎯 What to look for:")
        print("   • 🤖 Intelligent search bar with AI icon")
        print("   • ⚙️ Preferences panel with style/color/budget options")
        print("   • 📊 Matching percentages on product cards")
        print("   • 🟢 Excellent/Good/Moderate match indicators")
        print("   • ⚡ Fast AI-powered search results")
        
    def create_usage_guide(self):
        """Create a usage guide for the system"""
        guide = f"""
# StyleMe Enhanced RAG Search - Usage Guide

Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}

## 🚀 System Status
- RAG Service: Running on {self.rag_service_url}
- PHP Application: {self.php_app_url}
- Integration: Complete ✅

## 🎯 How to Use

### 1. Start Services
```bash
# Terminal 1: Start RAG Service
cd rag_service
python app.py

# Terminal 2: Start PHP Server (if using built-in server)
cd StyleMe
php -S localhost:8000
```

### 2. Access Web Interface
- Open: {self.php_app_url}/products.html
- Look for the enhanced search bar (blue gradient background)

### 3. Smart Search Features
- **Natural Language**: "comfortable shirt for office meetings under 5000"
- **Preferences Panel**: Click ⚙️ to set style, color, budget preferences
- **Matching Scores**: See percentage matches on each product
- **AI Recommendations**: Get contextual clothing suggestions

### 4. User Experience
- Products with 80%+ match show as "🟢 EXCELLENT"
- Products with 60-79% match show as "🟡 GOOD MATCH"
- Products with 40-59% match show as "🟠 MODERATE"
- Products below 40% show as "⚪ LOW MATCH"

### 5. Advanced Features
- **Preference Learning**: System saves and learns from user preferences
- **Contextual Search**: AI understands occasion, season, style context
- **Fast Performance**: Local embeddings + Groq LLM for speed
- **Cost Effective**: No OpenAI costs, uses free tiers

## 🎉 Success Indicators
- ✅ Search results appear within 2-3 seconds
- ✅ Matching percentages display on product cards
- ✅ Preferences panel opens and saves settings
- ✅ AI provides relevant clothing recommendations
- ✅ System handles natural language queries

## 🔧 Troubleshooting
- **No search results**: Check if RAG service is running on port 5000
- **PHP errors**: Ensure database connection is configured
- **Slow responses**: First search may be slower due to model loading
- **No matching scores**: Verify RAG service is accessible from PHP

## 📊 Test Queries to Try
1. "casual blue shirt for office casual friday"
2. "party dress for evening events under 10000"
3. "sports wear for gym workouts in summer"
4. "formal blazer for business meetings"
5. "comfortable jeans for daily wear"

Your StyleMe application now has intelligent AI-powered search! 🎉
        """
        
        with open('STYLEME_USAGE_GUIDE.md', 'w', encoding='utf-8') as f:
            f.write(guide)
        
        print(f"📋 Usage guide saved to: STYLEME_USAGE_GUIDE.md")

def main():
    """Main test function"""
    tester = WebIntegrationTester()
    
    success = tester.run_complete_test()
    
    if success:
        print("\n" + "=" * 70)
        print("🎉 COMPLETE INTEGRATION TEST PASSED!")
        print("✅ RAG Service: Working")
        print("✅ PHP APIs: Working") 
        print("✅ Intelligent Search: Working")
        print("✅ User Preferences: Working")
        print("✅ Web Integration: Ready")
        
        tester.create_usage_guide()
        
        print("\n🚀 Your StyleMe web application is fully integrated!")
        print("💡 Open products.html and try the intelligent search!")
        
    else:
        print("\n" + "=" * 70)
        print("❌ INTEGRATION TEST FAILED")
        print("Please check the errors above and fix them.")
        print("💡 Ensure RAG service is running: python app.py")
        print("💡 Ensure PHP server is running with database access")

if __name__ == "__main__":
    main()
