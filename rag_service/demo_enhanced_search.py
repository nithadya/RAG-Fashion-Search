#!/usr/bin/env python3
"""
StyleMe Enhanced RAG Search Demo
This demonstrates the complete integration with preferences and matching percentages
"""

import requests
import json
import time

def demo_enhanced_search():
    print("🎨 StyleMe Enhanced RAG Search Demo")
    print("=" * 50)
    print("This demo shows how your PHP project now has:")
    print("✨ Smart clothing recommendations")
    print("📊 Matching percentage calculations") 
    print("🎯 User preference integration")
    print("🧠 AI-powered style suggestions")
    print("\n" + "="*50)

    # Demo scenarios
    scenarios = [
        {
            "title": "Office Professional Looking for Casual Wear",
            "query": "comfortable shirt for office casual friday",
            "preferences": {
                "style_preferences": ["casual", "formal"],
                "color_preferences": ["blue", "white", "grey"],
                "budget_min": 2000,
                "budget_max": 8000,
                "occasion": "office"
            }
        },
        {
            "title": "Party Outfit Enthusiast",
            "query": "stunning red dress for evening party",
            "preferences": {
                "style_preferences": ["party", "western"],
                "color_preferences": ["red", "black"],
                "budget_min": 5000,
                "budget_max": 20000,
                "occasion": "party"
            }
        },
        {
            "title": "Budget-Conscious Student",
            "query": "affordable casual wear for daily use",
            "preferences": {
                "style_preferences": ["casual"],
                "color_preferences": ["blue", "black"],
                "budget_min": 500,
                "budget_max": 3000,
                "occasion": "casual"
            }
        }
    ]

    base_url = "http://localhost:5000"
    
    for i, scenario in enumerate(scenarios, 1):
        print(f"\n🎭 Scenario {i}: {scenario['title']}")
        print(f"🔍 Search Query: \"{scenario['query']}\"")
        print(f"⚙️ User Preferences:")
        for key, value in scenario['preferences'].items():
            print(f"   • {key.replace('_', ' ').title()}: {value}")
        
        try:
            # Call enhanced search
            response = requests.post(
                f"{base_url}/search_with_preferences",
                json={
                    "query": scenario['query'],
                    "user_id": i,
                    "preferences": scenario['preferences']
                },
                timeout=30
            )
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    print(f"\n📊 Search Results:")
                    print(f"   🎯 Found {data.get('results_count', 0)} matching products")
                    print(f"   📦 Product IDs: {data.get('product_ids', [])}")
                    print(f"   📈 Matching Scores: {data.get('matching_scores', [])}")
                    print(f"   🔍 Enhanced Query: \"{data.get('enhanced_query', '')}\"")
                    print(f"   ⏱️ Processing Time: {data.get('processing_time', 0):.2f}s")
                    print(f"   🤖 AI Provider: {data.get('provider_used', 'Unknown')}")
                    
                    # Show what the user would see
                    product_ids = data.get('product_ids', [])
                    scores = data.get('matching_scores', [])
                    
                    if product_ids and scores:
                        print(f"\n🎨 What Users See on Frontend:")
                        for pid, score in zip(product_ids[:3], scores[:3]):  # Show top 3
                            percentage = int(score * 100) if score < 1 else score
                            if percentage >= 80:
                                badge_color = "🟢 HIGH MATCH"
                            elif percentage >= 60:
                                badge_color = "🟡 MEDIUM MATCH"
                            else:
                                badge_color = "⚪ LOW MATCH"
                            
                            print(f"   📱 Product {pid}: {percentage}% {badge_color}")
                            
                else:
                    print(f"❌ Search failed: {data.get('message', 'Unknown error')}")
            else:
                print(f"❌ HTTP Error {response.status_code}")
                
        except requests.exceptions.ConnectionError:
            print("❌ Cannot connect to RAG service")
            print("   Please start the service: python app.py")
            break
        except Exception as e:
            print(f"❌ Error: {e}")
        
        print(f"\n" + "-"*50)
        time.sleep(1)  # Brief pause between scenarios

    # Show integration summary
    print(f"\n🎉 Integration Summary")
    print("="*50)
    print("Your StyleMe PHP project now includes:")
    print("")
    print("🔧 BACKEND INTEGRATION:")
    print("   • Enhanced RAG search API (api/rag_search.php)")
    print("   • User preference storage (api/save_preferences.php)")
    print("   • Database tables for ML learning")
    print("")
    print("🎨 FRONTEND FEATURES:")
    print("   • Smart search interface with preference panel")
    print("   • Visual matching percentage display")
    print("   • Color-coded product recommendations")
    print("   • Real-time preference adjustment")
    print("")
    print("🧠 AI CAPABILITIES:")
    print("   • Semantic product search using RAG")
    print("   • Multi-factor matching algorithm")
    print("   • User behavior learning")
    print("   • Contextual style suggestions")
    print("")
    print("💰 COST-EFFECTIVE:")
    print("   • Local HuggingFace embeddings (FREE)")
    print("   • Fast Groq LLM inference (FREE tier)")
    print("   • No OpenAI API costs")
    print("")
    print("🚀 TO START USING:")
    print("   1. Import SQL: StyleMe/SQL/enhanced_rag_tables.sql")
    print("   2. Start RAG service: python app.py")
    print("   3. Open your products page")
    print("   4. Try searching: 'casual blue shirt for office'")
    print("")
    print("🎯 Users will now get intelligent recommendations")
    print("   with matching percentages for every search! 🎊")

if __name__ == "__main__":
    demo_enhanced_search()
