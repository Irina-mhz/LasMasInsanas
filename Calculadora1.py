import tkinter as tk

root = tk.Tk()
root.title('Calculadora')
root.geometry('380x520')
root.resizable(False, False)
root.configure(bg='#f7f7f7')

# Paleta de colores
color_texto = '#000000'
color_boton = "#ffc8dd"
color_boton_op = "#bde0fe"
color_boton_igual = '#a2d2ff'
color_boton_clear = "#ffafcc"
color_boton_off = "#cdb4db"

# Variables de estado
expression = ''
history_text_var = tk.StringVar(value='')
screen_text = tk.StringVar(value='0')
calculadora_encendida = True
resultado_mostrado = False

# --- Frame de Pantalla (Historial + Pantalla Principal) ---
display_frame = tk.Frame(root, bg='#ffffff', bd=2, relief='sunken')
display_frame.grid(row=0, column=0, columnspan=4, sticky='nsew', padx=10, pady=10)

# Visor Secundario (Historial previo)
history_label = tk.Label(
    display_frame, textvariable=history_text_var,
    font=('Arial', 11), bg='#ffffff', fg='#777777', anchor='e', padx=10
)
history_label.pack(fill='x', pady=(5, 0))

# Visor Principal
screen_label = tk.Label(
    display_frame, textvariable=screen_text,
    font=('Arial', 24, 'bold'), bg='#ffffff', fg=color_texto, anchor='e', padx=10
)
screen_label.pack(fill='x', pady=(0, 5))


def get_current_operand(exp):
    """Obtiene el último número que se está escribiendo."""
    for ch in reversed(exp):
        if ch in "+-*/":
            return exp[exp.rfind(ch) + 1:]
    return exp


def press_num(char):
    global expression, resultado_mostrado
    if not calculadora_encendida:
        return

    if resultado_mostrado:
        expression = ''
        resultado_mostrado = False

    # Validación de punto decimal único
    if char == '.':
        current_op = get_current_operand(expression)
        if '.' in current_op:
            return
        if current_op == '':
            expression += '0.'
            screen_text.set(expression)
            return

    if expression == '0' and char != '.':
        expression = str(char)
    else:
        expression += str(char)

    screen_text.set(expression)


def press_operator(op):
    global expression, resultado_mostrado
    if not calculadora_encendida:
        return

    if resultado_mostrado:
        resultado_mostrado = False

    if expression == "":
        if op == "-":
            expression = "-"
            screen_text.set(expression)
        return

    if expression[-1] in "+-*/":
        expression = expression[:-1] + op
    else:
        expression += op

    screen_text.set(expression)


def toggle_sign():
    """Cambia el signo del valor actual (+/-)."""
    global expression, resultado_mostrado
    if not calculadora_encendida or not expression or expression == '0':
        return

    try:
        current_val = float(expression) if resultado_mostrado else float(screen_text.get())
        current_val = -current_val
        formatted = f"{current_val:.2f}" if current_val % 1 != 0 else f"{int(current_val)}"
        expression = formatted
        screen_text.set(expression)
    except ValueError:
        pass


def apply_percentage():
    """Aplica la función de porcentaje (%) sobre el número en pantalla."""
    global expression, resultado_mostrado
    if not calculadora_encendida or not expression:
        return

    try:
        current_val = float(expression) if resultado_mostrado else float(screen_text.get())
        percent_val = current_val / 100.0
        formatted = f"{percent_val:.2f}"
        expression = formatted
        screen_text.set(expression)
        resultado_mostrado = True
    except ValueError:
        pass


def calcular_izq_a_der(exp):
    """Evalúa la expresión secuencialmente de izquierda a derecha."""
    tokens = []
    num = ""
    i = 0

    if exp.startswith("-"):
        num = "-"
        i = 1

    while i < len(exp):
        ch = exp[i]
        if ch.isdigit() or ch == ".":
            num += ch
        else:
            if num != "":
                tokens.append(num)
                num = ""
            tokens.append(ch)
        i += 1
    if num != "":
        tokens.append(num)

    if not tokens or tokens[-1] in "+-*/":
        return None, "Error de sintaxis"

    try:
        result = float(tokens[0])
        idx = 1
        while idx < len(tokens) - 1:
            op = tokens[idx]
            val = float(tokens[idx + 1])
            if op == "+":
                result += val
            elif op == "-":
                result -= val
            elif op == "*":
                result *= val
            elif op == "/":
                if val == 0:
                    return None, "Error: División por cero"
                result /= val
            idx += 2
        return result, None
    except Exception:
        return None, "Error"


