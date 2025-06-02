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

        # Configurar modo de apariencia inicial (claro)
        ctk.set_appearance_mode("light") 
        ctk.set_default_color_theme("blue") # Tema por defecto de CustomTkinter

        self.primary_blue = "#003366"  
        self.secondary_blue = "#4169E1" 
        self.accent_blue_light = "#6495ED" 
        self.white_color = "#FFFFFF"   
        self.border_color_subtle = "#D0D0D0" 

        # Colores que cambiarán con el tema (se actualizarán en _update_theme_colors)
        # Inicializados aquí, pero sus valores se establecerán correctamente en _update_theme_colors
        self.dynamic_bg_color = ""
        self.dynamic_text_color = ""
        self.chat_area_dynamic_bg = ""
        self.typing_indicator_dynamic_color = ""

        self.chatbot = ChatbotLogic()
        self.user_avatar_path = os.path.join("assets", "images", "user_avatar.png")
        self.bot_avatar_path = os.path.join("assets", "images", "bot_avatar.png")
        self.unefa_logo_path = os.path.join("assets", "images", "logo_unefa.png")
        self.send_icon_path = os.path.join("assets", "images", "send_icon.png")

        self.typing_indicator_visible = False
        self.typing_dots = []
        self.typing_animation_id = None

        self._load_assets()
        # CORRECCIÓN CLAVE: Llamar a _update_theme_colors ANTES de _create_widgets
        self._update_theme_colors() 
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
        # Usar colores dinámicos para fondos que cambian con el tema
        self.main_frame = ctk.CTkFrame(self, fg_color=self.dynamic_bg_color) 
        self.main_frame.pack(fill=ctk.BOTH, expand=True, padx=15, pady=15)

        header_frame = ctk.CTkFrame(self.main_frame, fg_color=self.primary_blue, corner_radius=10)
        header_frame.pack(fill=ctk.X, pady=(15, 15))

        if self.unefa_logo_ctk:
            logo_label = ctk.CTkLabel(header_frame, image=self.unefa_logo_ctk, text="")
            logo_label.pack(side=ctk.LEFT, padx=(15, 10), pady=0)

        title_label = ctk.CTkLabel(header_frame, text="IngeChat 360°",
                                   font=ctk.CTkFont("Arial", 20, "bold"),
                                   text_color=self.white_color)
        title_label.pack(side=ctk.LEFT, expand=True, fill=ctk.X, pady=0)

        restart_btn = ctk.CTkButton(header_frame, text="Reiniciar Chat", command=self._restart_chat,
                                    fg_color=self.accent_blue_light, 
                                    hover_color=self.secondary_blue, 
                                    text_color=self.white_color,
                                    corner_radius=8)
        restart_btn.pack(side=ctk.RIGHT, padx=(0, 15), pady=0)

        # Selector de Tema (Modo Claro/Oscuro)
        self.appearance_mode_switch = ctk.CTkSwitch(
            header_frame, 
            text="Modo Oscuro", 
            command=self._change_appearance_mode_event,
            button_color=self.secondary_blue,
            button_hover_color=self.primary_blue,
            progress_color=self.accent_blue_light,
            text_color=self.white_color,
            font=ctk.CTkFont("Arial", 10, "bold")
        )
        self.appearance_mode_switch.pack(side=ctk.RIGHT, padx=(0, 15), pady=0)

        # Usar color dinámico para el fondo del área de chat
        self.chat_display_frame = ScrollableFrame(self.main_frame, fg_color=self.chat_area_dynamic_bg)
        self.chat_display_frame.pack(fill=ctk.BOTH, expand=True, pady=10, padx=5)

        self.typing_indicator_frame = ctk.CTkFrame(self.chat_display_frame.frame, fg_color="transparent")
        self.typing_indicator_label = ctk.CTkLabel(self.typing_indicator_frame, text="IngeChat 360° está escribiendo", 
                                                   font=ctk.CTkFont("Arial", 10, weight="normal", slant="italic"), 
                                                   text_color=self.typing_indicator_dynamic_color) # Color dinámico
        self.typing_indicator_label.pack(side=ctk.LEFT, padx=(5,2), pady=5)
        
        for i in range(3):
            dot = ctk.CTkLabel(self.typing_indicator_frame, text="•", font=ctk.CTkFont("Arial", 14, "bold"), text_color=self.typing_indicator_dynamic_color) # Color dinámico
            dot.pack(side=ctk.LEFT, pady=5)
            self.typing_dots.append(dot)
        
        self.typing_indicator_frame.pack_forget()

        # Usar colores dinámicos para el campo de entrada
        self.input_frame = ctk.CTkFrame(self.main_frame, fg_color=self.chat_area_dynamic_bg, corner_radius=10, border_width=1, border_color=self.border_color_subtle)
        self.input_frame.pack(fill=ctk.X, pady=(10, 0))

        self.user_input = ctk.CTkEntry(
            self.input_frame,
            font=ctk.CTkFont("Arial", 11),
            placeholder_text="Escribe tu mensaje...",
            fg_color=self.chat_area_dynamic_bg, # Fondo del campo de entrada dinámico
            text_color=self.dynamic_text_color, # Color del texto dinámico
            border_color=self.secondary_blue,
            corner_radius=8,
            border_width=1
        )
        self.user_input.pack(side=ctk.LEFT, fill=ctk.X, expand=True, padx=(10, 10), pady=10)
        self.user_input.bind("<Return>", self._send_message_event)

        send_button = ctk.CTkButton(
            self.input_frame,
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
            chat_area_bg=self.chat_area_dynamic_bg # Pasar el color dinámico del área de chat
        )
        bubble.pack(fill=ctk.X, pady=5, padx=5, anchor=ctk.NW if not is_user else ctk.NE)
        self.chat_display_frame.canvas.update_idletasks()
        self.chat_display_frame.canvas.yview_moveto(1.0)
        
        bubble.start_animation()

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
                dot.configure(text_color=self.typing_indicator_dynamic_color)

    def _animate_typing_dots(self, dot_index):
        if not self.typing_indicator_visible:
            return

        for i, dot in enumerate(self.typing_dots):
            if i == dot_index:
                dot.configure(text_color=self.secondary_blue)
            else:
                dot.configure(text_color=self.typing_indicator_dynamic_color)
        
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

    def _update_theme_colors(self):
        """Actualiza los colores de los widgets que deben cambiar con el tema."""
        current_mode = ctk.get_appearance_mode()
        if current_mode == "Light":
            self.dynamic_bg_color = "#F0F0F0" # Gris claro para fondo general
            self.dynamic_text_color = "#333333" # Texto oscuro
            self.chat_area_dynamic_bg = "#FFFFFF" # Fondo de chat blanco
            self.typing_indicator_dynamic_color = "gray" # Gris para indicador
        else: # Dark mode
            self.dynamic_bg_color = "#2B2B2B" # Gris oscuro para fondo general
            self.dynamic_text_color = "#FFFFFF" # Texto blanco
            self.chat_area_dynamic_bg = "#343638" # Fondo de chat oscuro
            self.typing_indicator_dynamic_color = "#A0A0A0" # Gris claro para indicador

        # Aplicar los colores a los widgets
        # Solo aplicar si los widgets ya han sido creados
        if hasattr(self, 'main_frame'):
            self.main_frame.configure(fg_color=self.dynamic_bg_color)
            self.chat_display_frame.configure(fg_color=self.chat_area_dynamic_bg)
            self.chat_display_frame.canvas.configure(background=self.chat_area_dynamic_bg) # Actualizar canvas
            self.chat_display_frame.frame.configure(fg_color=self.chat_area_dynamic_bg) # Actualizar frame interno del scrollable
            
            self.typing_indicator_label.configure(text_color=self.typing_indicator_dynamic_color)
            for dot in self.typing_dots:
                dot.configure(text_color=self.typing_indicator_dynamic_color)

            self.input_frame.configure(fg_color=self.chat_area_dynamic_bg)
            self.user_input.configure(fg_color=self.chat_area_dynamic_bg, text_color=self.dynamic_text_color)

            # Recorrer las burbujas existentes y actualizar sus colores
            for widget in self.chat_display_frame.frame.winfo_children():
                if isinstance(widget, ChatBubble):
                    widget.update_theme_colors(self.chat_area_dynamic_bg, current_mode)


    def _change_appearance_mode_event(self):
        """Cambia el modo de apariencia (claro/oscuro) de la aplicación."""
        if self.appearance_mode_switch.get() == 1: # Si el switch está encendido (modo oscuro)
            ctk.set_appearance_mode("dark")
            self.appearance_mode_switch.configure(text="Modo Claro")
        else: # Si el switch está apagado (modo claro)
            ctk.set_appearance_mode("light")
            self.appearance_mode_switch.configure(text="Modo Oscuro")
        
        self._update_theme_colors() # Llamar para actualizar los colores de los widgets

    def _restart_chat(self):
        for widget in self.chat_display_frame.frame.winfo_children():
            if widget != self.typing_indicator_frame:
                widget.destroy()
        self.chatbot.start_new_chat_session()
        self._hide_typing_indicator()
        self._initial_message()
        print("Chat reiniciado.")

