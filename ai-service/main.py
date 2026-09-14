import base64
import io
import json
import os
import re
from typing import Annotated

import httpx
from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.responses import StreamingResponse
from openpyxl import Workbook
from pypdf import PdfReader
from youtube_transcript_api import YouTubeTranscriptApi

app = FastAPI(title="AI Question Generator")

MAX_SOURCE_FILES = 5
MAX_YOUTUBE_URLS = 5
MAX_SOURCE_BYTES = 10 * 1024 * 1024
MAX_SOURCE_TEXT_CHARS = 200000
ALLOWED_IMAGE_TYPES = {"image/jpeg", "image/png", "image/webp"}
EXCEL_HEADERS = [
    "question",
    "type",
    "option_a",
    "option_b",
    "option_c",
    "option_d",
    "correct_answers",
    "marks",
    "explanation",
]


def youtube_video_id(url: str) -> str | None:
    match = re.search(r"(?:v=|youtu\.be/|youtube\.com/(?:shorts|embed)/)([\w-]{11})", url)
    return match.group(1) if match else None


def read_pdf(data: bytes) -> str:
    try:
        reader = PdfReader(io.BytesIO(data))
        return "\n".join(page.extract_text() or "" for page in reader.pages).strip()
    except Exception as exc:
        raise HTTPException(status_code=422, detail=f"Could not read PDF: {exc}") from exc


def read_transcript(url: str) -> str:
    video_id = youtube_video_id(url)
    if not video_id:
        raise HTTPException(status_code=422, detail="Please provide a valid YouTube URL.")
    try:
        transcript = YouTubeTranscriptApi().fetch(video_id, languages=["bn", "en"])
        return " ".join(item.text for item in transcript)
    except Exception as exc:
        raise HTTPException(status_code=422, detail=f"YouTube transcript unavailable: {exc}") from exc


def validate_source_counts(sources: list[UploadFile], youtube_urls: list[str]) -> None:
    if len(sources) > MAX_SOURCE_FILES:
        raise HTTPException(status_code=422, detail="You can upload up to 5 PDF/image files.")
    if len(youtube_urls) > MAX_YOUTUBE_URLS:
        raise HTTPException(status_code=422, detail="You can provide up to 5 YouTube URLs.")


def normalize_youtube_urls(value: list[str] | str | None) -> list[str]:
    if isinstance(value, str):
        try:
            value = json.loads(value)
        except json.JSONDecodeError:
            value = [value]
    return [url.strip() for url in (value or []) if isinstance(url, str) and url.strip()]


async def read_sources(
    sources: list[UploadFile], youtube_urls: list[str]
) -> tuple[list[str], list[dict]]:
    validate_source_counts(sources, youtube_urls)
    parts: list[str] = []
    image_parts: list[dict] = []
    for source in sources:
        data = await source.read()
        if len(data) > MAX_SOURCE_BYTES:
            raise HTTPException(status_code=413, detail=f"Each source file must be 10 MB or smaller: {source.filename}")
        if source.content_type == "application/pdf":
            parts.append(read_pdf(data))
        elif source.content_type in ALLOWED_IMAGE_TYPES:
            image_parts.append({
                "inline_data": {"mime_type": source.content_type, "data": base64.b64encode(data).decode()}
            })
        else:
            raise HTTPException(status_code=415, detail="Only PDF, JPG, PNG, and WEBP files are supported.")
    for youtube_url in youtube_urls:
        parts.append(read_transcript(youtube_url))
    return ["\n".join(parts)[:MAX_SOURCE_TEXT_CHARS]], image_parts


def parse_ai_json(text: str) -> list[dict]:
    cleaned = text.strip().removeprefix("```json").removesuffix("```").strip()
    try:
        payload = json.loads(cleaned)
    except json.JSONDecodeError as exc:
        raise HTTPException(status_code=502, detail="Gemini returned invalid JSON.") from exc
    questions = payload.get("questions") if isinstance(payload, dict) else payload
    if not isinstance(questions, list) or not questions:
        raise HTTPException(status_code=502, detail="Gemini returned no questions.")
    for question in questions:
        if not isinstance(question, dict) or not question.get("question"):
            raise HTTPException(status_code=502, detail="Gemini returned an invalid question.")
        options = question.get("options", [])
        if question.get("type", "single") not in {"single", "multiple"} or len(options) < 2:
            raise HTTPException(status_code=502, detail="Gemini returned an invalid MCQ.")
        if not question.get("correct_answers"):
            raise HTTPException(status_code=502, detail="Gemini returned a question without an answer.")
    return questions


