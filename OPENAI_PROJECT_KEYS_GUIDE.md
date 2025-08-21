# OpenAI Project API Keys Configuration Guide

## 🔑 Understanding OpenAI API Key Types

OpenAI offers two types of API keys:

### 1. **User API Keys** (Legacy - `sk-...`)

- Format: `sk-1234567890abcdef...`
- Scoped to your entire organization
- Being phased out in favor of project keys

### 2. **Project API Keys** (Current - `sk-proj-...`)

- Format: `sk-proj-1234567890abcdef...`
- Scoped to specific projects for better security and billing
- **Recommended for new implementations**

## ⚙️ Configuration for Project Keys

If your API key starts with `sk-proj-`, you may need to specify the project ID:

### 1. **Find Your Project ID:**

- Go to: https://platform.openai.com/settings/organization/projects
- Copy your project ID (format: `proj_1234567890abcdef...`)

### 2. **Update Your `.env` File:**

```env
# Your project-based API key
OPENAI_API_KEY="sk-proj-your-actual-key-here"

# Your project ID (optional but recommended for sk-proj keys)
OPENAI_PROJECT_ID="proj_your-project-id-here"
```

### 3. **Test the Configuration:**

```bash
cd rag_service
python test_rag_service.py
```

## 🚀 Benefits of Project Keys

✅ **Better Security:** Scoped to specific projects  
✅ **Clearer Billing:** Track usage per project  
✅ **Team Management:** Control access at project level  
✅ **Rate Limits:** Separate limits per project

## 🔧 Troubleshooting

### **Common Issues:**

1. **"Invalid project" error:**

   - Ensure your `OPENAI_PROJECT_ID` matches your API key's project
   - Check the project ID format: should start with `proj_`

2. **"Authentication failed" error:**

   - Verify your API key is active and has the right permissions
   - Check if your project has sufficient credits

3. **"Model not available" error:**
   - Ensure your project has access to the models you're using
   - Check your organization's model availability

### **Quick Test:**

```bash
# Test if your key works without project ID
curl -H "Authorization: Bearer your-api-key" https://api.openai.com/v1/models

# Test with project ID
curl -H "Authorization: Bearer your-api-key" \
     -H "OpenAI-Project: your-project-id" \
     https://api.openai.com/v1/models
```

## 📝 Notes

- **Legacy keys (`sk-...`):** Will continue to work but are deprecated
- **Project ID:** Optional for most use cases, but recommended for sk-proj keys
- **Backward compatibility:** The system works with both key types seamlessly

---

💡 **Pro Tip:** The RAG service automatically handles both key types, so you can upgrade to project keys without changing your code!
