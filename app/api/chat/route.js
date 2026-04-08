export const runtime = 'nodejs';

import path from 'node:path';
import { promises as fs } from 'node:fs';

const SYSTEM_INSTRUCTION =
  'Eres IngeChat 360°, un asistente virtual especializado en proporcionar información ' +
  'precisa y detallada sobre las carreras de Ingeniería (Sistemas, Mecánica, ' +
  'Telecomunicaciones y Eléctrica) de la UNEFA Núcleo Miranda, Sede Los Teques. ' +
  'Tu objetivo es asistir a estudiantes actuales y futuros con consultas académicas y profesionales ' +
  'relacionadas exclusivamente con estas carreras. ' +
  'Si la pregunta no está directamente relacionada con las carreras de ingeniería de la UNEFA, ' +
  'responde amablemente que tu función es específica y no puedes asistir con ese tema. ' +
  'Proporciona respuestas concisas pero informativas, y si es posible, sugiere dónde encontrar más detalles.';

const CAREER_KEYWORDS = ['sistemas', 'mecanica', 'telecomunicaciones', 'electrica'];

let cachedData = null;

async function readJson(filePath) {
  const raw = await fs.readFile(filePath, 'utf-8');
  return JSON.parse(raw);
}

async function loadDataOnce() {
  if (cachedData) return cachedData;

  const root = process.cwd();
  const dataDir = path.join(root, 'data');
  const carrerasDir = path.join(dataDir, 'carreras');

  const carrerasData = {};
  let faqsData = {};
  let unefaInfo = {};

  try {
    const files = await fs.readdir(carrerasDir);
    for (const filename of files) {
      if (!filename.endsWith('.json')) continue;
      const careerName = filename.replace('ingenieria_', '').replace('.json', '');
      const full = path.join(carrerasDir, filename);
      try {
        carrerasData[careerName] = await readJson(full);
      } catch {
        // ignore broken file
      }
    }
  } catch {
    // carreras folder missing
  }

  try {
    faqsData = await readJson(path.join(dataDir, 'faqs.json'));
  } catch {
    faqsData = {};
  }

  try {
    unefaInfo = await readJson(path.join(dataDir, 'unefa_info.json'));
  } catch {
    unefaInfo = {};
  }

  cachedData = { carrerasData, faqsData, unefaInfo };
  return cachedData;
}

function getFaqAnswer(faqsData, questionLower) {
  const list = faqsData?.preguntas_frecuentes || [];
  for (const qa of list) {
    const p = String(qa?.pregunta || '').toLowerCase();
    if (questionLower && questionLower.includes(p) || p.includes(questionLower)) {
      const ans = String(qa?.respuesta || '').trim();
      if (ans) return ans;
    }
  }
  return null;
}

function formatPlan(plan) {
  if (!plan || typeof plan !== 'object') return '';
  const lines = [];
  for (const [semester, courses] of Object.entries(plan)) {
    if (Array.isArray(courses) && courses.every((c) => c && typeof c === 'object')) {
      const names = courses.map((c) => c.asignatura || 'N/A');
      lines.push(`Semestre ${semester}: ${names.join(', ')}`);
    } else if (Array.isArray(courses) && courses.every((c) => typeof c === 'string')) {
      lines.push(`Semestre ${semester}: ${courses.join(', ')}`);
    } else if (typeof courses === 'string') {
      lines.push(`Semestre ${semester}: ${courses}`);
    } else if (Array.isArray(courses)) {
      const names = courses.map((c) => (c && typeof c === 'object' ? (c.asignatura || 'N/A') : String(c)));
      lines.push(`${String(semester).replaceAll('_', ' ')}: ${names.join(', ')}`);
    } else {
      lines.push(`Semestre ${semester}: Información no formateada.`);
    }
  }
  return lines.join('\n');
}

function buildHistoryText(history) {
  if (!Array.isArray(history) || !history.length) return '';
  const lines = [];
  for (const item of history) {
    if (!item || typeof item !== 'object') continue;
    const role = String(item.role || '').toLowerCase();
    const content = String(item.content || '').trim();
    if (!content) continue;
    if (role === 'assistant' || role === 'model' || role === 'bot') {
      lines.push(`Asistente: ${content}`);
    } else {
      lines.push(`Usuario: ${content}`);
    }
  }
  return lines.join('\n');
}

