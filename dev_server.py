"""dev_server.py

Simple development HTTP server that routes /api/* to the Python handlers
located in the `api/` folder. Run this locally during frontend development so
the Next.js UI can call http://localhost:8000/api/chat.

Usage:
    python dev_server.py

"""
import json
from http.server import ThreadingHTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse
import sys

try:
    from api.chat import handler as ChatHandler
    from api.index import handler as IndexHandler
except Exception as e:
    print("Error importing API handlers:", e)
    sys.exit(1)


class RouterHandler(BaseHTTPRequestHandler):
    def _send_json(self, payload: dict, status: int = 200) -> None:
        """Utility to send JSON responses with CORS headers (compatible with api handlers)."""
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path == "/api":
            IndexHandler.do_GET(self)
        elif parsed.path.startswith("/api/chat"):
            ChatHandler.do_GET(self)
        else:
            self.send_response(404)
            self.send_header("Content-Type", "text/plain; charset=utf-8")
            self.end_headers()
            self.wfile.write(b"Not found")

    def do_POST(self):
        parsed = urlparse(self.path)
        if parsed.path.startswith("/api/chat"):
            ChatHandler.do_POST(self)
        else:
            self.send_response(404)
            self.send_header("Content-Type", "text/plain; charset=utf-8")
            self.end_headers()
            self.wfile.write(b"Not found")

    def do_OPTIONS(self):
        # Generic CORS preflight response for local dev
        self.send_response(200)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()


def run(addr="0.0.0.0", port=8000):
    server = ThreadingHTTPServer((addr, port), RouterHandler)
    print(f"Dev API server listening on http://{addr}:{port}")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("Shutting down")
        server.server_close()


if __name__ == "__main__":
    run()
