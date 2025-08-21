@echo off
REM Windows batch script to set up and start the StyleMe RAG service
REM Run this script from the Style-Me-RAG directory

echo ========================================
echo   StyleMe RAG Service Setup (Windows)
echo ========================================

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8 or higher
    pause
    exit /b 1
)

echo ✓ Python is installed

REM Navigate to RAG service directory
cd rag_service
if errorlevel 1 (
    echo ERROR: rag_service directory not found
    pause
    exit /b 1
)

echo ✓ Found RAG service directory

REM Check if virtual environment exists
if not exist "venv" (
    echo Creating Python virtual environment...
    python -m venv venv
    if errorlevel 1 (
        echo ERROR: Failed to create virtual environment
        pause
        exit /b 1
    )
)

echo ✓ Virtual environment ready

REM Activate virtual environment
call venv\Scripts\activate.bat
if errorlevel 1 (
    echo ERROR: Failed to activate virtual environment
    pause
    exit /b 1
)

echo ✓ Virtual environment activated

REM Install dependencies
echo Installing Python dependencies...
pip install -r requirements.txt
if errorlevel 1 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)

echo ✓ Dependencies installed

REM Check if .env file exists
if not exist ".env" (
    echo WARNING: .env file not found
    echo Please configure your .env file with:
    echo - Database credentials
    echo - OpenAI API key
    echo.
    echo Copy .env.example to .env and edit it
    pause
    exit /b 1
)

echo ✓ Configuration file found

REM Check if vector store exists
if not exist "faiss_index" (
    echo Vector store not found. Creating it now...
    python create_vector_store.py
    if errorlevel 1 (
        echo ERROR: Failed to create vector store
        echo Please check your configuration and try again
        pause
        exit /b 1
    )
)

echo ✓ Vector store ready

REM Run a quick test
echo Running quick test...
python test_rag_service.py quick
if errorlevel 1 (
    echo WARNING: Quick test failed
    echo The service might still work, but please check the configuration
)

echo.
echo ========================================
echo   Setup Complete!
echo ========================================
echo.
echo To start the RAG service manually:
echo   1. cd rag_service
echo   2. venv\Scripts\activate.bat
echo   3. python app.py
echo.
echo Or run: start_rag_service.bat
echo.
pause
