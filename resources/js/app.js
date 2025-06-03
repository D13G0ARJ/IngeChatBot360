// resources/js/app.js
import './bootstrap'; // Incluye Axios y otras configuraciones de Laravel

// Importar Bootstrap JavaScript
import 'bootstrap';

// Lógica JavaScript para el frontend del chatbot
document.addEventListener('DOMContentLoaded', () => {
    const chatDisplay = document.getElementById('chat-display');
    const userInput = document.getElementById('user-input');
    const sendMessageBtn = document.getElementById('send-message-btn');
    const restartChatBtn = document.getElementById('restart-chat-btn');
    const typingIndicator = document.getElementById('typing-indicator');
    const quickReplyButtonsContainer = document.getElementById('quick-reply-buttons');
    const themeSwitch = document.getElementById('themeSwitch');
    const themeSwitchLabel = document.getElementById('themeSwitchLabel');
    const body = document.body;

    // Función para aplicar el tema
    function applyTheme(theme) {
        body.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        if (theme === 'dark') {
            themeSwitchLabel.innerText = 'Modo Claro';
            themeSwitch.checked = true;
        } else {
            themeSwitchLabel.innerText = 'Modo Oscuro';
            themeSwitch.checked = false;
        }
    }

    // Cargar tema guardado o por defecto
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    // Event listener para el cambio de tema
    themeSwitch.addEventListener('change', () => {
        const newTheme = themeSwitch.checked ? 'dark' : 'light';
        applyTheme(newTheme);
    });

    // Función para añadir mensajes al chat
    function addMessage(message, isUser) {
        // Ocultar botones de respuesta rápida antes de añadir un nuevo mensaje
        hideQuickReplyButtons();

        const messageDiv = document.createElement('div');
        messageDiv.classList.add('d-flex', 'mb-3', 'chat-bubble'); // Bootstrap classes

        const avatarImg = document.createElement('img');
        avatarImg.classList.add('avatar'); // Custom CSS for avatar size/shape

        const bubbleContent = document.createElement('div');
        bubbleContent.classList.add('message-bubble'); // Custom CSS for bubble styling

        if (isUser) {
            messageDiv.classList.add('justify-content-end');
            avatarImg.src = userAvatar; // Variable global definida en Blade
            avatarImg.classList.add('ms-3'); // margin-left
            bubbleContent.classList.add('user'); // Class for user bubble styling
            messageDiv.appendChild(bubbleContent);
            messageDiv.appendChild(avatarImg);
        } else {
            messageDiv.classList.add('justify-content-start', 'bot'); // Class 'bot' for animation
            avatarImg.src = botAvatar; // Variable global definida en Blade
            avatarImg.classList.add('me-3'); // margin-right
            bubbleContent.classList.add('bot'); // Class for bot bubble styling
            messageDiv.appendChild(avatarImg);
            messageDiv.appendChild(bubbleContent);
        }

        bubbleContent.innerText = message;
        chatDisplay.appendChild(messageDiv);
        chatDisplay.scrollTop = chatDisplay.scrollHeight; // Scroll al final
    }

    // Función para mostrar el indicador de escritura
    function showTypingIndicator() {
        typingIndicator.classList.remove('hidden');
        chatDisplay.scrollTop = chatDisplay.scrollHeight; // Scroll al final
    }

    // Función para ocultar el indicador de escritura
    function hideTypingIndicator() {
        typingIndicator.classList.add('hidden');
    }

    // Función para añadir botones de respuesta rápida
    function addQuickReplyButtons(suggestions) {
        hideQuickReplyButtons(); // Limpiar antes de añadir

        if (suggestions.length === 0) return;

        quickReplyButtonsContainer.classList.remove('hidden');
        suggestions.forEach(text => {
            const button = document.createElement('button');
            button.classList.add('btn', 'btn-primary', 'bg-[var(--secondary-blue)]', 'border-0', 'text-white', 'fw-bold', 'py-2', 'px-3', 'rounded-3', 'transition-colors', 'duration-200', 'fs-6'); // Bootstrap classes
            button.innerText = text;
            button.addEventListener('click', () => {
                userInput.value = text;
                sendMessage();
            });
            quickReplyButtonsContainer.appendChild(button);
        });
        chatDisplay.scrollTop = chatDisplay.scrollHeight; // Scroll al final
    }

    // Función para ocultar y limpiar botones de respuesta rápida
    function hideQuickReplyButtons() {
        quickReplyButtonsContainer.classList.add('hidden');
        quickReplyButtonsContainer.innerHTML = ''; // Limpiar botones
    }

    // Función para enviar mensaje al backend
    async function sendMessage() {
        const message = userInput.value.trim();
        if (!message) return;

        addMessage(message, true); // Mostrar mensaje del usuario
        userInput.value = ''; // Limpiar input
        userInput.disabled = true; // Deshabilitar input
        sendMessageBtn.disabled = true; // Deshabilitar botón enviar

        showTypingIndicator(); // Mostrar el indicador de escritura

        try {
            const response = await fetch('/api/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') // Laravel CSRF Token
                },
                body: JSON.stringify({ message: message })
            });

            if (!response.ok) {
                // Si la respuesta no es OK, intentar leer el cuerpo para más detalles
                const errorBody = await response.text();
                console.error('Server responded with an error:', response.status, errorBody);
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorBody.substring(0, 200)}...`); // Mostrar un fragmento
            }

            const data = await response.json();
            
            hideTypingIndicator(); // Ocultar el indicador después de recibir la respuesta del bot
            addMessage(data.bot_response, false); // Mostrar respuesta del bot

            // Lógica para mostrar botones de respuesta rápida basados en la respuesta del bot
            if (data.bot_response.includes("¡Hola! Soy IngeChat 360°")) {
                addQuickReplyButtons(["Ingeniería de Sistemas", "Ingeniería Mecánica", "Ingeniería Eléctrica", "Ingeniería de Telecomunicaciones", "Requisitos de Inscripción"]);
            } else if (data.bot_response.includes("Ingeniería de Sistemas")) {
                addQuickReplyButtons(["Pensum de Sistemas", "Perfil del Egresado de Sistemas", "Salidas Profesionales de Sistemas", "Duración de Sistemas"]);
            }
            // Añade más condiciones para otras carreras o temas específicos
            else if (data.bot_response.includes("Ingeniería Mecánica")) {
                addQuickReplyButtons(["Pensum de Mecánica", "Perfil del Egresado de Mecánica"]);
            }
            else if (data.bot_response.includes("Ingeniería Eléctrica")) {
                addQuickReplyButtons(["Pensum de Eléctrica", "Perfil del Egresado de Eléctrica"]);
            }
            else if (data.bot_response.includes("Ingeniería de Telecomunicaciones")) {
                addQuickReplyButtons(["Pensum de Telecomunicaciones", "Perfil del Egresado de Telecomunicaciones"]);
            }


        } catch (error) {
            console.error('Error al enviar mensaje:', error);
            hideTypingIndicator(); // Ocultar el indicador si hay un error
            addMessage('Lo siento, hubo un error al procesar tu solicitud. Por favor, inténtalo de nuevo.', false);
        } finally {
            userInput.disabled = false; // Habilitar input
            sendMessageBtn.disabled = false; // Habilitar botón enviar
            userInput.focus(); // Poner el foco de nuevo en el campo de entrada
        }
    }

    // Función para reiniciar el chat
    async function restartChat() {
        // IMPORTANTE: Según las instrucciones, evita window.confirm() en iframes.
        // Para una solución adecuada, implementa un modal de confirmación personalizado en chatbot.blade.php.
        console.warn('Reiniciar chat solicitado. Para una confirmación adecuada, implementa un modal personalizado en chatbot.blade.php.');
        
        // Limpiar el chat visualmente
        chatDisplay.innerHTML = '';
        hideQuickReplyButtons();
        hideTypingIndicator();

        try {
            const response = await fetch('/api/chat/restart', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            if (!response.ok) {
                const errorBody = await response.text();
                console.error('Server responded with an error during restart:', response.status, errorBody);
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorBody.substring(0, 200)}...`);
            }
            const data = await response.json();
            console.log(data.message);
            // Mostrar mensaje inicial después de reiniciar
            addMessage('¡Hola! Soy IngeChat 360°, tu asistente virtual de la UNEFA Núcleo Miranda, Sede Los Teques. Estoy aquí para brindarte información detallada sobre las carreras de Ingeniería: Sistemas, Mecánica, Telecomunicaciones y Eléctrica. ¿En qué carrera estás interesado hoy? O puedes preguntar sobre requisitos de inscripción, perfil del egresado, etc.', false);
            addQuickReplyButtons(["Ingeniería de Sistemas", "Ingeniería Mecánica", "Ingeniería Eléctrica", "Ingeniería de Telecomunicaciones", "Requisitos de Inscripción"]);

        } catch (error) {
            console.error('Error al reiniciar chat:', error);
            addMessage('Lo siento, hubo un error al reiniciar el chat.', false);
        } finally {
            userInput.focus();
        }
    }

    // Event Listeners
    sendMessageBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    restartChatBtn.addEventListener('click', restartChat);

    // Mostrar botones iniciales
    addQuickReplyButtons(["Ingeniería de Sistemas", "Ingeniería Mecánica", "Ingeniería Eléctrica", "Ingeniería de Telecomunicaciones", "Requisitos de Inscripción"]);
});
