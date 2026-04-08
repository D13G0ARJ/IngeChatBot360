from __future__ import annotations

import json
from http.server import BaseHTTPRequestHandler


class handler(BaseHTTPRequestHandler):
    def _send_json(self, payload: dict, status: int = 200) -> None:
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self) -> None:
        self._send_json(
            {
                "name": "IngeChatBot360 API",
                "status": "ok",
                "endpoints": {
                    "chat": {
                        "method": "POST",
                        "path": "/api/chat",
                        "body": {"message": "Hola"},
                    }
                },
            }
        )
