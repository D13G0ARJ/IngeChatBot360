# src/gui/main_window.py
import customtkinter as ctk
import tkinter as tk # Mantener para PhotoImage y Canvas (si ScrollableFrame lo usa)
from PIL import Image, ImageTk
import os
from src.core.chatbot_logic import ChatbotLogic
# NOTA IMPORTANTE: chat_bubble.py y scrollable_frame.py TAMBIÉN DEBEN SER ACTUALIZADOS
# para usar CustomTkinter internamente para una apariencia consistente.
from src.gui.chat_bubble import ChatBubble
from src.gui.scrollable_frame import ScrollableFrame

class MainWindow(ctk.CTk):
    def __init__(self):
        super().__init__()
        self.title("IngeChat 360° - UNEFA")
        self.geometry("800x700")
        self.minsize(600, 600)

        # Configurar modo de apariencia (claro/oscuro) y tema por defecto de CustomTkinter
        ctk.set_appearance_mode("light") # Puede ser "System", "Dark", "Light"
        ctk.set_default_color_theme("blue") # "blue", "dark-blue", "green" (ajusta si quieres un azul por defecto de ctk)

        # Definir colores específicos de UNEFA (Azul Rey y Blanco)
        self.primary_blue = "#003366"  # Azul Rey oscuro principal (para cabecera, botones hover)
        self.secondary_blue = "#4169E1" # Azul Rey estándar (para botones, bordes)
        self.accent_blue_light = "#6495ED" # Azul más claro para acentos/botones activos
        self.white_color = "#FFFFFF"   # Blanco (para fondos de chat, texto en oscuro)
        self.light_gray_bg = "#F0F0F0" # Gris claro (para fondo general de la ventana)
        self.chat_bg_color = "#FFFFFF" # Color de fondo del área de chat
        self.text_color_dark = "#333333" # Color de texto oscuro
        self.text_color_light = "#FFFFFF" # Color de texto claro (para fondos azules)
        self.border_color_subtle = "#D0D0D0" # Color de borde sutil

        self.chatbot = ChatbotLogic()
        self.user_avatar_path = os.path.join("assets", "images", "user_avatar.png")
        self.bot_avatar_path = os.path.join("assets", "images", "bot_avatar.png")
        self.unefa_logo_path = os.path.join("assets", "images", "logo_unefa.png")
        self.send_icon_path = os.path.join("assets", "images", "send_icon.png")

        self._load_assets()
        self._create_widgets()
        self._initial_message()

    def _load_assets(self):
        """Carga y procesa imágenes para la interfaz."""
        try:
            # Logo UNEFA
            unefa_logo_pil = Image.open(self.unefa_logo_path)
            
            # Obtener el tamaño original de la imagen
            original_width, original_height = unefa_logo_pil.size
            
            # Definir una altura máxima deseada para el logo
            max_logo_height = 50 # Puedes ajustar esta altura según sea necesario
            
            # Calcular el nuevo ancho manteniendo la relación de aspecto
            new_width = int(original_width * (max_logo_height / original_height))
            
            # Crear CTkImage con el nuevo tamaño calculado
            self.unefa_logo_ctk = ctk.CTkImage(light_image=unefa_logo_pil,
                                               dark_image=unefa_logo_pil, # Usar la misma imagen para modo oscuro/claro
                                               size=(new_width, max_logo_height)) # TAMAÑO AJUSTADO AQUÍ DINÁMICAMENTE

            # Icono de enviar
            if os.path.exists(self.send_icon_path):
                send_icon_pil = Image.open(self.send_icon_path)
                self.send_icon_ctk = ctk.CTkImage(light_image=send_icon_pil,
                                                  dark_image=send_icon_pil,
                                                  size=(24, 24))
            else:
                self.send_icon_ctk = None
                print(f"Advertencia: No se encontró el icono de enviar en {self.send_icon_path}")

        except Exception as e:
            print(f"Error al cargar assets: {e}")
            self.unefa_logo_ctk = None
            self.send_icon_ctk = None

    def _create_widgets(self):
        """Crea y posiciona todos los widgets de la interfaz."""
        # Contenedor principal para la UI
        main_frame = ctk.CTkFrame(self, fg_color=self.light_gray_bg) # Fondo gris claro
        main_frame.pack(fill=ctk.BOTH, expand=True, padx=15, pady=15) # Espaciado alrededor

        # --- Header ---
        header_frame = ctk.CTkFrame(main_frame, fg_color=self.primary_blue, corner_radius=10) # Fondo azul rey oscuro, esquinas redondeadas
        header_frame.pack(fill=ctk.X, pady=(15, 15)) # Aumentado el pady del header_frame para más espacio vertical

        if self.unefa_logo_ctk: # Usar el objeto CTkImage
            logo_label = ctk.CTkLabel(header_frame, image=self.unefa_logo_ctk, text="") # Etiqueta para el logo
            logo_label.pack(side=ctk.LEFT, padx=(15, 10), pady=0) # Ajustado padx y pady para el logo

        title_label = ctk.CTkLabel(header_frame, text="IngeChat 360°",
                                   font=ctk.CTkFont("Arial", 20, "bold"),
                                   text_color=self.text_color_light) # Texto blanco
        title_label.pack(side=ctk.LEFT, expand=True, fill=ctk.X, pady=0) # Ajustado pady para el título

        # Botón para reiniciar chat
        restart_btn = ctk.CTkButton(header_frame, text="Reiniciar Chat", command=self._restart_chat,
                                    fg_color=self.accent_blue_light, # Azul claro
                                    hover_color=self.secondary_blue, # Azul estándar al pasar el mouse
                                    text_color=self.white_color,
                                    corner_radius=8)
        restart_btn.pack(side=ctk.RIGHT, padx=15, pady=0) # Ajustado padx y pady para el botón

        # --- Área de Mensajes (ScrollableFrame) ---
        # NOTE: Asegúrate de que ScrollableFrame usa ctk.CTkFrame internamente
        self.chat_display_frame = ScrollableFrame(main_frame, fg_color=self.chat_bg_color) # Fondo blanco para el chat
        self.chat_display_frame.pack(fill=ctk.BOTH, expand=True, pady=10, padx=5)

        # --- Área de Entrada de Usuario ---
        input_frame = ctk.CTkFrame(main_frame, fg_color=self.white_color, corner_radius=10, border_width=1, border_color=self.border_color_subtle)
        input_frame.pack(fill=ctk.X, pady=(10, 0))

        self.user_input = ctk.CTkEntry(
            input_frame,
            font=ctk.CTkFont("Arial", 11),
            placeholder_text="Escribe tu mensaje...", # Texto de ayuda
            fg_color=self.white_color,
            text_color=self.text_color_dark,
            border_color=self.secondary_blue, # Borde azul
            corner_radius=8,
            border_width=1
        )
        self.user_input.pack(side=ctk.LEFT, fill=ctk.X, expand=True, padx=(10, 10), pady=10)
        self.user_input.bind("<Return>", self._send_message_event)

        send_button = ctk.CTkButton(
            input_frame,
            text="Enviar" if not self.send_icon_ctk else "", # Usar send_icon_ctk
            image=self.send_icon_ctk, # Usar send_icon_ctk
            command=self._send_message,
            compound=ctk.LEFT if self.send_icon_ctk else ctk.NONE,
            fg_color=self.secondary_blue, # Azul estándar
            hover_color=self.primary_blue, # Azul oscuro al pasar el mouse
            text_color=self.white_color,
            corner_radius=8
        )
        send_button.pack(side=ctk.RIGHT, padx=(0, 10), pady=10)

    def _initial_message(self):
        """Muestra el mensaje de bienvenida del chatbot."""
        welcome_message = (
            "¡Hola! Soy IngeChat 360°, tu asistente virtual de la UNEFA Núcleo Miranda, Sede Los Teques. "
            "Estoy aquí para brindarte información detallada sobre las carreras de Ingeniería: "
            "Sistemas, Mecánica, Telecomunicaciones y Eléctrica.\n\n"
            "¿En qué carrera estás interesado hoy? O puedes preguntar sobre requisitos de inscripción, perfil del egresado, etc."
        )
        self._add_message(welcome_message, is_user=False)

    def _add_message(self, message, is_user):
        """Agrega un mensaje al área de visualización del chat."""
        # NOTE: Asegúrate de que ChatBubble usa ctk.CTkFrame y ctk.CTkLabel internamente
        bubble = ChatBubble(
            self.chat_display_frame.frame, # Este es el frame interno de ScrollableFrame
            message,
            is_user,
            # Pasar la imagen PIL directamente, ChatBubble la convertirá a CTkImage
            avatar_path=self.user_avatar_path if is_user else self.bot_avatar_path,
            chat_area_bg=self.chat_bg_color # Pasa el color de fondo del área de chat
        )
        bubble.pack(fill=ctk.X, pady=5, padx=5, anchor=ctk.NW if not is_user else ctk.NE)

        # Asegurarse de que el scroll vaya hasta el final
        # Esta parte aún depende del tk.Canvas subyacente en ScrollableFrame.
        self.chat_display_frame.canvas.update_idletasks()
        self.chat_display_frame.canvas.yview_moveto(1.0)


    def _send_message_event(self, event):
        """Maneja el evento de enviar mensaje al presionar Enter."""
        self._send_message()

    def _send_message(self):
        """Envía el mensaje del usuario y muestra la respuesta del chatbot."""
        user_text = self.user_input.get().strip()
        if not user_text:
            return

        self.user_input.delete(0, ctk.END)
        self._add_message(user_text, is_user=True)

        bot_response = self.chatbot.process_message(user_text)
        self._add_message(bot_response, is_user=False)

    def _restart_chat(self):
        """Reinicia la conversación del chatbot."""
        for widget in self.chat_display_frame.frame.winfo_children():
            widget.destroy()
        self.chatbot.start_new_chat_session()
        self._initial_message()
        print("Chat reiniciado.")