def generate_xlsx(questions: list[dict]) -> io.BytesIO:
    workbook = Workbook()
    sheet = workbook.active
    sheet.title = "Questions Template"
    sheet.append(EXCEL_HEADERS)
    for question in questions:
        options = question.get("options", [])[:6]
        option_values = options + [""] * (6 - len(options))
        answers = question.get("correct_answers", [])
        if isinstance(answers, str):
            answers = re.split(r"[,;\s]+", answers.strip())
        answer_letters = []
        for answer in answers:
            value = str(answer).strip().upper()
            if value.isdigit():
                value = chr(64 + int(value))
            if value in {chr(65 + index) for index in range(len(options))}:
                answer_letters.append(value)
        if not answer_letters:
            raise HTTPException(status_code=502, detail="Gemini returned an invalid correct answer.")
        sheet.append([
            str(question["question"]).strip(),
            question.get("type", "single"),
            *option_values[:4],
            ",".join(answer_letters),
            float(question.get("marks", 1)),
            str(question.get("explanation", "")).strip(),
        ])
    for column in sheet.columns:
        sheet.column_dimensions[column[0].column_letter].width = min(
            max(len(str(cell.value or "")) for cell in column) + 2, 60
        )
    output = io.BytesIO()
    workbook.save(output)
    output.seek(0)
    return output


async def ask_gemini(prompt: str, parts: list[str], image_parts: list[dict], history: list[dict]) -> list[dict]:
    api_key = os.getenv("GEMINI_QUESTION_API_KEY")
    model = os.getenv("GEMINI_MODEL", "gemini-3.5-flash-lite")
    if not api_key:
        raise HTTPException(status_code=503, detail="Gemini API is not configured.")

    schema = json.dumps({"questions": [{
        "question": "string", "type": "single or multiple", "options": ["string"],
        "correct_answers": ["A"], "marks": 1, "explanation": "string",
    }]})
    instruction = (
        "You are an exam question author inside a teacher chat workspace. "
        "Use only the supplied source. Return JSON only, matching this schema: " + schema + " "
        "Create or revise questions according to the latest teacher instruction. "
        "Use 4 options unless the teacher says otherwise. correct_answers uses option letters."
    )
    chat_parts = [{"text": instruction}]
    for item in history[-10:]:
        chat_parts.append({"text": f"{item.get('role', 'user')}: {item.get('text', '')}"})
    chat_parts.append({"text": "Teacher's latest instruction:\n" + prompt + "\nSource text:\n" + "\n".join(parts)})
    chat_parts.extend(image_parts)
    endpoint = f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent"
    try:
        async with httpx.AsyncClient(timeout=120) as client:
            response = await client.post(endpoint, params={"key": api_key}, json={"contents": [{"parts": chat_parts}]})
            response.raise_for_status()
            response_data = response.json()
    except httpx.HTTPStatusError as exc:
        provider_message = exc.response.text[:500].replace("\n", " ").strip()
        raise HTTPException(
            status_code=502,
            detail=f"Gemini request failed with HTTP {exc.response.status_code} for model '{model}'. "
            f"Provider response: {provider_message}",
        ) from exc
    except httpx.RequestError as exc:
        raise HTTPException(status_code=502, detail=f"Gemini request failed: {exc}") from exc
    try:
        text = response_data["candidates"][0]["content"]["parts"][0]["text"]
    except (KeyError, IndexError, TypeError) as exc:
        raise HTTPException(status_code=502, detail="Gemini returned an empty response.") from exc
    return parse_ai_json(text)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/generate")
async def generate(
    prompt: Annotated[str, Form(...)],
    youtube_urls: Annotated[list[str] | str | None, Form()] = None,
    sources: Annotated[list[UploadFile] | None, File()] = None,
):
    youtube_urls = normalize_youtube_urls(youtube_urls)
    sources = sources or []
    if not prompt.strip():
        raise HTTPException(status_code=422, detail="Prompt is required.")
    if not sources and not youtube_urls:
        raise HTTPException(status_code=422, detail="Upload a PDF/image or provide a YouTube URL.")

    parts, image_parts = await read_sources(sources, youtube_urls)

    questions = await ask_gemini(prompt, parts, image_parts, [])
    workbook = generate_xlsx(questions)
    return StreamingResponse(
        workbook,
        media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        headers={"Content-Disposition": "attachment; filename=ai-generated-questions.xlsx"},
    )


@app.post("/chat")
async def chat(
    prompt: Annotated[str, Form(...)],
    history: Annotated[str, Form()] = "[]",
    youtube_urls: Annotated[list[str] | str | None, Form()] = None,
    sources: Annotated[list[UploadFile] | None, File()] = None,
):
    youtube_urls = normalize_youtube_urls(youtube_urls)
    sources = sources or []
    if not prompt.strip():
        raise HTTPException(status_code=422, detail="Message is required.")
    parts, image_parts = await read_sources(sources, youtube_urls)
    try:
        parsed_history = json.loads(history)
    except json.JSONDecodeError as exc:
        raise HTTPException(status_code=422, detail="Chat history is invalid.") from exc
    questions = await ask_gemini(prompt, parts, image_parts, parsed_history if isinstance(parsed_history, list) else [])
    workbook = generate_xlsx(questions)
    return {
        "message": f"আমি {len(questions)}টি প্রশ্ন তৈরি করেছি। প্রয়োজন হলে আরও পরিবর্তনের নির্দেশ দিন।",
        "questions": questions,
        "xlsx_base64": base64.b64encode(workbook.read()).decode(),
    }