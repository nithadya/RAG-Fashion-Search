# StyleMe RAG Service with LangChain Framework

## Overview

This is an upgraded RAG (Retrieval-Augmented Generation) microservice for the StyleMe e-commerce platform, built using the LangChain framework. The service provides intelligent product search capabilities by combining semantic search with large language models.

## Key Features

- **LangChain Framework**: Built using LangChain Expression Language (LCEL) for modular and maintainable RAG pipelines
- **FAISS Vector Store**: High-performance vector storage for fast similarity searches
- **OpenAI Integration**: Uses OpenAI's embedding and language models
- **User History Tracking**: Considers user search history for personalized recommendations
- **Performance Monitoring**: Built-in logging and analytics
- **RESTful API**: Clean HTTP API for integration with web applications

## Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   PHP Frontend  │───►│  RAG Service    │───►│  OpenAI API     │
│   (search.php)  │    │  (Flask/LC)     │    │  (Embeddings)   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                               │
                               ▼
                       ┌─────────────────┐    ┌─────────────────┐
                       │  FAISS Vector   │    │  MySQL Database │
                       │  Store (Local)  │    │  (User History) │
                       └─────────────────┘    └─────────────────┘
```

## Installation & Setup

### Prerequisites

- Python 3.8 or higher
- MySQL database with StyleMe schema
- OpenAI API key

### Step 1: Install Dependencies

```bash
# Navigate to the RAG service directory
cd rag_service

# Install Python packages
pip install -r requirements.txt
```

### Step 2: Configuration

1. Copy and configure the `.env` file:
   - Set your database credentials
   - Add your OpenAI API key
   - Adjust other settings as needed

### Step 3: Database Setup

Run the database upgrade script:

```sql
-- Execute the SQL file to add required tables
mysql -u your_username -p your_database < ../StyleMe/SQL/upgrade_for_langchain_rag.sql
```

### Step 4: Create Vector Store

This step creates the FAISS vector store from your product data:

```bash
python create_vector_store.py
```

This will:
- Connect to your MySQL database
- Fetch all active products
- Generate embeddings using OpenAI
- Create and save a FAISS vector store locally

### Step 5: Start the Service

```bash
python app.py
```

The service will start on `http://localhost:5000` by default.

## API Endpoints

### Health Check
```http
GET /
```
Returns service status and health information.

### Search Products
```http
POST /search
Content-Type: application/json

{
    "user_id": 1,
    "query": "red dress for party"
}
```

Response:
```json
{
    "success": true,
    "product_ids": [3, 10, 15],
    "query": "red dress for party",
    "results_count": 3,
    "processing_time": 0.245,
    "history_considered": true
}
```

### Vector Store Statistics
```http
GET /vector-store/stats
```
Returns information about the loaded vector store.

## Testing

Run the test suite to verify everything is working:

```bash
# Comprehensive tests
python test_rag_service.py

# Quick test
python test_rag_service.py quick
```

## Integration with PHP Frontend

The existing `search.php` file will work with this new service without any changes. The service maintains the same API contract as the previous version while providing enhanced functionality.

## Performance Features

- **Efficient Vector Search**: FAISS provides sub-millisecond search times
- **LangChain Optimization**: Streamlined pipeline reduces latency
- **Batch Processing**: Optimized for handling multiple concurrent requests
- **Caching**: Vector store is loaded once and reused across requests

## Monitoring & Analytics

The service automatically logs:
- Search queries and results
- Processing times
- User search patterns
- System performance metrics

This data is stored in the MySQL database for analysis and improvement.

## Configuration Options

Key environment variables in `.env`:

- `OPENAI_API_KEY`: Your OpenAI API key
- `EMBEDDING_MODEL`: OpenAI embedding model (default: text-embedding-ada-002)
- `LLM_MODEL`: OpenAI language model (default: gpt-3.5-turbo)
- `MAX_RETRIEVED_DOCS`: Number of documents to retrieve (default: 20)
- `TEMPERATURE`: LLM creativity (0.0 for deterministic, higher for creative)

## Troubleshooting

### Common Issues

1. **Vector store not found**
   - Run `python create_vector_store.py` to create it

2. **OpenAI API errors**
   - Verify your API key in `.env`
   - Check your OpenAI account has sufficient credits

3. **Database connection issues**
   - Verify MySQL is running
   - Check database credentials in `.env`

4. **Poor search results**
   - Recreate vector store after product updates
   - Adjust `MAX_RETRIEVED_DOCS` parameter
   - Fine-tune the prompt template in `app.py`

### Logs

The service provides detailed logging. Check the console output for debugging information.

## Upgrading from Previous Version

If you're upgrading from a previous RAG implementation:

1. The new service is fully backward-compatible
2. No changes needed to existing PHP code
3. Old database embeddings can be removed (handled automatically)
4. Performance should improve significantly

## Development

### Adding New Features

The LangChain framework makes it easy to add new features:

- Custom retrievers
- Multiple vector stores
- Advanced prompt engineering
- Multi-modal search capabilities

### Contributing

When contributing to this service:
1. Follow the existing code structure
2. Add appropriate error handling
3. Update tests for new features
4. Document configuration changes

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review the test output
3. Examine service logs
4. Verify configuration settings
