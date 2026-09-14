#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AI_DIR="$ROOT_DIR/ai-service"
AI_PYTHON="$AI_DIR/.venv/bin/python"

cleanup() {
    trap - INT TERM EXIT
    [[ -n "${AI_PID:-}" ]] && kill "$AI_PID" 2>/dev/null || true
    [[ -n "${PHP_PID:-}" ]] && kill "$PHP_PID" 2>/dev/null || true
    [[ -n "${VITE_PID:-}" ]] && kill "$VITE_PID" 2>/dev/null || true
}

trap cleanup INT TERM EXIT

cd "$ROOT_DIR"

if [[ -f "$ROOT_DIR/.env" ]]; then
    set -a
    # Load shared Laravel credentials into the AI service process too.
    source "$ROOT_DIR/.env"
    set +a
fi

if [[ ! -f "$ROOT_DIR/vendor/autoload.php" ]]; then
    echo "Installing PHP dependencies..."
    composer install --no-interaction
fi

if [[ ! -d "$ROOT_DIR/node_modules" ]]; then
    echo "Installing frontend dependencies..."
    npm install
fi

if [[ ! -x "$AI_PYTHON" ]]; then
    echo "Creating Python virtual environment..."
    python3 -m venv "$AI_DIR/.venv"
fi

if ! "$AI_PYTHON" -c 'import fastapi, httpx, openpyxl, pypdf, youtube_transcript_api' >/dev/null 2>&1; then
    echo "Installing AI service dependencies..."
    "$AI_PYTHON" -m pip install -r "$AI_DIR/requirements.txt"
fi

if [[ -z "${GEMINI_QUESTION_API_KEY:-}" ]]; then
    printf "Enter your question-generator Gemini API key (hidden): "
    read -r -s GEMINI_QUESTION_API_KEY
    printf "\n"
    export GEMINI_QUESTION_API_KEY
fi

export GEMINI_MODEL="${GEMINI_MODEL:-gemini-3.5-flash-lite}"

php artisan optimize:clear >/dev/null
php artisan migrate --graceful --force >/dev/null

echo "Starting AI service at http://127.0.0.1:8001"
(cd "$AI_DIR" && exec "$AI_PYTHON" -m uvicorn main:app --host 127.0.0.1 --port 8001) &
AI_PID=$!

echo "Starting Laravel at http://127.0.0.1:8000"
php artisan serve --host=127.0.0.1 --port=8000 &
PHP_PID=$!

echo "Starting Vite development server..."
npm run dev &
VITE_PID=$!

echo "Project is running. Open http://127.0.0.1:8000"
echo "Press Ctrl+C to stop all services."

wait