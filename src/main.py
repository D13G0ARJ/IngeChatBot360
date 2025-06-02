# src/main.py
from src.gui.main_window import MainWindow
import logging

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

if __name__ == "__main__":
    app = MainWindow()
    app.mainloop()