async function callGemini({ message, history }) {
  const apiKey = process.env.GEMINI_API_KEY;
  if (!apiKey) {
    throw new Error('GEMINI_API_KEY no está configurada en Vercel');
  }

  const model = process.env.GEMINI_MODEL || 'models/gemini-flash-latest';
  const url = `https://generativelanguage.googleapis.com/v1beta/${model}:generateContent?key=${encodeURIComponent(apiKey)}`;

  const historyText = buildHistoryText(history);
  const parts = [SYSTEM_INSTRUCTION];
  if (historyText) parts.push('Contexto de la conversación:\n' + historyText);
  parts.push('Usuario: ' + message);
  const prompt = parts.join('\n\n');

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      contents: [{ role: 'user', parts: [{ text: prompt }] }],
      generationConfig: {
        temperature: 0.4,
        maxOutputTokens: 700,
      },
    }),
  });

  const data = await res.json().catch(() => null);
  if (!res.ok) {
    const msg = data?.error?.message || `Gemini error (${res.status})`;
    throw new Error(msg);
  }

  const text =
    data?.candidates?.[0]?.content?.parts?.map((p) => p?.text).filter(Boolean).join('') ||
    data?.candidates?.[0]?.content?.parts?.[0]?.text ||
    data?.text ||
    '';

  return String(text || '').trim() || 'No pude generar una respuesta en este momento.';
}

export async function POST(request) {
  try {
    const payload = await request.json().catch(() => ({}));
    const message = String(payload?.message || '').trim();
    const history = payload?.history;

    if (!message) {
      return Response.json({ error: "Missing 'message'" }, { status: 400 });
    }

    const lower = message.toLowerCase();
    const { carrerasData, faqsData, unefaInfo } = await loadDataOnce();

    // 1) FAQs
    const faq = getFaqAnswer(faqsData, lower);
    if (faq) return Response.json({ reply: faq });

    // 2) Carreras
    for (const keyword of CAREER_KEYWORDS) {
      if (!lower.includes(keyword)) continue;

      const careerInfo = carrerasData?.[keyword] || {};
      if (!careerInfo || typeof careerInfo !== 'object') break;

      if (lower.includes('plan de estudio') || lower.includes('pensum')) {
        const plan = careerInfo?.plan_estudios || {};
        const planStr = formatPlan(plan);
        if (planStr) {
          return Response.json({
            reply:
              `El plan de estudios de Ingeniería de ${keyword.charAt(0).toUpperCase() + keyword.slice(1)} incluye:\n` +
              `${planStr}\n` +
              'Para más detalles, consulta la sección de la carrera en el portal de la UNEFA.',
          });
        }
        return Response.json({ reply: `Información del plan de estudios para Ingeniería de ${keyword} no disponible.` });
      }

      if (lower.includes('perfil') || lower.includes('egresado')) {
        return Response.json({ reply: careerInfo?.perfil_egresado || `Perfil del egresado para Ingeniería de ${keyword} no disponible.` });
      }

      if (lower.includes('salidas profesionales') || lower.includes('campo laboral')) {
        const salidas = Array.isArray(careerInfo?.salidas_profesionales) ? careerInfo.salidas_profesionales : [];
        if (salidas.length) {
          return Response.json({ reply: `Algunas salidas profesionales para Ingeniería de ${keyword} incluyen: ${salidas.join(', ')}.` });
        }
      }

      if (lower.includes('descripcion') || lower.includes('que es') || lower.includes('qué es')) {
        return Response.json({ reply: careerInfo?.descripcion || `Descripción para Ingeniería de ${keyword} no disponible.` });
      }

      if (lower.includes('duracion') || lower.includes('duración')) {
        return Response.json({ reply: `La duración de la carrera de Ingeniería de ${keyword} es de ${careerInfo?.duracion || 'N/A'}.` });
      }

      const desc = careerInfo?.descripcion || 'Descripción no disponible.';
      const dur = careerInfo?.duracion || 'N/A';
      return Response.json({
        reply: `Ingeniería de ${keyword.charAt(0).toUpperCase() + keyword.slice(1)}: ${desc} Duración: ${dur}. Puedes preguntar sobre su perfil de egresado, plan de estudios o salidas profesionales.`,
      });
    }

    // 3) Info UNEFA
    if (lower.includes('contacto') || lower.includes('telefono') || lower.includes('teléfono') || lower.includes('ubicacion') || lower.includes('ubicación')) {
      const info = unefaInfo?.contacto;
      if (info) return Response.json({ reply: info });
    }
    if (lower.includes('mision') || lower.includes('misión')) {
      const info = unefaInfo?.mision;
      if (info) return Response.json({ reply: info });
    }
    if (lower.includes('vision') || lower.includes('visión')) {
      const info = unefaInfo?.vision;
      if (info) return Response.json({ reply: info });
    }
    if (lower.includes('nombre de la institucion') || lower.includes('nombre de la institución') || lower.includes('nombre de la universidad')) {
      const info = unefaInfo?.nombre_institucion;
      if (info) return Response.json({ reply: info });
    }

    // 4) Gemini
    const reply = await callGemini({ message, history });
    return Response.json({ reply });
  } catch (e) {
    return Response.json({ error: e?.message || 'Internal error' }, { status: 500 });
  }
}

export async function GET() {
  return Response.json({
    name: 'IngeChatBot360',
    status: 'ok',
    endpoints: {
      chat: { method: 'POST', path: '/api/chat', body: { message: 'Hola', history: [] } },
    },
  });
}
