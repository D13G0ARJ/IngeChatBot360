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

    // Acceso a los assets de imágenes (asegúrate de que existan en public/images)
    // Se leen directamente desde los atributos data- del body HTML
    const userAvatar = body.getAttribute('data-user-avatar');
    const botAvatar = body.getAttribute('data-bot-avatar');

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

    /**
     * Convierte un texto con formato Markdown básico a HTML.
     * Soporta negritas (**texto**), cursivas (*texto*), saltos de línea y listas con guiones.
     * @param {string} markdownText - El texto en formato Markdown.
     * @returns {string} El texto convertido a HTML.
     */
    function parseMarkdown(markdownText) {
        let htmlText = markdownText;

        // Negritas: **texto** -> <strong>texto</strong>
        htmlText = htmlText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Cursivas: *texto* -> <em>texto</em>
        // Asegúrate de que no coincida con los asteriscos de negritas ya procesados
        htmlText = htmlText.replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>'); // Fixed regex for italic

        // Saltos de línea: \n -> <br>
        htmlText = htmlText.replace(/\n/g, '<br>');

        // Listas: - item1<br>- item2 -> <ul><li>item1</li><li>item2</li></ul>
        // Esto es una simplificación y asume que las listas están al inicio de la línea
        // y no hay otros contenidos antes de los items de lista.
        // También, asegúrate de que cada <li> termine su <br> si lo tiene y no se duplique
        const listRegex = /^- (.*)$/gm; // Match lines starting with '- '
        let matches = [];
        let tempText = htmlText;
        let match;
        // Reiniciar el índice de la última coincidencia para asegurar que la búsqueda sea global
        listRegex.lastIndex = 0; 
        while ((match = listRegex.exec(tempText)) !== null) {
            matches.push({ text: match[1], index: match.index, fullMatch: match[0] });
        }

        if (matches.length > 0) {
            let parts = [];
            let lastIndex = 0;
            let inList = false;

            matches.forEach((item, i) => {
                // Agregar el texto antes de la lista actual
                if (item.index > lastIndex) {
                    if (inList) {
                        parts.push('</ul>');
                        inList = false;
                    }
                    parts.push(tempText.substring(lastIndex, item.index));
                }

                // Iniciar nueva lista si no estamos en una
                if (!inList) {
                    parts.push('<ul>');
                    inList = true;
                }
                // Añadir el ítem de la lista, eliminando <br> internos si los hay
                parts.push(`<li>${item.text.replace(/<br>/g, '')}</li>`);
                lastIndex = item.index + item.fullMatch.length;
            });

            // Cerrar la última lista si quedó abierta
            if (inList) {
                parts.push('</ul>');
            }
            // Agregar cualquier texto restante después de la última lista
            if (lastIndex < tempText.length) {
                parts.push(tempText.substring(lastIndex));
            }
            htmlText = parts.join('');
        }

        return htmlText;
    }


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

        bubbleContent.innerHTML = parseMarkdown(message);
        chatDisplay.appendChild(messageDiv);
        chatDisplay.scrollTop = chatDisplay.scrollHeight; // Scroll al final
    }

    // Función para mostrar el indicador de escritura
    function showTypingIndicator() {
        // Mueve el indicador al final del chatDisplay para asegurar que aparezca abajo
        chatDisplay.appendChild(typingIndicator); 
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
            button.classList.add('btn', 'btn-primary', 'bg-[var(--secondary-blue)]', 'border-0', 'text-white', 'fw-bold', 'py-2', 'px-3', 'rounded-3', 'transition-colors', 'duration-200', 'fs-6', 'quick-reply-button'); // Added quick-reply-button class
            button.innerText = text;
            button.addEventListener('click', () => {
                // Al hacer clic, enviar el texto del botón directamente a sendMessage
                sendMessage(text);
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
    // Ahora acepta un parámetro 'messageToSend' para permitir el envío desde botones
    async function sendMessage(messageToSend = null) {
        const message = messageToSend || userInput.value.trim(); // Usa messageToSend si existe, de lo contrario, el input
        if (!message) return;

        addMessage(message, true); // Mostrar mensaje del usuario
        userInput.value = ''; // Limpiar input SOLO si no vino de un botón
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
                const errorBody = await response.text();
                console.error('Server responded with an error:', response.status, errorBody);
                throw new Error(`HTTP error! status: ${response.status}. Details: ${errorBody.substring(0, 200)}...`);
            }

            const data = await response.json();
            
            hideTypingIndicator(); // Ocultar el indicador después de recibir la respuesta del bot
            addMessage(data.bot_response, false); // Mostrar respuesta del bot

            // Lógica para mostrar botones de respuesta rápida basados en la respuesta del bot
            if (data.quick_replies && data.quick_replies.length > 0) {
                addQuickReplyButtons(data.quick_replies);
            } else if (data.bot_response.includes("¡Hola! Soy IngeChat 360°")) {
                addQuickReplyButtons(["Ingeniería de Sistemas", "Ingeniería Mecánica", "Ingeniería Eléctrica", "Ingeniería de Telecomunicaciones", "Requisitos de Inscripción"]);
            } else if (data.bot_response.includes("Ingeniería de Sistemas") && !data.bot_response.includes("plan de estudios")) { // Check for general career info, not pensum
                addQuickReplyButtons(["Pensum de Sistemas", "Perfil del Egresado de Sistemas", "Salidas Profesionales de Sistemas", "Duración de la carrera de Ingeniería de Sistemas"]);
            }
            // Añade más condiciones para otras carreras o temas específicos
            else if (data.bot_response.includes("Ingeniería Mecánica") && !data.bot_response.includes("plan de estudios")) {
                addQuickReplyButtons(["Pensum de Mecánica", "Perfil del Egresado de Mecánica", "Salidas Profesionales de Mecánica", "Duración de la carrera de Ingeniería Mecánica"]);
            }
            else if (data.bot_response.includes("Ingeniería Eléctrica") && !data.bot_response.includes("plan de estudios")) {
                addQuickReplyButtons(["Pensum de Eléctrica", "Perfil del Egresado de Eléctrica", "Salidas Profesionales de Eléctrica", "Duración de la carrera de Ingeniería Eléctrica"]);
            }
            else if (data.bot_response.includes("Ingeniería en Telecomunicaciones") && !data.bot_response.includes("plan de estudios")) {
                addQuickReplyButtons(["Pensum de Telecomunicaciones", "Perfil del Egresado de Telecomunicaciones", "Salidas Profesionales de Telecomunicaciones", "Duración de la carrera de Ingeniería en Telecomunicaciones"]);
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
            addMessage(data.bot_response, false); // Usa la respuesta del bot para el mensaje inicial
            // Si la respuesta de reinicio tiene quick_replies, úsalos
            if (data.quick_replies && data.quick_replies.length > 0) {
                addQuickReplyButtons(data.quick_replies);
            } else {
                // De lo contrario, usa los botones por defecto
                addQuickReplyButtons(["Ingeniería de Sistemas", "Ingeniería Mecánica", "Ingeniería Eléctrica", "Ingeniería de Telecomunicaciones", "Requisitos de Inscripción"]);
            }

        } catch (error) {
            console.error('Error al reiniciar chat:', error);
            addMessage('Lo siento, hubo un error al reiniciar el chat.', false);
        } finally {
            userInput.focus();
        }
    }

    // Event Listeners
    sendMessageBtn.addEventListener('click', () => sendMessage()); // Llama a sendMessage sin parámetros
    userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage(); // Llama a sendMessage sin parámetros
        }
    });
    restartChatBtn.addEventListener('click', restartChat);

    // Mostrar botones iniciales al cargar la página
    // Llamar a restartChat para que el backend inicie una nueva sesión y envíe el mensaje inicial con botones.
    // Esto asegura que la lógica del backend se sincronice con el frontend desde el principio.
    restartChat(); 
});
