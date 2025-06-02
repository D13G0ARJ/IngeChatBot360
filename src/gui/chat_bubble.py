# src/gui/chat_bubble.py
import tkinter as tk
from PIL import Image, ImageTk
import textwrap
import customtkinter as ctk

class ChatBubble(ctk.CTkFrame):
    def __init__(self, parent, text, is_user, avatar_path=None, chat_area_bg="#F8F8F8", *args, **kwargs):
        super().__init__(parent, fg_color=chat_area_bg, *args, **kwargs)

        self.is_user = is_user
        self.chat_area_bg = chat_area_bg # Guardar para futuras actualizaciones de tema

        # Colores de la burbuja y texto para modo CLARO y OSCURO
        self.bubble_light_user_color = "#DCF8C6"  # Verde claro para usuario (modo claro)
        self.bubble_dark_user_color = "#004D40"   # Verde oscuro para usuario (modo oscuro, un tono más profundo)
        self.bubble_light_bot_color = "#E0E0E0"   # Gris claro para bot (modo claro)
        self.bubble_dark_bot_color = "#424242"    # Gris oscuro para bot (modo oscuro, un tono más oscuro)
        
        self.text_light_color = "black"
        self.text_dark_color = "white"

        # Determinar colores iniciales de la burbuja y texto
        current_mode = ctk.get_appearance_mode()
        self._set_bubble_colors(current_mode)

        self.bubble_frame = ctk.CTkFrame(self, fg_color=self.current_bubble_color, corner_radius=12)
        
        self.message_label = ctk.CTkLabel(
            self.bubble_frame,
            text=text,
            fg_color=self.current_bubble_color,
            text_color=self.current_text_color,
            font=ctk.CTkFont("Arial", 11),
            wraplength=700,
            justify=ctk.LEFT if not is_user else ctk.RIGHT
        )
        self.message_label.pack(side=ctk.LEFT, padx=10, pady=8, anchor=ctk.W if not is_user else ctk.E)

        if avatar_path:
            try:
                avatar_pil = Image.open(avatar_path)
                self.avatar_ctk_image = ctk.CTkImage(light_image=avatar_pil,
                                                     dark_image=avatar_pil,
                                                     size=(30, 30))
                self.avatar_label = ctk.CTkLabel(self, image=self.avatar_ctk_image, text="", fg_color=self.chat_area_bg) 
            except Exception as e:
                print(f"Error loading avatar {avatar_path}: {e}")
                self.avatar_label = None
        else:
            self.avatar_label = None

        # Definir padding inicial y final para la animación
        # AJUSTADO: initial_slide_padding para que se deslice desde más lejos, pero con más pasos
        self.initial_slide_padding = 180 # Distancia desde donde "entra" la burbuja (antes 150)
        self.final_slide_padding = 15 # Padding final deseado en el lado de la animación

        if is_user:
            self.bubble_frame.pack(side=ctk.RIGHT, fill=ctk.BOTH, expand=True, padx=(5, self.final_slide_padding), pady=2)
            if self.avatar_label:
                self.avatar_label.pack(side=ctk.RIGHT, padx=(0, 5))
            self.pack(fill=ctk.X, padx=(self.initial_slide_padding, 10), pady=2, anchor=ctk.E)
        else:
            self.bubble_frame.pack(side=ctk.LEFT, fill=ctk.BOTH, expand=True, padx=(self.final_slide_padding, 5), pady=2)
            if self.avatar_label:
                self.avatar_label.pack(side=ctk.LEFT, padx=(5, 0))
            self.pack(fill=ctk.X, padx=(10, self.initial_slide_padding), pady=2, anchor=ctk.W)

        # Atributos para la animación
        # AJUSTADO: animation_step para movimientos más pequeños y suaves
        self.animation_step = 3 # Cuántos píxeles se mueve en cada paso (antes 5)
        # AJUSTADO: animation_delay para que la animación sea más lenta y suave
        self.animation_delay = 30 # Retardo en milisegundos entre pasos (antes 20)
        self.animation_id = None

    def _set_bubble_colors(self, mode):
        """Establece los colores de la burbuja y el texto según el modo (Light/Dark)."""
        if self.is_user:
            self.current_bubble_color = self.bubble_light_user_color if mode == "Light" else self.bubble_dark_user_color
        else:
            self.current_bubble_color = self.bubble_light_bot_color if mode == "Light" else self.bubble_dark_bot_color
        
        self.current_text_color = self.text_light_color if mode == "Light" else self.text_dark_color

    def update_theme_colors(self, new_chat_area_bg, current_mode):
        """Actualiza los colores de la burbuja cuando cambia el tema."""
        self.chat_area_bg = new_chat_area_bg
        self.configure(fg_color=self.chat_area_bg)

        if self.avatar_label:
            self.avatar_label.configure(fg_color=self.chat_area_bg)

        self._set_bubble_colors(current_mode)
        self.bubble_frame.configure(fg_color=self.current_bubble_color)
        self.message_label.configure(fg_color=self.current_bubble_color, text_color=self.current_text_color)


    def start_animation(self):
        """Inicia la animación de deslizamiento de la burbuja."""
        if self.is_user:
            self._animate_slide_in(current_padding=self.initial_slide_padding, target_padding=10, side_to_animate="left")
        else:
            self._animate_slide_in(current_padding=self.initial_slide_padding, target_padding=10, side_to_animate="right")

    def _animate_slide_in(self, current_padding, target_padding, side_to_animate):
        """
        Realiza la animación de deslizamiento de la burbuja.
        Reduce el padding del lado de la animación hasta alcanzar el objetivo.
        """
        if self.animation_id:
            self.after_cancel(self.animation_id)

        if side_to_animate == "left":
            if current_padding > target_padding:
                new_padding = max(target_padding, current_padding - self.animation_step)
                self.pack_configure(padx=(new_padding, 10))
                self.animation_id = self.after(self.animation_delay, self._animate_slide_in, new_padding, target_padding, side_to_animate)
            else:
                self.pack_configure(padx=(target_padding, 10))
        elif side_to_animate == "right":
            if current_padding > target_padding:
                new_padding = max(target_padding, current_padding - self.animation_step)
                self.pack_configure(padx=(10, new_padding))
                self.animation_id = self.after(self.animation_delay, self._animate_slide_in, new_padding, target_padding, side_to_animate)
            else:
                self.pack_configure(padx=(10, target_padding))
