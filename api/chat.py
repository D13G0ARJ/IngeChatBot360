from __future__ import annotations

import json
from http.server import BaseHTTPRequestHandler

from src.core.chatbot_logic import ChatbotLogic
from src.core.data_manager import DataManager


_DATA_MANAGER = DataManager()
_LOGIC = ChatbotLogic(data_manager=_DATA_MANAGER)


class handler(BaseHTTPRequestHandler):
    def _send_json(self, payload: dict, status: int = 200) -> None:
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        # CORS headers for browser-based clients
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_POST(self) -> None:
        try:
            content_length = int(self.headers.get("content-length", "0"))
        except ValueError:
            content_length = 0

        raw_body = self.rfile.read(content_length) if content_length else b"{}"

        try:
            payload = json.loads(raw_body.decode("utf-8"))
        except Exception:
            self._send_json({"error": "Invalid JSON body"}, status=400)
            return

        message = (payload.get("message") or "").strip()
        if not message:
            self._send_json({"error": "Missing 'message'"}, status=400)
            return

        history = payload.get("history")
        if history is None:
            history = []
        if not isinstance(history, list):
            self._send_json({"error": "'history' must be a list"}, status=400)
            return

        try:
            reply = _LOGIC.process_message(message, history=history)
        except ValueError as e:
            # e.g. missing GEMINI_API_KEY
            self._send_json({"error": str(e)}, status=500)
            return
        except Exception:
            self._send_json({"error": "Internal error"}, status=500)
            return

        self._send_json({"reply": reply}, status=200)

    def do_GET(self) -> None:
        self._send_json(
            {
                "hint": "Use POST with JSON body: { 'message': '...' }",
                "example": {
                    "curl": "curl -X POST https://<tu-app>.vercel.app/api/chat -H \"Content-Type: application/json\" -d \"{\\\"message\\\":\\\"Hola\\\"}\"",
                },
            }
        )

    def do_OPTIONS(self) -> None:
        # Respond to CORS preflight
        self.send_response(200)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()
