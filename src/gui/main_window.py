# src/gui/main_window.py
import customtkinter as ctk
import tkinter as tk
from PIL import Image, ImageTk
import os
import threading
import time

from src.core.chatbot_logic import ChatbotLogic
from src.gui.chat_bubble import ChatBubble
from src.gui.scrollable_frame import ScrollableFrame

class MainWindow(ctk.CTk):
    def __init__(self):
        super().__init__()
        self.title("IngeChat 360° - UNEFA")
        self.geometry("800x700")
        self.minsize(600, 600)

        ctk.set_appearance_mode("light") 
        ctk.set_default_color_theme("blue") 

        self.primary_blue = "#003366"  
        self.secondary_blue = "#4169E1" 
        self.accent_blue_light = "#6495ED" 
        self.white_color = "#FFFFFF"   
        self.light_gray_bg = "#F0F0F0" 
        self.chat_bg_color = "#FFFFFF" 
        self.text_color_dark = "#333333" 
        self.text_color_light = "#FFFFFF" 
        self.border_color_subtle = "#D0D0D0" 

        self.chatbot = ChatbotLogic()
        self.user_avatar_path = os.path.join("assets", "images", "user_avatar.png")
        self.bot_avatar_path = os.path.join("assets", "images", "bot_avatar.png")
        self.unefa_logo_path = os.path.join("assets", "images", "logo_unefa.png")
        self.send_icon_path = os.path.join("assets", "images", "send_icon.png")

        self.typing_indicator_visible = False
        self.typing_dots = []
        self.typing_animation_id = None

        self._load_assets()
        self._create_widgets()
        self._initial_message()
        
    def _load_assets(self):
        """Carga y procesa imágenes para la interfaz."""
        try:
            unefa_logo_pil = Image.open(self.unefa_logo_path)
            original_width, original_height = unefa_logo_pil.size
            max_logo_height = 50 
            new_width = int(original_width * (max_logo_height / original_height))
            self.unefa_logo_ctk = ctk.CTkImage(light_image=unefa_logo_pil,
                                               dark_image=unefa_logo_pil,
                                               size=(new_width, max_logo_height))

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
        main_frame = ctk.CTkFrame(self, fg_color=self.light_gray_bg)
        main_frame.pack(fill=ctk.BOTH, expand=True, padx=15, pady=15)

        header_frame = ctk.CTkFrame(main_frame, fg_color=self.primary_blue, corner_radius=10)
        header_frame.pack(fill=ctk.X, pady=(15, 15))

        if self.unefa_logo_ctk:
            logo_label = ctk.CTkLabel(header_frame, image=self.unefa_logo_ctk, text="")
            logo_label.pack(side=ctk.LEFT, padx=(15, 10), pady=0)

        title_label = ctk.CTkLabel(header_frame, text="IngeChat 360°",
                                   font=ctk.CTkFont("Arial", 20, "bold"),
                                   text_color=self.text_color_light)
        title_label.pack(side=ctk.LEFT, expand=True, fill=ctk.X, pady=0)

        restart_btn = ctk.CTkButton(header_frame, text="Reiniciar Chat", command=self._restart_chat,
                                    fg_color=self.accent_blue_light, 
                                    hover_color=self.secondary_blue, 
                                    text_color=self.white_color,
                                    corner_radius=8)
        restart_btn.pack(side=ctk.RIGHT, padx=15, pady=0)

        self.chat_display_frame = ScrollableFrame(main_frame, fg_color=self.chat_bg_color)
        self.chat_display_frame.pack(fill=ctk.BOTH, expand=True, pady=10, padx=5)

        self.typing_indicator_frame = ctk.CTkFrame(self.chat_display_frame.frame, fg_color="transparent")
        self.typing_indicator_label = ctk.CTkLabel(self.typing_indicator_frame, text="IngeChat 360° está escribiendo", 
                                                   font=ctk.CTkFont("Arial", 10, weight="normal", slant="italic"), 
                                                   text_color="gray")
        self.typing_indicator_label.pack(side=ctk.LEFT, padx=(5,2), pady=5)
        
        for i in range(3):
            dot = ctk.CTkLabel(self.typing_indicator_frame, text="•", font=ctk.CTkFont("Arial", 14, "bold"), text_color="gray")
            dot.pack(side=ctk.LEFT, pady=5)
            self.typing_dots.append(dot)
        
        self.typing_indicator_frame.pack_forget()

        input_frame = ctk.CTkFrame(main_frame, fg_color=self.white_color, corner_radius=10, border_width=1, border_color=self.border_color_subtle)
        input_frame.pack(fill=ctk.X, pady=(10, 0))

        self.user_input = ctk.CTkEntry(
            input_frame,
            font=ctk.CTkFont("Arial", 11),
            placeholder_text="Escribe tu mensaje...",
            fg_color=self.white_color,
            text_color=self.text_color_dark,
            border_color=self.secondary_blue,
            corner_radius=8,
            border_width=1
        )
        self.user_input.pack(side=ctk.LEFT, fill=ctk.X, expand=True, padx=(10, 10), pady=10)
        self.user_input.bind("<Return>", self._send_message_event)

        send_button = ctk.CTkButton(
            input_frame,
            text="Enviar" if not self.send_icon_ctk else "",
            image=self.send_icon_ctk,
            command=self._send_message,
            compound=ctk.LEFT if self.send_icon_ctk else ctk.NONE,
            fg_color=self.secondary_blue,
            hover_color=self.primary_blue,
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
        bubble = ChatBubble(
            self.chat_display_frame.frame,
            message,
            is_user,
            avatar_path=self.user_avatar_path if is_user else self.bot_avatar_path,
            chat_area_bg=self.chat_bg_color
        )
        bubble.pack(fill=ctk.X, pady=5, padx=5, anchor=ctk.NW if not is_user else ctk.NE)
        self.chat_display_frame.canvas.update_idletasks()
        self.chat_display_frame.canvas.yview_moveto(1.0)
        
        # Iniciar la animación de la burbuja después de añadirla
        bubble.start_animation() # ESTA ES LA LÍNEA CLAVE

    def _show_typing_indicator(self):
        if not self.typing_indicator_visible:
            self.typing_indicator_frame.pack(side=ctk.LEFT, fill=ctk.X, expand=True, padx=10, pady=5)
            self.typing_indicator_visible = True
            self._animate_typing_dots(0)

        self.chat_display_frame.canvas.update_idletasks()
        self.chat_display_frame.canvas.yview_moveto(1.0)

    def _hide_typing_indicator(self):
        if self.typing_indicator_visible:
            self.typing_indicator_frame.pack_forget()
            self.typing_indicator_visible = False
            if self.typing_animation_id:
                self.after_cancel(self.typing_animation_id)
                self.typing_animation_id = None
            for dot in self.typing_dots:
                dot.configure(text_color="gray")

    def _animate_typing_dots(self, dot_index):
        if not self.typing_indicator_visible:
            return

        for i, dot in enumerate(self.typing_dots):
            if i == dot_index:
                dot.configure(text_color=self.secondary_blue)
            else:
                dot.configure(text_color="gray")
        
        next_dot_index = (dot_index + 1) % len(self.typing_dots)
        self.typing_animation_id = self.after(300, self._animate_typing_dots, next_dot_index)


    def _send_message_event(self, event):
        self._send_message()

    def _send_message(self):
        user_text = self.user_input.get().strip()
        if not user_text:
            return

        self.user_input.delete(0, ctk.END)
        self._add_message(user_text, is_user=True)

        self.user_input.configure(state=ctk.DISABLED)

        self._show_typing_indicator()

        threading.Thread(target=self._process_bot_response, args=(user_text,)).start()

    def _process_bot_response(self, user_text):
        bot_response = self.chatbot.process_message(user_text)
        self.after(100, self._update_ui_with_bot_response, bot_response)

    def _update_ui_with_bot_response(self, bot_response):
        self._hide_typing_indicator()
        self._add_message(bot_response, is_user=False)
        
        self.user_input.configure(state=ctk.NORMAL)
        self.user_input.focus_set()

    def _restart_chat(self):
        for widget in self.chat_display_frame.frame.winfo_children():
            if widget != self.typing_indicator_frame:
                widget.destroy()
        self.chatbot.start_new_chat_session()
        self._hide_typing_indicator()
        self._initial_message()
        print("Chat reiniciado.")

