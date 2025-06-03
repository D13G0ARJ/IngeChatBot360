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
            --chat-background-image: url('{{ asset('images/fondo_chat.png') }}'); /* Ruta de la imagen de fondo del chat */
        }
        /* Colores para el modo oscuro */
        [data-bs-theme="dark"] {
            --primary-blue: #003366; /* Mantener fijo para branding */
            --secondary-blue: #4169E1; /* Mantener fijo para branding */
            --accent-blue-light: #6495ED; /* Mantener fijo para branding */
            --white-color: #FFFFFF; /* Mantener fijo para texto en fondos oscuros */
            --light-gray-bg: #2B2B2B; /* Fondo general oscuro del body */
            --chat-bg-color: #1A1A1A; /* Fondo de chat oscuro (negro) para burbujas, input y header */
            --chat-area-bg-color: #2B2B2B; /* Fondo gris oscuro para el área de chat y botones rápidos (no usado si hay imagen de fondo) */
            --text-color-dark: #FFFFFF; /* Texto blanco en fondos oscuros */
            --border-color-subtle: #555555; /* Borde sutil oscuro */
            --bubble-user-light: #004D40; /* Verde oscuro para usuario */
            --bubble-bot-light: #424242; /* Gris oscuro para bot */
            --chat-background-image: url('{{ asset('images/fondo_chat.png') }}'); /* Ruta de la imagen de fondo del chat en modo oscuro */
        }

        body {
            font-family: 'Inter', sans-serif; /* Fuente moderna */
            background-color: var(--light-gray-bg);
            transition: background-color 0.3s ease; /* Transición suave para el fondo del body */
            min-height: 100vh; /* Asegura que el body ocupe toda la altura de la ventana */
            display: flex; /* Usar flexbox para centrar el contenedor principal */
            align-items: center; /* Centrar verticalmente */
            justify-content: center; /* Centrar horizontalmente */
            padding: 1rem; /* Padding general para el body */
        }

        /* Contenedor principal del chat */
        .chat-container {
            background-color: var(--white-color);
            border-radius: 1.5rem; /* Bordes más redondeados */
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); /* Sombra más pronunciada */
            width: 100%;
            max-width: 600px; /* Ancho máximo para pantallas grandes */
            height: 90vh; /* Altura fija para pantallas grandes */
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s ease;
        }

        /* Media queries para responsividad */
        @media (max-width: 768px) {
            .chat-container {
                height: 98vh; /* Ocupa casi toda la altura en móviles */
                max-width: 95%; /* Ocupa casi todo el ancho en móviles */
                border-radius: 1rem; /* Bordes ligeramente menos redondeados en móvil */
            }
            .message-bubble {
                max-width: 85%; /* Las burbujas pueden ser un poco más anchas en móvil */
            }
            .quick-reply-button {
                font-size: 0.9rem !important; /* Fuente más pequeña para botones de respuesta rápida */
                padding: 0.6rem 0.9rem !important;
            }
            .form-control {
                font-size: 1rem !important; /* Ajuste del tamaño de fuente del input */
            }
            .header-title {
                font-size: 1.5rem !important; /* Ajuste del tamaño de fuente del título */
            }
            /* Ocultar el banner interno en pantallas pequeñas si molesta */
            .ingechat-logo-overlay {
                display: none;
            }
        }

        .chat-bubble {
            opacity: 0;
            transform: translateX(var(--initial-slide-x, 180px));
            animation: slideIn 0.5s ease-out forwards;
            border-radius: 0.75rem; /* Bordes redondeados para las burbujas */
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); /* Sombra sutil para las burbujas */
        }

        .chat-bubble.bot {
            --initial-slide-x: -180px;
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
            flex-shrink: 0; /* Evita que el avatar se encoja */
        }

        /* Estilos para burbujas de chat */
        .message-bubble {
            padding: 0.75rem 1rem; /* Más padding */
            border-radius: 0.75rem; /* Bordes más redondeados */
            max-width: 75%; /* Ajuste para responsividad */
            word-wrap: break-word;
            line-height: 1.4; /* Espaciado de línea para mejor legibilidad */
            color: var(--text-color-dark); /* Asegura que el texto sea oscuro */
        }

        .message-bubble.user {
            background-color: var(--bubble-user-light);
            margin-left: auto; /* Empuja la burbuja del usuario a la derecha */
        }

        .message-bubble.bot {
            background-color: var(--bubble-bot-light);
            margin-right: auto; /* Empuja la burbuja del bot a la izquierda */
        }

        /* Asegurar que el indicador de escritura esté oculto por defecto */
        .typing-indicator.hidden {
            display: none !important;
        }

        /* Estilos para los botones de respuesta rápida */
        .quick-reply-button {
            padding: 0.75rem 1.25rem !important; /* Más padding */
            border-radius: 9999px !important; /* Completamente redondeado */
            font-size: 1rem !important;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .quick-reply-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Estilos para el input de usuario */
        .user-input-field {
            border-radius: 9999px; /* Bordes redondeados */
            padding: 0.75rem 1.25rem; /* Más padding */
            border: 1px solid var(--border-color-subtle);
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .user-input-field:focus {
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 0.2rem rgba(65, 105, 225, 0.25); /* Sombra de foco */
        }

        /* Estilo para el botón de enviar */
        .send-button {
            border-radius: 9999px !important; /* Completamente redondeado */
            padding: 0.85rem !important; /* Ajuste para el tamaño del icono */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease-in-out;
        }
        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Estilo para el área de visualización del chat con imagen de fondo */
        #chat-display {
            background-image: var(--chat-background-image);
            background-size: cover; /* La imagen cubrirá todo el área */
            background-position: center; /* Centra la imagen */
            background-repeat: no-repeat; /* Evita que la imagen se repita */
            background-color: transparent !important; /* Asegura que el color de fondo no oculte la imagen */
            position: relative; /* Necesario para posicionar el banner interno */
        }

        /* Estilo para el contenedor de botones de respuesta rápida con imagen de fondo */
        #quick-reply-buttons {
            background-image: var(--chat-background-image); /* Usa la misma imagen de fondo */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: transparent !important; /* Asegura que el color de fondo no oculte la imagen */
        }

        /* Estilos para el banner interno flotante */
        .ingechat-logo-overlay {
            position: absolute;
            bottom: 1rem; /* Ajusta la distancia desde abajo */
            right: 1rem; /* Ajusta la distancia desde la derecha */
            width: 100px; /* Tamaño del logo */
            height: auto;
            opacity: 0.6; /* Hazlo semitransparente para que no interfiera con el texto */
            z-index: -1; /* Asegura que esté detrás de las burbujas de chat */
        }
        /* Ajustar el tamaño del overlay en pantallas más grandes si es necesario */
        @media (min-width: 768px) {
            .ingechat-logo-overlay {
                width: 150px; /* Un poco más grande en tablet/desktop */
                bottom: 2rem;
                right: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Header -->
        <div class="bg-[var(--primary-blue)] p-4 rounded-t-xl shadow-md d-flex align-items-center justify-content-between">
            <img src="{{ asset('images/logo_unefa.png') }}" alt="Logo UNEFA" class="me-3" style="height: 45px; width: auto; max-width: 140px; object-fit: contain;">
            {{-- Añadido el texto "UNEFA" junto al logo --}}
            <h1 class="text-[var(--text-color-dark)] fs-4 fw-bold flex-grow-1 header-title">
                <span style="color: var(--text-color-dark); margin-right: 0.5rem; font-size: 1.2em;">UNEFA</span> IngeChat 360°
            </h1>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Texto "Tema" -->
                <span class="text-white me-2">Tema</span>
                <!-- Selector de Tema (Bootstrap Switch) -->
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="themeSwitch">
                    <label class="form-check-label text-white" for="themeSwitch" id="themeSwitchLabel">Modo Oscuro</label>
                </div>

                <!-- Botón Reiniciar Chat -->
                <button id="restart-chat-btn" class="btn btn-primary bg-[var(--accent-blue-light)] border-0 text-white fw-bold py-2 px-4 rounded-lg transition-colors duration-200 quick-reply-button">
                    Reiniciar Chat
                </button>
            </div>
        </div>

        <!-- Chat Display Area -->
        <div id="chat-display" class="flex-grow-1 p-4 overflow-auto d-flex flex-column gap-3 rounded-b-lg">
            <!-- Banner interno flotante -->
            <img src="{{ asset('images/ingechat-logo.png') }}" alt="IngeChat Logo Overlay" class="ingechat-logo-overlay">

            <!-- Mensajes del chat se añadirán aquí -->
            <div class="d-flex align-items-start chat-bubble bot">
                <img src="{{ asset('images/bot_avatar.png') }}" alt="Bot Avatar" class="avatar me-3">
                <div class="message-bubble bot">
                    ¡Hola! Soy IngeChat 360°, tu asistente virtual de la UNEFA Núcleo Miranda, Sede Los Teques. Estoy aquí para brindarte información detallada sobre las carreras de Ingeniería: Sistemas, Mecánica, Telecomunicaciones y Eléctrica. ¿En qué carrera estás interesado hoy? O puedes preguntar sobre requisitos de inscripción, perfil del egresado, etc.
                </div>
            </div>

            <!-- Indicador de "Escribiendo..." -->
            <div id="typing-indicator" class="d-flex align-items-center typing-indicator hidden">
                <img src="{{ asset('images/bot_avatar.png') }}" alt="Bot Avatar" class="avatar me-3">
                <div class="bg-light text-muted fst-italic fs-6 message-bubble">
                    IngeChat 360° está escribiendo<span>.</span><span>.</span><span>.</span>
                </div>
            </div>
        </div>

        <!-- Quick Reply Buttons Area -->
        <div id="quick-reply-buttons" class="p-3 d-flex flex-wrap justify-content-start gap-2 hidden border-top border-[var(--border-color-subtle)]">
            <!-- Botones de respuesta rápida se añadirán aquí dinámicamente -->
        </div>

        <!-- User Input Area -->
        <div class="p-3 bg-[var(--chat-bg-color)] border-top border-[var(--border-color-subtle)] d-flex align-items-center rounded-b-xl shadow-md">
            <input type="text" id="user-input" placeholder="Escribe tu mensaje..."
                   class="form-control me-3 user-input-field">
            <button id="send-message-btn" class="btn btn-primary bg-[var(--secondary-blue)] border-0 text-white send-button">
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
