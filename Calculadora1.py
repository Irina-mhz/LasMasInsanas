import tkinter as tk

root = tk.Tk()
root.title('Calculadora')
root.geometry('380x520')
root.resizable(False, False)
root.configure(bg='#f7f7f7')

# Paleta de colores original
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

# Visor Secundario (Historial de la operación previa - RF-05)
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

    # Si se acaba de mostrar un resultado, empezar uno nuevo al presionar un dígito
    if resultado_mostrado:
        expression = ''
        resultado_mostrado = False

    # Validación de punto decimal único (RF-03)
    if char == '.':
        current_op = get_current_operand(expression)
        if '.' in current_op:
            return
        if current_op == '':
            expression += '0.'
            screen_text.set(expression)
            return

    # Si la expresión era solo '0' y entra otro número
    if expression == '0' and char != '.':
        expression = str(char)
    else:
        expression += str(char)

    screen_text.set(expression)


def press_operator(op):
    global expression, resultado_mostrado
    if not calculadora_encendida:
        return

    # Si venimos de un resultado, encadenamos el cálculo (RF-07)
    if resultado_mostrado:
        resultado_mostrado = False

    if expression == "":
        if op == "-":
            expression = "-"
            screen_text.set(expression)
        return

    # Reemplazar operador si el último carácter ya era uno
    if expression[-1] in "+-*/":
        expression = expression[:-1] + op
    else:
        expression += op

    screen_text.set(expression)


def calcular_izq_a_der(exp):
    """Evalúa la expresión secuencialmente con manejo de división por cero (RF-01, RF-04)."""
    tokens = []
    num = ""
    i = 0
    # Manejar si empieza con número negativo
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

    # Formato a 2 decimales (RF-02)
    formatted_result = f"{result:.2f}"

    # Guardar en el visor de historial previo (RF-05)
    history_text_var.set(f"{expression} = {formatted_result}")

    # Mostrar en pantalla principal
    screen_text.set(formatted_result)
    expression = formatted_result
    resultado_mostrado = True


def clear():
    """Borrado total (C) - RF-06."""
    global expression, resultado_mostrado
    if not calculadora_encendida:
        return
    expression = ''
    resultado_mostrado = False
    screen_text.set('0')


def backspace():
    """Borrado del último carácter (CE / Backspace) - RF-06."""
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


# --- Distribución de Teclado Clásico (RNF-02) ---
layout = [
    [("ON", "on"), ("OFF", "off"), ("C", "clear"), ("CE", "back")],
    [("7", "num"), ("8", "num"), ("9", "num"), ("/", "op")],
    [("4", "num"), ("5", "num"), ("6", "num"), ("*", "op")],
    [("1", "num"), ("2", "num"), ("3", "num"), ("-", "op")],
    [("0", "num"), (".", "num"), ("=", "equal"), ("+", "op")]
]

for r, fila in enumerate(layout, start=1):
    for c, (texto, tipo) in enumerate(fila):
        if tipo == "num":
            boton = tk.Button(
                root, text=texto, font=('Arial', 14, 'bold'),
                bg=color_boton, fg=color_texto, relief='flat',
                command=lambda t=texto: press_num(t)
            )
        elif tipo == "op":
            boton = tk.Button(
                root, text=texto, font=('Arial', 14, 'bold'),
                bg=color_boton_op, fg=color_texto, relief='flat',
                command=lambda t=texto: press_operator(t)
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
        elif tipo == "on":
            boton = tk.Button(
                root, text=texto, font=('Arial', 11, 'bold'),
                bg=color_boton_off, fg=color_texto, relief='flat',
                command=encender
            )
        elif tipo == "off":
            boton = tk.Button(
                root, text=texto, font=('Arial', 11, 'bold'),
                bg=color_boton_off, fg=color_texto, relief='flat',
                command=apagar
            )

        boton.grid(row=r, column=c, padx=4, pady=4, sticky="nsew")

# Configurar expansión de rejilla
root.grid_rowconfigure(0, weight=2)
for i in range(1, 6):
    root.grid_rowconfigure(i, weight=1)
for j in range(4):
    root.grid_columnconfigure(j, weight=1)

root.mainloop()