# assets/styles/custom_theme.tcl
# Este archivo define un tema personalizado para Tkinter.ttk

# Definir el nombre de tu tema
set theme_name "IngeChatTheme"

# Colores base de UNEFA (Azul Rey y Blanco)
set primary_blue "#003366"  ;# Azul Rey oscuro principal
set secondary_blue "#4169E1" ;# Azul Rey estándar, para acentos o elementos interactivos
set accent_blue_light "#6495ED" # Azul más claro para botones de acento
set white_color "#FFFFFF"
set light_gray "#F0F0F0"
set dark_gray_text "#333333"
set border_gray "#D0D0D0"

# Crear el nuevo tema
# El tema 'alt' es un buen punto de partida por su simplicidad.
ttk::theme create $theme_name parent alt

# Configurar estilos para el tema
$theme_name configure TFrame -background $white_color
$theme_name configure TLabel -background $white_color -foreground $dark_gray_text
$theme_name configure TButton -font {{Arial} 10 bold} -background $secondary_blue -foreground $white_color -padding {8 8}
$theme_name map TButton -background {active $primary_blue} -foreground {active $white_color}

# Estilos específicos de tu aplicación
# Main Frame
$theme_name configure Main.TFrame -background $light_gray

# Header Frame
$theme_name configure Header.TFrame -background $primary_blue -relief flat
$theme_name configure Title.TLabel -foreground $white_color -background $primary_blue

# Accent Button (Botón Reiniciar Chat)
$theme_name configure Accent.TButton -background $accent_blue_light -foreground $white_color
$theme_name map Accent.TButton -background {active $secondary_blue}

# Chat Display Frame (ScrollableFrame)
$theme_name configure ChatDisplay.TFrame -background $white_color -relief flat
$theme_name configure TScrollbar -background $secondary_blue -troughcolor $light_gray

# Input Frame
$theme_name configure Input.TFrame -background $white_color -relief solid -borderwidth 1 -bordercolor $border_gray
$theme_name configure Input.TEntry -fieldbackground $white_color -foreground $dark_gray_text -bordercolor $secondary_blue -borderwidth 1 -relief flat -padding {5 5}
$theme_name map Input.TEntry -bordercolor {focus $primary_blue}

# Send Button
$theme_name configure Send.TButton -background $secondary_blue -foreground $white_color -padding {5 5}
$theme_name map Send.TButton -background {active $primary_blue}

# Puedes añadir más configuraciones para otros widgets si los usas
# Por ejemplo:
# $theme_name configure TText -background $white_color -foreground $dark_gray_text -font {{Arial} 10}
