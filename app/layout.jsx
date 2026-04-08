import './globals.css';

export const metadata = {
  title: 'IngeChat 360° - UNEFA',
  description: 'Chatbot de orientación académica (UNEFA) con IA (Gemini) y datos locales.'
};

export default function RootLayout({ children }) {
  return (
    <html lang="es">
      <body>{children}</body>
    </html>
  );
}
