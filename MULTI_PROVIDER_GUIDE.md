# Multi-Provider Configuration Guide

## 🤖 StyleMe RAG Service - OpenAI & Groq Support

Your StyleMe RAG service now supports both **OpenAI** and **Groq** as LLM providers, giving you flexibility in terms of cost, performance, and reliability.

## 🚀 Quick Start

### Option 1: OpenAI Only (Recommended for Production)

```bash
# In rag_service/.env
LLM_PROVIDER=openai
OPENAI_API_KEY="your-openai-api-key"
# Leave GROQ_API_KEY empty or unset
```

### Option 2: Groq Only (Fast & Cost-Effective)

```bash
# In rag_service/.env
LLM_PROVIDER=groq
OPENAI_API_KEY="your-openai-api-key"  # Still needed for embeddings
GROQ_API_KEY="your-groq-api-key"
```

### Option 3: Both Providers (Maximum Flexibility)

```bash
# In rag_service/.env
LLM_PROVIDER=groq  # Primary choice
OPENAI_API_KEY="your-openai-api-key"
GROQ_API_KEY="your-groq-api-key"
```

## 🔄 Automatic Fallback

The system intelligently chooses providers:

1. **Uses your preferred provider** (set by `LLM_PROVIDER`)
2. **Automatically falls back** if the primary provider is unavailable
3. **Validates API keys** at startup
4. **Shows provider status** in health checks

## 📊 Provider Comparison

| Feature              | OpenAI     | Groq       |
| -------------------- | ---------- | ---------- |
| **Response Quality** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Speed**            | ⭐⭐⭐     | ⭐⭐⭐⭐⭐ |
| **Cost**             | ⭐⭐       | ⭐⭐⭐⭐⭐ |
| **Reliability**      | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐   |
| **Rate Limits**      | Moderate   | High       |

### 🎯 When to Use Each Provider

**Use OpenAI when:**

- Quality is paramount
- You need maximum reliability
- Cost is not a primary concern
- You're in production

**Use Groq when:**

- Speed is critical (5-10x faster)
- You have high volume requirements
- Cost optimization is important
- You're in development/testing

## 🔧 Configuration Details

### Environment Variables

```bash
# === Provider Selection ===
LLM_PROVIDER=openai              # or "groq"

# === API Keys ===
OPENAI_API_KEY="sk-..."          # Required for embeddings + OpenAI LLM
GROQ_API_KEY="gsk_..."          # Required only for Groq LLM

# === Model Selection ===
OPENAI_LLM_MODEL=gpt-3.5-turbo        # or gpt-4, gpt-4-turbo
GROQ_LLM_MODEL=llama-3.1-8b-instant   # or llama-3.1-70b-versatile

# === Embeddings (Always OpenAI for now) ===
OPENAI_EMBEDDING_MODEL=text-embedding-ada-002
```

### 🔑 OpenAI API Key Types

OpenAI offers two types of API keys:

**1. Legacy Keys (`sk-...`)**

- Format: `sk-1234567890abcdef...`
- Scoped to your entire organization
- Still supported but being phased out

**2. Project Keys (`sk-proj-...`) - Recommended**

- Format: `sk-proj-1234567890abcdef...`
- Scoped to specific projects for better security
- Better billing tracking and access control

### OpenAI Project API Keys

OpenAI offers two key types; the RAG system supports both but project keys are recommended.

#### Key Types

- **Legacy (user) keys**: `sk-...` — organization-scoped, older format.
- **Project keys**: `sk-proj-...` — scoped to a specific project, recommended for security and billing.

If your API key starts with `sk-proj-`, you may optionally set a project ID to ensure requests are routed correctly and billed to the right project.

#### Configuration (add to `rag_service/.env`)

```bash
# Your project-based API key
OPENAI_API_KEY="sk-proj-your-actual-key-here"

# Optional: Your project ID (recommended for sk-proj keys)
OPENAI_PROJECT_ID="proj_your-project-id-here"
```

#### How to find your Project ID

1. Visit: https://platform.openai.com/settings/organization/projects
2. Copy the project ID (format: `proj_1234567890abcdef...`)

#### Benefits of using project keys

- ✅ Better security and fine-grained access control
- ✅ Clearer billing and usage tracking by project
- ✅ Separate rate limits and quotas per project
- ✅ Recommended for new implementations and team workflows

#### Quick tests & troubleshooting

- Test without project header:

