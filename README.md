


# IngeChat 360° - UNEFA 🤖

## 📋 Descripción

**IngeChat 360°** es un chatbot inteligente especializado en proporcionar información detallada sobre las carreras de Ingeniería de la Universidad Nacional Experimental Politécnica de la Fuerza Armada Bolivariana (UNEFA), Núcleo Miranda, Sede Los Teques. [1](#0-0) 

El chatbot está diseñado para asistir a estudiantes actuales y futuros con consultas académicas y profesionales relacionadas con las cuatro carreras de ingeniería disponibles en la institución. [2](#0-1) 

## 🎯 Misión y Visión

### Misión
La UNEFA, a través de IngeChat 360°, busca modernizar y optimizar los procesos de divulgación de información académica y profesional, ofreciendo acceso inmediato, interactivo y personalizado a información clave sobre las carreras de Ingeniería. [3](#0-2) 

### Visión
IngeChat 360° posiciona a la UNEFA como una institución a la vanguardia en el uso estratégico de soluciones tecnológicas accesibles para la gestión educativa, modernizando la comunicación y orientación estudiantil. [4](#0-3) 

## 🏫 Carreras Disponibles

El chatbot proporciona información especializada sobre las siguientes carreras de ingeniería:

- **Ingeniería de Sistemas**
- **Ingeniería Mecánica** 
- **Ingeniería en Telecomunicaciones**
- **Ingeniería Eléctrica** [5](#0-4) 

## ✨ Características Principales

### Funcionalidades Iniciales
- **Descripción general de carreras**: Perfil del egresado, áreas de estudio y competencias
- **Plan de estudios**: Materias, pensum y duración de cada carrera
- **Áreas de especialización** disponibles
- **Salidas profesionales y campo laboral** detalladas
- **Requisitos y procesos de inscripción** (guía básica)
- **Actividades extracurriculares**: Grupos de investigación y proyectos estudiantiles [6](#0-5) 

### Capacidades Técnicas
- **Respuestas inteligentes híbridas**: Combina datos locales con IA de Google Gemini [7](#0-6) 
- **Interfaz gráfica moderna**: Desarrollada con CustomTkinter [8](#0-7) 
- **Modo claro/oscuro**: Cambio dinámico de tema [9](#0-8) 
- **Botones de respuesta rápida**: Navegación intuitiva [10](#0-9) 
- **Indicador de escritura animado**: Retroalimentación visual [11](#0-10) 

## 🏗️ Arquitectura del Sistema

### Estructura del Proyecto

```
IngeChatBot360/
├── src/
│   ├── core/
│   │   ├── chatbot_logic.py    # Lógica principal del chatbot
│   │   └── data_manager.py     # Gestión de datos locales
│   ├── gui/
│   │   ├── main_window.py      # Ventana principal
│   │   ├── chat_bubble.py      # Burbujas de chat
│   │   └── scrollable_frame.py # Marco desplazable
│   ├── utils/
│   │   ├── config.py           # Configuración
│   │   └── gemini_api.py       # API de Google Gemini
│   └── main.py                 # Punto de entrada
├── data/
│   ├── carreras/               # Información por carrera
│   ├── faqs.json              # Preguntas frecuentes
│   ├── training_data.json     # Datos de entrenamiento
│   └── unefa_info.json        # Información institucional
├── assets/
│   └── styles/                # Recursos visuales
└── list_models.py             # Utilidad para listar modelos
```

### Componentes Principales

#### 1. Motor de Lógica (`ChatbotLogic`)
Procesa los mensajes del usuario con un enfoque híbrido que prioriza los datos locales antes de consultar la IA. [12](#0-11) 

#### 2. Gestor de Datos (`DataManager`)
Administra la información local almacenada en archivos JSON, incluyendo carreras, FAQs e información institucional. [13](#0-12) 

#### 3. API de Gemini (`GeminiAPI`)
Integra con Google Gemini 1.5 Flash para respuestas inteligentes cuando no hay datos locales disponibles. [14](#0-13) 

#### 4. Interfaz Gráfica (`MainWindow`)
Proporciona una experiencia de usuario moderna y responsive con CustomTkinter. [15](#0-14) 

## 🛠️ Tecnologías Utilizadas

- **Python 3.x**: Lenguaje de programación principal
- **CustomTkinter**: Framework para interfaz gráfica moderna [16](#0-15) 
- **Google Generative AI (Gemini)**: Modelo de IA para respuestas inteligentes [17](#0-16) 
- **PIL (Pillow)**: Procesamiento de imágenes [18](#0-17) 
- **python-dotenv**: Gestión de variables de entorno [19](#0-18) 
- **JSON**: Almacenamiento de datos estructurados

## 🚀 Instalación y Configuración

### Prerrequisitos
- Python 3.8 o superior
- Clave API de Google Gemini

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/D13G0ARJ/IngeChatBot360.git
   cd IngeChatBot360
   ```

2. **Instalar dependencias**
   ```bash
   pip install customtkinter pillow google-generativeai python-dotenv
   ```

3. **Configurar variables de entorno**
   Crear un archivo `.env` en la raíz del proyecto:
   ```env
   GEMINI_API_KEY=tu_clave_api_aqui
   ``` [20](#0-19) 

4. **Verificar modelos disponibles** (opcional)
   ```bash
   python list_models.py
   ``` [21](#0-20) 

## 🎮 Uso

### Ejecutar la aplicación
```bash
python src/main.py
``` [22](#0-21) 

### Funcionalidades de la Interfaz

#### Controles Principales
- **Campo de texto**: Para escribir mensajes al chatbot
- **Botón Enviar**: Envía el mensaje (también con Enter)
- **Botón Reiniciar Chat**: Inicia una nueva sesión [23](#0-22) 
- **Switch Modo Oscuro/Claro**: Cambia el tema visual [24](#0-23) 

#### Botones de Respuesta Rápida
El sistema incluye botones contextuales que aparecen automáticamente para facilitar la navegación:
- Después del mensaje de bienvenida: opciones de carreras y requisitos
- Para carreras específicas: pensum, perfil del egresado, salidas profesionales [25](#0-24) 

### Ejemplos de Consultas

- "¿Qué es Ingeniería de Sistemas?"
- "Cuéntame sobre el plan de estudios de Mecánica"
- "¿Cuáles son las salidas profesionales de Telecomunicaciones?"
- "¿Cuál es la duración de Ingeniería Eléctrica?"
- "¿Cuáles son los requisitos de inscripción?"

## 🧠 Lógica de Funcionamiento

### Procesamiento de Mensajes
El chatbot utiliza un sistema de priorización inteligente:

1. **Búsqueda en FAQs locales**: Respuestas rápidas para preguntas frecuentes [26](#0-25) 
2. **Información de carreras**: Búsqueda por palabras clave específicas [27](#0-26) 
3. **Información institucional**: Datos generales de la UNEFA [28](#0-27) 
4. **Consulta a Gemini**: Para preguntas no cubiertas localmente [29](#0-28) 

### Palabras Clave Reconocidas
El sistema reconoce automáticamente términos relacionados con:
- Nombres de carreras: "sistemas", "mecanica", "telecomunicaciones", "electrica"
- Consultas específicas: "plan de estudio", "pensum", "perfil", "salidas profesionales", "duracion"
- Información institucional: "contacto", "mision", "vision", "ubicacion" [30](#0-29) 

## 📊 Limitaciones Actuales

- **Alcance específico**: Centrado únicamente en las cuatro carreras de ingeniería de la UNEFA
- **Sin funcionalidades transaccionales**: No permite inscripciones directas o pagos
- **Interacción estructurada**: Basada en patrones predefinidos, aunque busca naturalidad [31](#0-30) 

## 🔮 Futuras Mejoras

### Recomendaciones de Desarrollo
- **Sistema de mantenimiento**: Plan de actualización periódica del contenido
- **Gestión dinámica**: Sistema similar a QR dinámicos para contenido
- **Funcionalidades avanzadas**: 
  - FAQs mejorados
  - Enlaces a recursos universitarios
  - Sistema de citas con orientadores
- **Integración institucional**: Conexión con líneas de investigación de la UNEFA [32](#0-31) 



## 🤝 Contribución

### Cómo Contribuir
1. Fork del repositorio
2. Crear una rama para la nueva característica
3. Realizar cambios y pruebas
4. Enviar Pull Request con descripción detallada

### Áreas de Mejora
- Expansión de la base de datos de carreras
- Mejoras en la interfaz de usuario
- Optimización del procesamiento de lenguaje natural
- Integración con sistemas universitarios adicionales

## 📄 Licencia

Este proyecto es desarrollado para la UNEFA Núcleo Miranda, Sede Los Teques, como parte de las iniciativas de modernización tecnológica educativa.

## 👥 Créditos

Desarrollado para la **Universidad Nacional Experimental Politécnica de la Fuerza Armada Bolivariana (UNEFA)**, Núcleo Miranda, Sede Los Teques, como parte del proyecto de modernización de la gestión educativa y orientación estudiantil.

---

**IngeChat 360°** - *Revolucionando la orientación académica en ingeniería* 🚀


