#!/bin/bash
# Start the StyleMe RAG service

echo "Starting StyleMe RAG Service..."

# Navigate to RAG service directory
cd rag_service || {
    echo "ERROR: rag_service directory not found"
    echo "Please run setup_rag_service.sh first"
    exit 1
}

# Activate virtual environment
if [ -f "venv/bin/activate" ]; then
    source venv/bin/activate
else
    echo "ERROR: Virtual environment not found"
    echo "Please run setup_rag_service.sh first"
    exit 1
fi

# Check if vector store exists
if [ ! -d "faiss_index" ]; then
    echo "WARNING: Vector store not found"
    echo "Creating vector store first..."
    python create_vector_store.py || {
        echo "ERROR: Failed to create vector store"
        exit 1
    }
fi

# Start the service
echo ""
echo "========================================"
echo "  Starting RAG Service on port 5000"
echo "========================================"
echo ""
echo "Press Ctrl+C to stop the service"
echo ""

python app.py