```bash
curl -H "Authorization: Bearer your-api-key" https://api.openai.com/v1/models
```

- Test with project header:

```bash
curl -H "Authorization: Bearer your-api-key" \
  -H "OpenAI-Project: your-project-id" \
  https://api.openai.com/v1/models
```

Common issues:

- "Invalid project": verify `OPENAI_PROJECT_ID` matches the project for that key and begins with `proj_`
- "Authentication failed": confirm key is active and has permissions
- "Model not available": ensure the project has model access enabled

Note: legacy `sk-` keys continue to work for now, but project keys are the recommended path forward.

### Available Groq Models

| Model                     | Speed      | Quality    | Use Case                    |
| ------------------------- | ---------- | ---------- | --------------------------- |
| `llama-3.1-8b-instant`    | ⚡⚡⚡⚡⚡ | ⭐⭐⭐⭐   | Development, high-volume    |
| `llama-3.1-70b-versatile` | ⚡⚡⚡     | ⭐⭐⭐⭐⭐ | Production, complex queries |
| `mixtral-8x7b-32768`      | ⚡⚡⚡⚡   | ⭐⭐⭐⭐   | Long context, versatile     |

## 🧪 Testing Both Providers

```bash
# Test current setup
python test_rag_service.py quick

# Check provider status
curl http://localhost:5000/providers

# Health check with provider info
curl http://localhost:5000/
```

## 🔍 Monitoring & Debugging

### Check Provider Status

```bash
# In your RAG service logs, look for:
🤖 Using LLM provider: OPENAI
# or
🤖 Using LLM provider: GROQ
```

### Health Check Response

```json
{
  "status": "healthy",
  "provider_info": {
    "current_provider": "groq",
    "openai_available": true,
    "groq_available": true,
    "groq_sdk_installed": true
  }
}
```

### Search Response (includes provider info)

```json
{
  "success": true,
  "product_ids": [1, 5, 12],
  "provider_used": "groq",
  "service_version": "2.1.0-multi-provider"
}
```

## 🛠️ Troubleshooting

### Issue: Groq not available

```
⚠️ Groq requested but not available, falling back to OpenAI
```

**Solution:** Install Groq dependencies:

```bash
pip install langchain-groq groq
```

### Issue: No valid API key found

```
❌ No valid API key found for any provider
```

**Solution:** Set at least one API key:

```bash
# For OpenAI
export OPENAI_API_KEY="sk-..."
# For Groq
export GROQ_API_KEY="gsk_..."
```

### Issue: Provider switching

```
⚠️ OpenAI key not found, switching to Groq
```

**This is normal** - the system automatically chooses the best available provider.

## 💰 Cost Optimization Tips

1. **Use Groq for development** - Much cheaper for testing
2. **Use OpenAI for production** - Higher quality, more reliable
3. **Set rate limits** appropriately for your usage
4. **Monitor usage** through provider dashboards
5. **Use smaller models** when quality allows (Groq 8B vs 70B)

## 🔐 Security Best Practices

1. **Never commit API keys** - Use .env files
2. **Use environment variables** in production
3. **Rotate keys regularly**
4. **Monitor API usage** for unusual activity
5. **Set up billing alerts** on both platforms

## 🚀 Performance Optimization

### For Speed (Groq):

```bash
LLM_PROVIDER=groq
GROQ_LLM_MODEL=llama-3.1-8b-instant
TEMPERATURE=0
MAX_TOKENS=100
```

### For Quality (OpenAI):

```bash
LLM_PROVIDER=openai
OPENAI_LLM_MODEL=gpt-4
TEMPERATURE=0.1
MAX_TOKENS=200
```

### For Balance:

```bash
LLM_PROVIDER=groq
GROQ_LLM_MODEL=llama-3.1-70b-versatile
TEMPERATURE=0
MAX_TOKENS=150
```

## 📈 Production Deployment

1. **Start with OpenAI** for reliability
2. **Add Groq as fallback** for cost optimization
3. **Monitor performance** and costs
4. **Switch providers** based on load/requirements
5. **Use load balancing** if needed

## 🎯 Next Steps

1. **Get your API keys**:

   - OpenAI: https://platform.openai.com/api-keys
   - Groq: https://console.groq.com/keys

2. **Update your .env** file with both keys

3. **Test the service**:

   ```bash
   python test_rag_service.py
   ```

4. **Monitor performance** and adjust as needed

Your RAG service is now ready for maximum flexibility and performance! 🚀
