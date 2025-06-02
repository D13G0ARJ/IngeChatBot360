# src/gui/chat_bubble.py
import tkinter as tk
from PIL import Image, ImageTk
import textwrap
import customtkinter as ctk

class ChatBubble(ctk.CTkFrame):
    def __init__(self, parent, text, is_user, avatar_path=None, chat_area_bg="#F8F8F8", *args, **kwargs):
        super().__init__(parent, fg_color=chat_area_bg, *args, **kwargs)

        self.is_user = is_user
        
        bubble_color = "#DCF8C6" if is_user else "#E0E0E0"
        text_color = "black"

        self.bubble_frame = ctk.CTkFrame(self, fg_color=bubble_color, corner_radius=12)
        
        wrapped_text = "\n".join(textwrap.wrap(text, width=60))

        self.message_label = ctk.CTkLabel(
            self.bubble_frame,
            text=wrapped_text,
            fg_color=bubble_color,
            text_color=text_color,
            font=ctk.CTkFont("Arial", 11),
            wraplength=400,
            justify=ctk.LEFT if not is_user else ctk.RIGHT
        )
        self.message_label.pack(side=ctk.LEFT, padx=10, pady=8, anchor=ctk.W if not is_user else ctk.E)

        if avatar_path:
            try:
                avatar_pil = Image.open(avatar_path)
                self.avatar_ctk_image = ctk.CTkImage(light_image=avatar_pil,
                                                     dark_image=avatar_pil,
                                                     size=(30, 30))
                self.avatar_label = ctk.CTkLabel(self, image=self.avatar_ctk_image, text="", fg_color=chat_area_bg) 
            except Exception as e:
                print(f"Error loading avatar {avatar_path}: {e}")
                self.avatar_label = None
        else:
            self.avatar_label = None

        # Definir padding inicial y final para la animación
        self.initial_slide_padding = 80 # Distancia desde donde "entra" la burbuja
        self.final_slide_padding = 15 # Padding final deseado en el lado de la animación

        if is_user:
            # Usuario: burbuja a la derecha, avatar a la derecha
            self.bubble_frame.pack(side=ctk.RIGHT, fill=ctk.BOTH, expand=False, padx=(5, self.final_slide_padding), pady=2)
            if self.avatar_label:
                self.avatar_label.pack(side=ctk.RIGHT, padx=(0, 5))
            # Inicialmente, la burbuja del usuario se "desliza" desde la derecha (más padding a la izquierda)
            self.pack(fill=ctk.X, padx=(self.initial_slide_padding, 10), pady=2, anchor=ctk.E)
        else:
            # Bot: burbuja a la izquierda, avatar a la izquierda
            self.bubble_frame.pack(side=ctk.LEFT, fill=ctk.BOTH, expand=False, padx=(self.final_slide_padding, 5), pady=2)
            if self.avatar_label:
                self.avatar_label.pack(side=ctk.LEFT, padx=(5, 0))
            # Inicialmente, la burbuja del bot se "desliza" desde la izquierda (más padding a la derecha)
            self.pack(fill=ctk.X, padx=(10, self.initial_slide_padding), pady=2, anchor=ctk.W)

        # Atributos para la animación
        self.animation_step = 5 # Cuántos píxeles se mueve en cada paso
        self.animation_delay = 10 # Retardo en milisegundos entre pasos
        self.animation_id = None

    def start_animation(self):
        """Inicia la animación de deslizamiento de la burbuja."""
        if self.is_user:
            # Animar el padding izquierdo para la burbuja del usuario (se reduce)
            self._animate_slide_in(current_padding=self.initial_slide_padding, target_padding=10, side_to_animate="left")
        else:
            # Animar el padding derecho para la burbuja del bot (se reduce)
            self._animate_slide_in(current_padding=self.initial_slide_padding, target_padding=10, side_to_animate="right")

    def _animate_slide_in(self, current_padding, target_padding, side_to_animate):
        """
        Realiza la animación de deslizamiento de la burbuja.
        Reduce el padding del lado de la animación hasta alcanzar el objetivo.
        """
        if self.animation_id:
            self.after_cancel(self.animation_id) # Cancelar animación previa si existe

        if side_to_animate == "left":
            if current_padding > target_padding:
                new_padding = max(target_padding, current_padding - self.animation_step)
                self.pack_configure(padx=(new_padding, 10)) # Ajustar el padding izquierdo
                self.animation_id = self.after(self.animation_delay, self._animate_slide_in, new_padding, target_padding, side_to_animate)
            else:
                self.pack_configure(padx=(target_padding, 10)) # Asegurar el padding final
        elif side_to_animate == "right":
            if current_padding > target_padding:
                new_padding = max(target_padding, current_padding - self.animation_step)
                self.pack_configure(padx=(10, new_padding)) # Ajustar el padding derecho
                self.animation_id = self.after(self.animation_delay, self._animate_slide_in, new_padding, target_padding, side_to_animate)
            else:
                self.pack_configure(padx=(10, target_padding)) # Asegurar el padding final
