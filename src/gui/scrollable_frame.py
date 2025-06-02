# src/gui/scrollable_frame.py
import tkinter as tk
import customtkinter as ctk # Importar CustomTkinter

class ScrollableFrame(ctk.CTkFrame): # Cambiado de ttk.Frame a ctk.CTkFrame
    def __init__(self, parent, fg_color="#F8F8F8", *args, **kwargs): # Añadir fg_color para el fondo
        super().__init__(parent, fg_color=fg_color, *args, **kwargs)

        self.canvas = tk.Canvas(self, borderwidth=0, background=fg_color, highlightthickness=0) # Fondo del área de chat, highlightthickness=0 para quitar borde
        self.frame = ctk.CTkFrame(self.canvas, fg_color=fg_color) # Cambiado de ttk.Frame a ctk.CTkFrame
        self.vsb = ctk.CTkScrollbar(self, orientation="vertical", command=self.canvas.yview) # Cambiado de ttk.Scrollbar a ctk.CTkScrollbar
        self.canvas.configure(yscrollcommand=self.vsb.set)

        self.vsb.pack(side="right", fill="y")
        self.canvas.pack(side="left", fill="both", expand=True)
        self.canvas.create_window((4, 4), window=self.frame, anchor="nw",
                                  tags="self.frame")

        self.frame.bind("<Configure>", self.on_frame_configure)
        self.canvas.bind("<Configure>", self.on_canvas_configure)

        # Habilitar scroll con la rueda del ratón
        self.canvas.bind_all("<MouseWheel>", self._on_mousewheel) # Windows y macOS
        self.canvas.bind_all("<Button-4>", self._on_mousewheel)   # Linux (scroll up)
        self.canvas.bind_all("<Button-5>", self._on_mousewheel)   # Linux (scroll down)


    def on_frame_configure(self, event):
        """Reset the scroll region to encompass the inner frame"""
        self.canvas.configure(scrollregion=self.canvas.bbox("all"))

    def on_canvas_configure(self, event):
        """Resize the frame to fill the canvas width"""
        canvas_width = event.width
        self.canvas.itemconfig("self.frame", width=canvas_width)

    def _on_mousewheel(self, event):
        if self.canvas.winfo_containing(event.x_root, event.y_root) == self.canvas:
            if event.num == 5 or event.delta == -120: # Scroll down
                self.canvas.yview_scroll(1, "units")
            elif event.num == 4 or event.delta == 120: # Scroll up
                self.canvas.yview_scroll(-1, "units")
