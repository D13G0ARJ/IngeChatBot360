# src/gui/chat_bubble.py
import tkinter as tk # Mantener para PhotoImage (aunque ahora usaremos CTkImage)
from PIL import Image, ImageTk # Asegúrate de haber instalado Pillow
import textwrap
import customtkinter as ctk # Importar CustomTkinter

class ChatBubble(ctk.CTkFrame): # Cambiado de tk.Frame a ctk.CTkFrame
    def __init__(self, parent, text, is_user, avatar_path=None, chat_area_bg="#F8F8F8", *args, **kwargs):
        # Usar fg_color para el fondo del CTkFrame
        super().__init__(parent, fg_color=chat_area_bg, *args, **kwargs)

        self.is_user = is_user
        
        # Colores de la burbuja (pueden ser definidos en main_window y pasados si se desea mayor consistencia)
        bubble_color = "#DCF8C6" if is_user else "#E0E0E0" # Verde claro para usuario, gris claro para bot
        text_color = "black"

        # Marco para la burbuja de texto (también CTkFrame)
        # Ajustar corner_radius para un aspecto más suave
        self.bubble_frame = ctk.CTkFrame(self, fg_color=bubble_color, corner_radius=12) # Aumentado a 12 para más suavidad
        
        # Etiqueta de texto con ajuste de línea (CTkLabel)
        # Ajustar 'width' para el textwrap para controlar el ancho de la burbuja
        # Usar un wraplength más grande para que el texto no se corte tan rápido
        # El ancho del texto es crucial para la legibilidad
        wrapped_text = "\n".join(textwrap.wrap(text, width=60)) # Ajustar 'width' para mejor legibilidad

        self.message_label = ctk.CTkLabel( # Cambiado de tk.Label a ctk.CTkLabel
            self.bubble_frame,
            text=wrapped_text,
            fg_color=bubble_color, # Usar fg_color para el fondo
            text_color=text_color, # Usar text_color para el color del texto
            font=ctk.CTkFont("Arial", 11), # Ligeramente más grande para mejor lectura
            wraplength=400, # Ancho máximo del texto antes de saltar de línea (ajustado para que quepa bien)
            justify=ctk.LEFT if not is_user else ctk.RIGHT # Alineación del texto dentro de la burbuja
        )
        # Añadir padding interno a la etiqueta del mensaje para que el texto no toque los bordes
        self.message_label.pack(side=ctk.LEFT, padx=10, pady=8, anchor=ctk.W if not is_user else ctk.E) # Aumentado padx y pady

        if avatar_path:
            try:
                # Cargar la imagen PIL y luego convertirla a CTkImage
                avatar_pil = Image.open(avatar_path)
                self.avatar_ctk_image = ctk.CTkImage(light_image=avatar_pil,
                                                     dark_image=avatar_pil,
                                                     size=(30, 30)) # Redimensionar con CTkImage
                
                # Usar el objeto CTkImage con CTkLabel
                self.avatar_label = ctk.CTkLabel(self, image=self.avatar_ctk_image, text="", fg_color=chat_area_bg) 
            except Exception as e:
                print(f"Error loading avatar {avatar_path}: {e}")
                self.avatar_label = None
        else:
            self.avatar_label = None

        if is_user:
            # Usuario: burbuja a la derecha, avatar a la derecha
            # Ajustar padx para que la burbuja no esté pegada al borde
            self.bubble_frame.pack(side=ctk.RIGHT, fill=ctk.BOTH, expand=False, padx=(5, 15), pady=2) # Más padding a la derecha
            if self.avatar_label:
                self.avatar_label.pack(side=ctk.RIGHT, padx=(0, 5))
            self.pack(fill=ctk.X, padx=10, pady=2, anchor=ctk.E)
        else:
            # Bot: burbuja a la izquierda, avatar a la izquierda
            # Ajustar padx para que la burbuja no esté pegada al borde
            self.bubble_frame.pack(side=ctk.LEFT, fill=ctk.BOTH, expand=False, padx=(15, 5), pady=2) # Más padding a la izquierda
            if self.avatar_label:
                self.avatar_label.pack(side=ctk.LEFT, padx=(5, 0))
            self.pack(fill=ctk.X, padx=10, pady=2, anchor=ctk.W)

