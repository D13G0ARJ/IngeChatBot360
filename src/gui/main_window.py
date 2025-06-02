# src/gui/main_window.py
import tkinter as tk
from tkinter import ttk, PhotoImage
from PIL import Image, ImageTk
import os
from src.core.chatbot_logic import ChatbotLogic
from src.gui.chat_bubble import ChatBubble
from src.gui.scrollable_frame import ScrollableFrame

class MainWindow(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("IngeChat 360° - UNEFA")
        self.geometry("800x700") # Tamaño inicial de la ventana
        self.minsize(600, 600) # Tamaño mínimo
        self.configure(bg="#F0F2F5") # Color de fondo general

        self.chatbot = ChatbotLogic()
        self.user_avatar_path = os.path.join("assets", "images", "user_avatar.png")
        self.bot_avatar_path = os.path.join("assets", "images", "bot_avatar.png")
        self.unefa_logo_path = os.path.join("assets", "images", "logo_unefa.png")
        self.send_icon_path = os.path.join("assets", "images", "send_icon.png") # Necesitarás un icono de enviar

        self._load_assets()
        self._apply_styles() # Aplicar estilos antes de crear widgets para que estén disponibles
        self._create_widgets()
        self._initial_message()

    def _load_assets(self):
        """Carga y procesa imágenes para la interfaz."""
        try:
            # Logo UNEFA
            self.unefa_logo_img = Image.open(self.unefa_logo_path)
            self.unefa_logo_img = self.unefa_logo_img.resize((150, 40), Image.LANCZOS)
            self.unefa_logo_tk = ImageTk.PhotoImage(self.unefa_logo_img)

            # Icono de enviar (si existe)
            if os.path.exists(self.send_icon_path):
                self.send_icon_img = Image.open(self.send_icon_path)
                self.send_icon_img = self.send_icon_img.resize((24, 24), Image.LANCZOS)
                self.send_icon_tk = ImageTk.PhotoImage(self.send_icon_img)
            else:
                self.send_icon_tk = None # No hay icono
                print(f"Advertencia: No se encontró el icono de enviar en {self.send_icon_path}")

        except Exception as e:
            print(f"Error al cargar assets: {e}")
            self.unefa_logo_tk = None
            self.send_icon_tk = None

    def _create_widgets(self):
        # Contenedor principal para la UI
        main_frame = ttk.Frame(self, padding="15", style="Main.TFrame")
        main_frame.pack(fill=tk.BOTH, expand=True)

        # --- Header ---
        header_frame = ttk.Frame(main_frame, style="Header.TFrame")
        header_frame.pack(fill=tk.X, pady=(0, 10))

        if self.unefa_logo_tk:
            # CORRECCIÓN AQUÍ: Usar el color literal definido en _apply_styles para Header.TFrame
            logo_label = tk.Label(header_frame, image=self.unefa_logo_tk, bg="#FFFFFF") 
            logo_label.pack(side=tk.LEFT, padx=(0, 10))
        
        title_label = ttk.Label(header_frame, text="IngeChat 360°", font=("Arial", 20, "bold"), style="Title.TLabel")
        title_label.pack(side=tk.LEFT, expand=True, fill=tk.X)
        
        # Botón para reiniciar chat
        restart_btn = ttk.Button(header_frame, text="Reiniciar Chat", command=self._restart_chat, style="Accent.TButton")
        restart_btn.pack(side=tk.RIGHT)

        # --- Área de Mensajes (ScrollableFrame) ---
        self.chat_display_frame = ScrollableFrame(main_frame, style="ChatDisplay.TFrame")
        self.chat_display_frame.pack(fill=tk.BOTH, expand=True, pady=10)

        # --- Área de Entrada de Usuario ---
        input_frame = ttk.Frame(main_frame, style="Input.TFrame")
        input_frame.pack(fill=tk.X, pady=(10, 0))

        self.user_input = ttk.Entry(
            input_frame,
            font=("Arial", 11),
            style="Input.TEntry"
        )
        self.user_input.pack(side=tk.LEFT, fill=tk.X, expand=True, padx=(0, 10))
        self.user_input.bind("<Return>", self._send_message_event) # Enviar con Enter

        send_button = ttk.Button(
            input_frame,
            text="Enviar" if not self.send_icon_tk else "",
            image=self.send_icon_tk, # Mostrar icono si está cargado
            command=self._send_message,
            compound=tk.LEFT if self.send_icon_tk else tk.NONE, # Icono a la izquierda del texto
            style="Send.TButton"
        )
        send_button.pack(side=tk.RIGHT)

        # Mover _apply_styles() al final del __init__ o antes de _create_widgets()
        # para asegurar que los estilos estén definidos cuando se crean los widgets.
        # Ya lo moví al __init__ para ti.

    def _apply_styles(self):
        """Define y aplica estilos personalizados con ttk.Style."""
        style = ttk.Style(self)
        
        # Colores base
        primary_color = "#4CAF50"  # Verde UNEFA (ejemplo)
        secondary_color = "#388E3C" # Verde más oscuro
        background_light = "#F8F8F8"
        background_dark = "#E0E0E0"
        text_color_dark = "#333333"
        text_color_light = "#FFFFFF"

        # General
        style.configure("TFrame", background=self.cget('bg'))
        style.configure("TLabel", background=self.cget('bg'), foreground=text_color_dark)
        style.configure("TButton", 
                        font=("Arial", 10, "bold"), 
                        background=primary_color, 
                        foreground=text_color_light,
                        padding=8)
        style.map("TButton", 
                  background=[('active', secondary_color)],
                  foreground=[('active', text_color_light)])
        
        # Estilos específicos para la ventana principal
        style.configure("Main.TFrame", background="#F0F2F5") # Fondo de la ventana
        
        # Header
        style.configure("Header.TFrame", background="#FFFFFF", relief="solid", borderwidth=1, bordercolor="#E0E0E0")
        style.configure("Title.TLabel", foreground=primary_color, background="#FFFFFF")
        style.configure("Accent.TButton", background="#FFC107", foreground="black") # Botón de acento
        style.map("Accent.TButton", background=[('active', "#FFA000")])

        # Chat Display Frame (ScrollableFrame)
        style.configure("ChatDisplay.TFrame", background=background_light, relief="flat") # Fondo del área de chat
        style.configure("ChatDisplay.TScrollbar", background=primary_color, troughcolor=background_dark)
        
        # Input Frame
        style.configure("Input.TFrame", background="#FFFFFF", relief="solid", borderwidth=1, bordercolor="#E0E0E0")
        style.configure("Input.TEntry", 
                        fieldbackground="#FFFFFF", 
                        foreground=text_color_dark, 
                        bordercolor=primary_color, 
                        borderwidth=1, 
                        relief="flat",
                        padding=(5, 5))
        style.map("Input.TEntry", bordercolor=[('focus', secondary_color)])

        style.configure("Send.TButton", background=primary_color, foreground=text_color_light, padding=(5,5))
        style.map("Send.TButton", background=[('active', secondary_color)])
        
        # Configurar un tema si se usa archivo .tcl
        # self.tk.call("source", os.path.join("assets", "styles", "custom_theme.tcl"))
        # style.theme_use("IngeChatTheme") # Si se ha creado un tema TTK custom

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
        # Obtener el color de fondo del área de chat (ScrollableFrame)
        # Este color es el background_light (#F8F8F8) definido en _apply_styles
        chat_area_bg_color = "#F8F8F8" 

        bubble = ChatBubble(
            self.chat_display_frame.frame,
            message,
            is_user,
            avatar_path=self.user_avatar_path if is_user else self.bot_avatar_path,
            chat_area_bg=chat_area_bg_color # Pasar el color de fondo del área de chat
        )
        bubble.pack(fill=tk.X, pady=5, padx=5, anchor=tk.NW if not is_user else tk.NE)
        
        # Asegurarse de que el scroll vaya hasta el final
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

        self.user_input.delete(0, tk.END)
        self._add_message(user_text, is_user=True)

        # Obtener respuesta del chatbot (puede tomar tiempo, ejecutar en hilo si es muy lento)
        # Para Tkinter, es mejor evitar operaciones bloqueantes en el hilo principal.
        # Para este caso, la llamada a Gemini es una solicitud de red, lo que podría congelar la UI.
        # Si la latencia es un problema, se debería usar threading.Thread o asyncio (con ttk.asyncio).
        
        # Ejemplo simple (bloqueante):
        bot_response = self.chatbot.process_message(user_text)
        self._add_message(bot_response, is_user=False)

    def _restart_chat(self):
        """Reinicia la conversación del chatbot."""
        for widget in self.chat_display_frame.frame.winfo_children():
            widget.destroy() # Eliminar todos los mensajes previos
        self.chatbot.start_new_chat_session()
        self._initial_message()
        print("Chat reiniciado.")

