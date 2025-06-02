# src/gui/chat_bubble.py
import tkinter as tk
from tkinter import ttk
from PIL import Image, ImageTk # Asegúrate de haber instalado Pillow
import textwrap

class ChatBubble(tk.Frame):
    # Añade chat_area_bg al constructor con un valor por defecto
    def __init__(self, parent, text, is_user, avatar_path=None, chat_area_bg="#F8F8F8", *args, **kwargs):
        super().__init__(parent, *args, **kwargs)

        self.is_user = is_user
        # Usa el color de fondo del área de chat para el propio Frame de la burbuja
        self.config(bg=chat_area_bg) 

        bubble_color = "#DCF8C6" if is_user else "#E0E0E0" # Verde para usuario, gris para bot
        text_color = "black"

        # Marco para la burbuja de texto
        self.bubble_frame = tk.Frame(self, bg=bubble_color, bd=0, relief="flat", padx=8, pady=5)
        
        # Etiqueta de texto con ajuste de línea
        wrapped_text = "\n".join(textwrap.wrap(text, width=70)) # Ajustar 'width' según tu diseño

        self.message_label = tk.Label(
            self.bubble_frame,
            text=wrapped_text,
            bg=bubble_color,
            fg=text_color,
            font=("Arial", 10), # Puedes usar la fuente personalizada si la cargas con TTK
            wraplength=450, # Ancho máximo del texto antes de saltar de línea
            justify=tk.LEFT if not is_user else tk.RIGHT # Alineación del texto dentro de la burbuja
        )
        self.message_label.pack(side=tk.LEFT, padx=5, pady=2, anchor=tk.W if not is_user else tk.E) # Centrar verticalmente

        if avatar_path:
            try:
                img = Image.open(avatar_path)
                img = img.resize((30, 30), Image.LANCZOS) # Redimensionar avatar
                self.avatar_photo = ImageTk.PhotoImage(img)
                # Usa el color de fondo del área de chat para la etiqueta del avatar
                self.avatar_label = tk.Label(self, image=self.avatar_photo, bg=chat_area_bg) 
                self.avatar_label.image = self.avatar_photo # Mantener referencia
            except Exception as e:
                print(f"Error loading avatar {avatar_path}: {e}")
                self.avatar_label = None
        else:
            self.avatar_label = None

        if is_user:
            # Usuario: burbuja a la derecha, avatar a la derecha
            self.bubble_frame.pack(side=tk.RIGHT, fill=tk.BOTH, expand=False, padx=(5, 5), pady=2)
            if self.avatar_label:
                self.avatar_label.pack(side=tk.RIGHT, padx=(0, 5))
            self.pack(fill=tk.X, padx=10, pady=2, anchor=tk.E)
        else:
            # Bot: burbuja a la izquierda, avatar a la izquierda
            self.bubble_frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=False, padx=(5, 5), pady=2)
            if self.avatar_label:
                self.avatar_label.pack(side=tk.LEFT, padx=(5, 0))
            self.pack(fill=tk.X, padx=10, pady=2, anchor=tk.W)

        self.bubble_frame.bind("<Configure>", self._on_frame_configure)
        self.bubble_frame.update_idletasks() # Asegurar que el tamaño se calcule

    def _on_frame_configure(self, event=None):
        pass

