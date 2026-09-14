# AI Question Generator

From the Laravel project root, run `./start-dev.sh`. It starts Laravel, Vite, and this service together, and loads `GEMINI_API_KEY` from the root `.env`. If the key is not configured there or already exported, the launcher asks for it without echoing the value.

Run the service locally:

```bash
cd ai-service
source .venv/bin/activate
export GEMINI_QUESTION_API_KEY="your-question-generator-key"
export GEMINI_MODEL="gemini-3.5-flash-lite"
uvicorn main:app --reload --port 8001
```

When running Uvicorn manually, exporting the key is required because this is a separate Python process and it does not automatically read the Laravel root `.env` file.

The Laravel application calls `POST /generate` for a one-shot export or `POST /chat` for the teacher workspace. Both paths use the same Excel headers as the existing question importer. The chat endpoint accepts the source and conversation history on each request, so the Laravel app remains stateless and the selected source is never stored as a long-lived notebook file.