def equalpress():
    global expression, resultado_mostrado
    if not calculadora_encendida or not expression or resultado_mostrado:
        return

    result, err = calcular_izq_a_der(expression)
    if err:
        screen_text.set(err)
        expression = ''
        resultado_mostrado = True
        return

    formatted_result = f"{result:.2f}"
    history_text_var.set(f"{expression} = {formatted_result}")
    screen_text.set(formatted_result)
    expression = formatted_result
    resultado_mostrado = True


def clear():
    global expression, resultado_mostrado
    if not calculadora_encendida:
        return
    expression = ''
    resultado_mostrado = False
    screen_text.set('0')


def backspace():
    global expression, resultado_mostrado
    if not calculadora_encendida:
        return
    if resultado_mostrado:
        clear()
        return

    expression = expression[:-1]
    screen_text.set(expression if expression else '0')


def apagar():
    global calculadora_encendida, expression, resultado_mostrado
    calculadora_encendida = False
    expression = ''
    resultado_mostrado = False
    history_text_var.set('')
    screen_text.set('OFF')


def encender():
    global calculadora_encendida, expression, resultado_mostrado
    calculadora_encendida = True
    expression = ''
    resultado_mostrado = False
    history_text_var.set('')
    screen_text.set('0')


# --- Distribución de Teclado Clásico con % y +/- ---
layout = [
    [("ON", "on"), ("OFF", "off"), ("C", "clear"), ("CE", "back")],
    [("%", "pct"), ("+/-", "sign"), ("/", "op"), ("*", "op")],
    [("7", "num"), ("8", "num"), ("9", "num"), ("-", "op")],
    [("4", "num"), ("5", "num"), ("6", "num"), ("+", "op")],
    [("1", "num"), ("2", "num"), ("3", "num"), ("=", "equal")],
    [("0", "num"), (".", "num"), ("", "empty"), ("", "empty")]
]

for r, fila in enumerate(layout, start=1):
    for c, (texto, tipo) in enumerate(fila):
        if tipo == "empty":
            continue

        # Span del botón 0 para cubrir espacio
        colspan = 2 if texto == "0" else 1

        if tipo == "num":
            boton = tk.Button(
                root, text=texto, font=('Arial', 14, 'bold'),
                bg=color_boton, fg=color_texto, relief='flat',
                command=lambda t=texto: press_num(t)
            )
        elif tipo in ("op", "pct", "sign"):
            cmd = press_operator if tipo == "op" else (apply_percentage if tipo == "pct" else toggle_sign)
            boton = tk.Button(
                root, text=texto, font=('Arial', 14, 'bold'),
                bg=color_boton_op, fg=color_texto, relief='flat',
                command=lambda op=texto, c=cmd: c(op) if tipo == "op" else c()
            )
        elif tipo == "equal":
            boton = tk.Button(
                root, text=texto, font=('Arial', 14, 'bold'),
                bg=color_boton_igual, fg=color_texto, relief='flat',
                command=equalpress
            )
        elif tipo in ("clear", "back"):
            boton = tk.Button(
                root, text=texto, font=('Arial', 12, 'bold'),
                bg=color_boton_clear, fg=color_texto, relief='flat',
                command=clear if tipo == "clear" else backspace
            )
        elif tipo in ("on", "off"):
            boton = tk.Button(
                root, text=texto, font=('Arial', 11, 'bold'),
                bg=color_boton_off, fg=color_texto, relief='flat',
                command=encender if tipo == "on" else apagar
            )

        boton.grid(row=r, column=c, columnspan=colspan, padx=4, pady=4, sticky="nsew")

# Configurar expansión de rejilla
root.grid_rowconfigure(0, weight=2)
for i in range(1, 7):
    root.grid_rowconfigure(i, weight=1)
for j in range(4):
    root.grid_columnconfigure(j, weight=1)

root.mainloop()