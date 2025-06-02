from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from src.core.chatbot_logic import ChatbotLogic

router = APIRouter()
chatbot = ChatbotLogic()

class ChatRequest(BaseModel):
    message: str

class ChatResponse(BaseModel):
    response: str

@router.post("/chat", response_model=ChatResponse)
def chat_endpoint(request: ChatRequest):
    try:
        answer = chatbot.process_message(request.message)
        return ChatResponse(response=answer)
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
