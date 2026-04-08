'use client';

import { useEffect, useMemo, useRef, useState } from 'react';

const WELCOME_MESSAGE =
  '¡Hola! Soy IngeChat 360°, tu asistente virtual de la UNEFA Núcleo Miranda, Sede Los Teques. ' +
  'Estoy aquí para brindarte información detallada sobre las carreras de Ingeniería: ' +
  'Sistemas, Mecánica, Telecomunicaciones y Eléctrica.\n\n' +
  '¿En qué carrera estás interesado hoy? O puedes preguntar sobre requisitos de inscripción, perfil del egresado, etc.';

const INITIAL_SUGGESTIONS = [
  'Ingeniería de Sistemas',
  'Ingeniería Mecánica',
  'Ingeniería Eléctrica',
  'Ingeniería de Telecomunicaciones',
  'Requisitos de Inscripción',
];

function getSuggestionsFromBotText(botText) {
  if (!botText) return [];

  if (botText.includes('¡Hola! Soy IngeChat 360°')) {
    return INITIAL_SUGGESTIONS;
  }

  if (botText.includes('Ingeniería de Sistemas')) {
    return [
      'Pensum de Sistemas',
      'Perfil del Egresado de Sistemas',
      'Salidas Profesionales de Sistemas',
      'Duración de Sistemas',
    ];
  }

  if (botText.includes('Ingeniería Mecánica')) {
    return ['Pensum de Mecánica', 'Perfil del Egresado de Mecánica', 'Duración de Mecánica'];
  }

  if (botText.includes('Ingeniería Eléctrica')) {
    return ['Pensum de Eléctrica', 'Perfil del Egresado de Eléctrica', 'Duración de Eléctrica'];
  }

  if (botText.includes('Ingeniería de Telecomunicaciones')) {
    return [
      'Pensum de Telecomunicaciones',
      'Perfil del Egresado de Telecomunicaciones',
      'Duración de Telecomunicaciones',
    ];
  }

  return [];
}

const API_BASE = typeof process !== 'undefined' && process.env.NEXT_PUBLIC_API_URL ? process.env.NEXT_PUBLIC_API_URL : '';

export default function Page() {
  const [theme, setTheme] = useState('light');
  const [messages, setMessages] = useState(() => [
    { role: 'assistant', content: WELCOME_MESSAGE, ts: Date.now() }
  ]);
  const [input, setInput] = useState('');
  const [isTyping, setIsTyping] = useState(false);
  const [error, setError] = useState('');

  const chatRef = useRef(null);

  useEffect(() => {
    document.documentElement.dataset.theme = theme;
  }, [theme]);

  useEffect(() => {
    // scroll to bottom on new messages
    const el = chatRef.current;
    if (!el) return;
    el.scrollTop = el.scrollHeight;
  }, [messages, isTyping]);

  const lastBotMessage = useMemo(() => {
    for (let i = messages.length - 1; i >= 0; i--) {
      if (messages[i].role === 'assistant') return messages[i].content;
    }
    return '';
  }, [messages]);

  const suggestions = useMemo(() => {
    const s = getSuggestionsFromBotText(lastBotMessage);
    // Si no hay match, mostrar sugerencias iniciales cuando hay poco contexto
    if (!s.length && messages.length <= 2) return INITIAL_SUGGESTIONS;
    return s;
  }, [lastBotMessage, messages.length]);

  function resetChat() {
    setError('');
    setInput('');
    setIsTyping(false);
    setMessages([{ role: 'assistant', content: WELCOME_MESSAGE, ts: Date.now() }]);
  }

  async function sendMessage(text) {
    const message = (text ?? input).trim();
    if (!message || isTyping) return;

    setError('');
    setInput('');
    const now = Date.now();

    const nextMessages = [...messages, { role: 'user', content: message, ts: now }];
    setMessages(nextMessages);
    setIsTyping(true);

    // Build history in a simple, explicit format
    const history = nextMessages
      .filter((m) => m.role === 'user' || m.role === 'assistant')
      .map((m) => ({ role: m.role, content: m.content }));

    try {
      const res = await fetch(`${API_BASE || ''}/api/chat`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, history }),
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(data?.error || 'Error llamando /api/chat');
      }

      const reply = String(data?.reply ?? '');
      setMessages((prev) => [...prev, { role: 'assistant', content: reply, ts: Date.now() }]);
    } catch (e) {
      setError(e?.message || 'Error inesperado');
    } finally {
      setIsTyping(false);
    }
  }

  function onKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  }

  return (
    <div className="container">
      <div className="header">
        <div className="headerTitle">
          <strong>IngeChat 360°</strong>
          <span>UNEFA Núcleo Miranda · Sede Los Teques</span>
        </div>
        <div className="headerActions">
          <button className="button" onClick={resetChat} disabled={isTyping}>
            Reiniciar chat
          </button>
          <button
            className="button"
            onClick={() => setTheme((t) => (t === 'light' ? 'dark' : 'light'))}
            disabled={isTyping}
            aria-label="Cambiar tema"
          >
            {theme === 'light' ? 'Modo oscuro' : 'Modo claro'}
          </button>
        </div>
      </div>

      <div className="panel">
        <div className="chat" ref={chatRef}>
          {messages.map((m, idx) => (
            <div key={idx} className={`row ${m.role === 'user' ? 'user' : 'bot'}`}>
              <div>
                <div className={`bubble ${m.role === 'user' ? 'user' : 'bot'}`}>{m.content}</div>
                <div className="meta">{m.role === 'user' ? 'Tú' : 'IngeChat 360°'}</div>
              </div>
            </div>
          ))}

          {isTyping ? (
            <div className="row bot">
              <div>
                <div className="bubble bot">
                  IngeChat 360° está escribiendo{' '}
                  <span className="typingDots" aria-hidden="true">
                    <span className="dot" />
                    <span className="dot" />
                    <span className="dot" />
                  </span>
                </div>
                <div className="meta">IngeChat 360°</div>
              </div>
            </div>
          ) : null}
        </div>

        {suggestions?.length ? (
          <div className="quickReplies" role="group" aria-label="Respuestas rápidas">
            {suggestions.map((s) => (
              <button key={s} className="chip" onClick={() => sendMessage(s)} disabled={isTyping}>
                {s}
              </button>
            ))}
          </div>
        ) : null}

        {error ? <div className="error">{error}</div> : null}

        <div className="composer">
          <input
            className="input"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={onKeyDown}
            placeholder="Escribe tu mensaje..."
            disabled={isTyping}
          />
          <button className="send" onClick={() => sendMessage()} disabled={isTyping || !input.trim()}>
            Enviar
          </button>
        </div>
      </div>
    </div>
  );
}
