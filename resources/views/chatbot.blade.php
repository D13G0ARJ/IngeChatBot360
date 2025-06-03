<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IngeChat 360° - UNEFA</title>
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- Necesario para solicitudes POST en Laravel --}}

    <!-- Incluye los assets de Vite (CSS y JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Colores de UNEFA para el modo claro */
        :root {
            --primary-blue: #003366;
            --secondary-blue: #4169E1;
            --accent-blue-light: #6495ED;
            --white-color: #FFFFFF;
            --light-gray-bg: #F0F0F0;
            --chat-bg-color: #FFFFFF;
            --text-color-dark: #333333;
            --border-color-subtle: #D0D0D0;
            --bubble-user-light: #DCF8C6;
            --bubble-bot-light: #E0E0E0;
            --avatar-size: 40px; /* Tamaño de avatar */
        }
        /* Colores para el modo oscuro */
        [data-bs-theme="dark"] {
            --primary-blue: #003366; /* Mantener fijo para branding */
            --secondary-blue: #4169E1; /* Mantener fijo para branding */
            --accent-blue-light: #6495ED; /* Mantener fijo para branding */
            --white-color: #FFFFFF; /* Mantener fijo para texto en fondos oscuros */
            --light-gray-bg: #2B2B2B; /* Fondo general oscuro */
            --chat-bg-color: #343638; /* Fondo de chat oscuro */
            --text-color-dark: #FFFFFF; /* Texto blanco en fondos oscuros */
            --border-color-subtle: #555555; /* Borde sutil oscuro */
            --bubble-user-light: #004D40; /* Verde oscuro para usuario */
            --bubble-bot-light: #424242; /* Gris oscuro para bot */
        }

        body {
            font-family: 'Inter', sans-serif; /* O la fuente que prefieras */
            background-color: var(--light-gray-bg);
            transition: background-color 0.3s ease; /* Transición suave para el fondo del body */
        }

        .chat-bubble {
            opacity: 0;
            transform: translateX(var(--initial-slide-x, 180px)); /* Ajustado a 180px */
            animation: slideIn 0.5s ease-out forwards; /* Duración de 0.5s para suavizar */
        }

        .chat-bubble.bot {
            --initial-slide-x: -180px; /* Para que la burbuja del bot venga de la izquierda */
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Estilos para el indicador de escritura */
        .typing-indicator span {
            opacity: 0.2;
            animation: blink 1.4s infinite;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes blink {
            0%, 100% { opacity: 0.2; }
            50% { opacity: 1; }
        }

        /* Estilos para avatares */
        .avatar {
            width: var(--avatar-size);
            height: var(--avatar-size);
            border-radius: 50%;
            object-fit: cover;
        }

        /* Estilos para burbujas de chat */
        .message-bubble {
            padding: 12px;
            border-radius: 12px;
            max-width: 75%; /* Ajuste para responsividad */
            word-wrap: break-word; /* Asegura que el texto largo se rompa */
        }

        .message-bubble.user {
            background-color: var(--bubble-user-light);
            color: var(--text-color-dark);
        }

        .message-bubble.bot {
            background-color: var(--bubble-bot-light);
            color: var(--text-color-dark);
        }

        /* Asegurar que el indicador de escritura esté oculto por defecto */
        .typing-indicator.hidden {
            display: none !important;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-4" data-bs-theme="light">
    <div class="bg-white rounded-3 shadow-lg w-100" style="max-width: 600px; height: 90vh; display: flex; flex-direction: column;">
        <!-- Header -->
        <div class="bg-[var(--primary-blue)] p-3 rounded-top d-flex align-items-center justify-content-between">
            {{-- Ajuste del logo: height fijo, width auto para mantener proporción, y max-width para control --}}
            <img src="{{ asset('images/logo_unefa.png') }}" alt="Logo UNEFA" class="me-3" style="height: 40px; width: auto; max-width: 120px; object-fit: contain;">
            <h1 class="text-white fs-4 fw-bold flex-grow-1">IngeChat 360°</h1>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Selector de Tema (Bootstrap Switch) -->
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="themeSwitch">
                    <label class="form-check-label text-white" for="themeSwitch" id="themeSwitchLabel">Modo Oscuro</label>
                </div>

                <!-- Botón Reiniciar Chat -->
                <button id="restart-chat-btn" class="btn btn-primary bg-[var(--accent-blue-light)] border-0 text-white fw-bold py-2 px-4 rounded-3 transition-colors duration-200">
                    Reiniciar Chat
                </button>
            </div>
        </div>

        <!-- Chat Display Area -->
        <div id="chat-display" class="flex-grow-1 p-4 overflow-auto bg-[var(--chat-bg-color)] d-flex flex-column gap-3">
            <!-- Mensajes del chat se añadirán aquí -->
            <div class="d-flex align-items-start chat-bubble bot">
                <img src="{{ asset('images/bot_avatar.png') }}" alt="Bot Avatar" class="avatar me-3">
                <div class="message-bubble bot">
                    ¡Hola! Soy IngeChat 360°, tu asistente virtual de la UNEFA Núcleo Miranda, Sede Los Teques. Estoy aquí para brindarte información detallada sobre las carreras de Ingeniería: Sistemas, Mecánica, Telecomunicaciones y Eléctrica. ¿En qué carrera estás interesado hoy? O puedes preguntar sobre requisitos de inscripción, perfil del egresado, etc.
                </div>
            </div>

            <!-- Indicador de "Escribiendo..." -->
            {{-- Asegurarse de que esté oculto por defecto con la clase 'hidden' --}}
            <div id="typing-indicator" class="d-flex align-items-center typing-indicator hidden">
                <img src="{{ asset('images/bot_avatar.png') }}" alt="Bot Avatar" class="avatar me-3">
                <div class="bg-light text-muted fst-italic fs-6 message-bubble">
                    IngeChat 360° está escribiendo<span>.</span><span>.</span><span>.</span>
                </div>
            </div>
        </div>

        <!-- Quick Reply Buttons Area -->
        <div id="quick-reply-buttons" class="p-3 bg-[var(--chat-bg-color)] d-flex flex-wrap justify-content-start gap-2 hidden">
            <!-- Botones de respuesta rápida se añadirán aquí dinámicamente -->
        </div>

        <!-- User Input Area -->
        <div class="p-3 bg-[var(--chat-bg-color)] border-top border-[var(--border-color-subtle)] d-flex align-items-center rounded-bottom">
            <input type="text" id="user-input" placeholder="Escribe tu mensaje..."
                   class="form-control me-3" style="font-size: 1.1rem;">
            {{-- Ajuste del botón de enviar: Aumentado el tamaño del SVG --}}
            <button id="send-message-btn" class="btn btn-primary bg-[var(--secondary-blue)] border-0 text-white p-3 rounded-3 transition-colors duration-200">
                <svg width="28" height="28" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.409l-7-14z"></path></svg>
            </button>
        </div>
    </div>

    <script>
        // Acceso a los assets de imágenes (asegúrate de que existan en public/images)
        const userAvatar = "{{ asset('images/user_avatar.png') }}";
        const botAvatar = "{{ asset('images/bot_avatar.png') }}";
    </script>
</body>
</html>
