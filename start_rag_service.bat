@echo off
REM Start the StyleMe RAG service
echo Starting StyleMe RAG Service...

REM Navigate to RAG service directory
cd rag_service
if errorlevel 1 (
    echo ERROR: rag_service directory not found
    echo Please run setup_rag_service.bat first
    pause
    exit /b 1
)

REM Activate virtual environment
if exist "venv\Scripts\activate.bat" (
    call venv\Scripts\activate.bat
) else (
    echo ERROR: Virtual environment not found
    echo Please run setup_rag_service.bat first
    pause
    exit /b 1
)

REM Check if vector store exists
if not exist "faiss_index" (
    echo WARNING: Vector store not found
    echo Creating vector store first...
    python create_vector_store.py
    if errorlevel 1 (
        echo ERROR: Failed to create vector store
        pause
        exit /b 1
    )
)

REM Start the service
echo.
echo ========================================
echo   Starting RAG Service on port 5000
echo ========================================
echo.
echo Press Ctrl+C to stop the service
echo.

python app.py
