import google.generativeai as genai
import os
from dotenv import load_dotenv
from fastapi import FastAPI
from api.chatbot_api import router as chatbot_router

load_dotenv() # Carga las variables del archivo .env
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")

app = FastAPI()
app.include_router(chatbot_router)

# Si quieres mantener el código de prueba de modelos, puedes ponerlo bajo un if __name__ == "__main__":
if __name__ == "__main__":
    if not GEMINI_API_KEY:
        print("Error: GEMINI_API_KEY no está configurada en tu archivo .env")
    else:
        genai.configure(api_key=GEMINI_API_KEY)
        print("Modelos disponibles para generateContent:")
        for m in genai.list_models():
            if "generateContent" in m.supported_generation_methods:
                print(f"- {m.name}")
