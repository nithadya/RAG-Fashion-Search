#!/bin/bash
# Linux/Mac setup script for StyleMe RAG service

echo "========================================"
echo "  StyleMe RAG Service Setup (Linux/Mac)"
echo "========================================"

# Check if Python is installed
if ! command -v python3 &> /dev/null; then
    echo "ERROR: Python3 is not installed"
    echo "Please install Python 3.8 or higher"
    exit 1
fi

echo "✓ Python3 is installed"

# Navigate to RAG service directory
cd rag_service || {
    echo "ERROR: rag_service directory not found"
    exit 1
}

echo "✓ Found RAG service directory"

# Create virtual environment if it doesn't exist
if [ ! -d "venv" ]; then
    echo "Creating Python virtual environment..."
    python3 -m venv venv || {
        echo "ERROR: Failed to create virtual environment"
        exit 1
    }
fi

echo "✓ Virtual environment ready"

# Activate virtual environment
source venv/bin/activate || {
    echo "ERROR: Failed to activate virtual environment"
    exit 1
}

echo "✓ Virtual environment activated"

# Upgrade pip
pip install --upgrade pip

# Install dependencies
echo "Installing Python dependencies..."
pip install -r requirements.txt || {
    echo "ERROR: Failed to install dependencies"
    exit 1
}

echo "✓ Dependencies installed"

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "WARNING: .env file not found"
    echo "Please configure your .env file with:"
    echo "- Database credentials"
    echo "- OpenAI API key"
    echo ""
    echo "Copy .env.example to .env and edit it"
    exit 1
fi

echo "✓ Configuration file found"

# Check if vector store exists
if [ ! -d "faiss_index" ]; then
    echo "Vector store not found. Creating it now..."
    python create_vector_store.py || {
        echo "ERROR: Failed to create vector store"
        echo "Please check your configuration and try again"
        exit 1
    }
fi

echo "✓ Vector store ready"

# Run a quick test
echo "Running quick test..."
python test_rag_service.py quick || {
    echo "WARNING: Quick test failed"
    echo "The service might still work, but please check the configuration"
}

echo ""
echo "========================================"
echo "  Setup Complete!"
echo "========================================"
echo ""
echo "To start the RAG service manually:"
echo "  1. cd rag_service"
echo "  2. source venv/bin/activate"
echo "  3. python app.py"
echo ""
echo "Or run: ./start_rag_service.sh"
echo ""
