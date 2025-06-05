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
            --light-gray-bg: #F0F0F0; /* Fondo general del body */
            --chat-bg-color: #FFFFFF; /* Color de fondo para burbujas, input */
            --chat-area-bg-color: #F0F0F0; /* Fondo gris para el área de chat y botones rápidos (no usado si hay imagen de fondo) */
            --text-color-dark: #333333; /* Texto negro para burbujas y elementos en fondos claros */
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

        /* Estilos generales para el contenedor principal de la aplicación */
        .app-container {
            display: flex;
            flex-direction: column;
            background-color: var(--chat-bg-color); /* Usar el color de fondo de chat para el contenedor principal */
            border-radius: 1rem; /* 16px */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-lg */
            width: 100%;
            max-width: 600px;
            height: 90vh; /* Ocupa el 90% de la altura del viewport */
        }

        /* Estilos para el encabezado del chat */
        .chat-header {
            background-color: var(--primary-blue);
            padding: 0.75rem; /* p-3 */
            border-top-left-radius: 1rem; /* rounded-t-xl */
            border-top-right-radius: 1rem; /* rounded-t-xl */
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Estilos para el área de visualización del chat */
        .chat-display-area {
            flex-grow: 1;
            padding: 1rem; /* p-4 */
            overflow-y: auto; /* overflow-auto */
            background-color: var(--chat-area-bg-color); /* Usar el color de fondo para el área de chat */
            display: flex;
            flex-direction: column;
            gap: 0.75rem; /* gap-3 */
        }

        /* Estilos para burbujas de chat */
        .chat-bubble {
            display: flex;
            margin-bottom: 0.75rem; /* mb-3 */
            align-items: flex-start; /* Asegura que el avatar esté alineado con la parte superior de la burbuja */
        }

        .message-bubble {
            padding: 0.75rem; /* p-3 */
            border-radius: 0.75rem; /* rounded-xl */
            max-width: 75%; /* max-w-3/4 */
            word-wrap: break-word;
        }

        .message-bubble.user {
            background-color: var(--bubble-user-light);
            color: var(--text-color-dark);
            margin-left: auto; /* D-flex justify-content-end */
        }

        .message-bubble.bot {
            background-color: var(--bubble-bot-light);
            color: var(--text-color-dark);
            margin-right: auto; /* D-flex justify-content-start */
        }

        /* Estilos para avatares */
        .avatar {
            width: var(--avatar-size);
            height: var(--avatar-size);
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0; /* Evita que el avatar se encoja */
        }

        .avatar.user { margin-left: 0.75rem; /* ms-3 */ }
        .avatar.bot { margin-right: 0.75rem; /* me-3 */ }

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

        .typing-indicator.hidden { display: none !important; }

        /* Estilos para el área de botones rápidos */
        .quick-reply-buttons-area {
            padding: 0.75rem; /* p-3 */
            background-color: var(--chat-bg-color);
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: 0.5rem; /* gap-2 */
            border-top: 1px solid var(--border-color-subtle);
        }

        /* Estilos para los botones rápidos */
        .quick-reply-button {
            background-color: var(--secondary-blue);
            border: none;
            color: var(--white-color);
            font-weight: bold;
            padding: 0.5rem 0.75rem; /* py-2 px-3 */
            border-radius: 0.75rem; /* rounded-3 */
            transition-property: background-color;
            transition-duration: 200ms;
            font-size: 1rem; /* fs-6 */
        }

        .quick-reply-button:hover {
            background-color: var(--primary-blue);
        }

        /* Estilos para el área de entrada del usuario */
        .user-input-area {
            padding: 0.75rem; /* p-3 */
            background-color: var(--chat-bg-color);
            border-top: 1px solid var(--border-color-subtle);
            display: flex;
            align-items: center;
            border-bottom-left-radius: 1rem; /* rounded-b-xl */
            border-bottom-right-radius: 1rem; /* rounded-b-xl */
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1), 0 -2px 4px -1px rgba(0, 0, 0, 0.06); /* shadow-md */
        }

        /* Estilos para el campo de entrada de texto */
        .user-input-field {
            flex-grow: 1; /* flex-grow-1 */
            margin-right: 0.75rem; /* me-3 */
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color-subtle);
            background-color: var(--light-gray-bg);
            color: var(--text-color-dark);
        }

        .user-input-field:focus {
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 0.2rem rgba(65, 105, 225, 0.25); /* Bootstrap focus-ring like */
            outline: none;
        }
        
        /* Estilos para el botón de enviar */
        .send-button {
            background-color: var(--secondary-blue);
            border: none;
            color: var(--white-color);
            padding: 0.75rem; /* p-3 */
            border-radius: 0.75rem; /* rounded-3 */
            transition-property: background-color;
            transition-duration: 200ms;
        }

        .send-button:hover {
            background-color: var(--primary-blue);
        }
        
        /* Estilos de transición para el cambio de tema */
        html[data-bs-theme="light"] body, html[data-bs-theme="dark"] body {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        html[data-bs-theme="light"] .app-container, html[data-bs-theme="dark"] .app-container {
            transition: background-color 0.3s ease;
        }
        html[data-bs-theme="light"] .message-bubble.bot, html[data-bs-theme="dark"] .message-bubble.bot {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        html[data-bs-theme="light"] .message-bubble.user, html[data-bs-theme="dark"] .message-bubble.user {
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Animación de entrada de burbujas */
        .chat-bubble {
            opacity: 0;
            transform: translateX(var(--initial-slide-x, 100px)); /* Distancia inicial */
            animation: slideIn 0.3s ease-out forwards; /* Duración y curva de animación */
            /* Evitar que el transform afecte el layout antes de que la animación comience */
            will-change: transform, opacity;
        }

        .chat-bubble.bot {
            --initial-slide-x: -100px; /* Deslizamiento desde la izquierda para el bot */
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(var(--initial-slide-x, 100px));
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-4" data-bs-theme="light"
      data-user-avatar="{{ asset('images/user_avatar.png') }}"
      data-bot-avatar="{{ asset('images/bot_avatar.png') }}">
    <div class="app-container">
        <!-- Header -->
        <div class="chat-header">
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
        <div id="chat-display" class="chat-display-area">
            <!-- Mensajes del chat se añadirán aquí -->
            {{-- El mensaje inicial ahora se añade por JavaScript para consistencia --}}

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
        <div id="quick-reply-buttons" class="quick-reply-buttons-area hidden">
            <!-- Botones de respuesta rápida se añadirán aquí dinámicamente -->
        </div>

        <!-- User Input Area -->
        <div class="user-input-area">
            <input type="text" id="user-input" placeholder="Escribe tu mensaje..."
                   class="form-control user-input-field">
            {{-- Ajuste del botón de enviar: Aumentado el tamaño del SVG --}}
            <button id="send-message-btn" class="btn btn-primary send-button">
                <svg width="28" height="28" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.409l-7-14z"></path></svg>
            </button>
        </div>
    </div>
</body>
</html>
