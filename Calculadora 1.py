import tkinter as tk

root = tk.Tk()
root.title('Calculadora')
root.geometry('380x640')
root.resizable(False, False)

COLOR_VENTANA = "#17121d"
COLOR_BG_DISPLAY = "#100c14"
COLOR_TEXT_MAIN = "#fce7f3"
COLOR_TEXT_SEC = "#c4b5fd"
COLOR_BADGE_MEM = "#f472b6"

COLOR_BTN_NUM = "#2a1e35"
COLOR_TEXT_NUM = "#f5f3ff"

COLOR_BTN_OP = "#382347"
COLOR_TEXT_OP = "#fbcfe8"

COLOR_BTN_MEM = "#1f1730"
COLOR_TEXT_MEM = "#e9d5ff"

COLOR_BTN_FUNC = "#2e1c3b"
COLOR_TEXT_FUNC = "#fbcfe8"

COLOR_BTN_EQUAL = "#702459"
COLOR_TEXT_EQUAL = "#ffffff"

COLOR_BTN_SPECIAL = "#3b2038"
COLOR_TEXT_SPECIAL = "#fecdd3"

COLOR_ACTIVE_BG = "#4c2b5e"

root.configure(bg=COLOR_VENTANA)

expression = ''
history_text_var = tk.StringVar(value='')
screen_text = tk.StringVar(value='0')
memory_indicator_var = tk.StringVar(value='')
calculadora_encendida = True
resultado_mostrado = False
memory_value = 0.0

display_frame = tk.Frame(root, bg=COLOR_BG_DISPLAY, bd=1, relief='solid', highlightthickness=1, highlightbackground='#382347')
display_frame.grid(row=0, column=0, columnspan=4, sticky='nsew', padx=12, pady=10)

top_info_frame = tk.Frame(display_frame, bg=COLOR_BG_DISPLAY)
top_info_frame.pack(fill='x', padx=10, pady=(6, 0))

memory_badge = tk.Label(
    top_info_frame, textvariable=memory_indicator_var,
    font=('Segoe UI', 9, 'bold'), bg=COLOR_BG_DISPLAY, fg=COLOR_BADGE_MEM, anchor='w'
)
memory_badge.pack(side='left')

history_label = tk.Label(
    top_info_frame, textvariable=history_text_var,
    font=('Segoe UI', 10), bg=COLOR_BG_DISPLAY, fg=COLOR_TEXT_SEC, anchor='e'
)
history_label.pack(side='right', expand=True, fill='x')

screen_label = tk.Label(
    display_frame, textvariable=screen_text,
    font=('Segoe UI', 24, 'bold'), bg=COLOR_BG_DISPLAY, fg=COLOR_TEXT_MAIN, anchor='e', padx=10
)
screen_label.pack(fill='x', pady=(2, 6))

def get_current_operand(exp):
    for ch in reversed(exp):
        if ch in "+-*/":
            return exp[exp.rfind(ch) + 1:]
    return exp

def update_memory_indicator():
    memory_indicator_var.set("M" if memory_value != 0.0 else "")

def press_num(char):
    global expression, resultado_mostrado
    if not calculadora_encendida:
        return

    if resultado_mostrado:
        expression = ''
        resultado_mostrado = False

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
    global expression, resultado_mostrado
    if not calculadora_encendida or not expression or expression in ('0', '-'):
        return
    try:
        val = float(expression)
        val = -val
        expression = f"{val:.2f}" if val % 1 != 0 else f"{int(val)}"
        screen_text.set(expression)
    except ValueError:
        pass

def apply_percentage():
    global expression, resultado_mostrado
    if not calculadora_encendida or not expression:
        return
    try:
        val = float(expression)
        percent_val = val / 100.0
        expression = f"{percent_val:.2f}"
        screen_text.set(expression)
        resultado_mostrado = True
    except ValueError:
        pass

def calcular_izq_a_der(exp):
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

def get_active_number_value():
    global expression
    if not expression or expression in ("-", "Error de sintaxis", "Error: División por cero"):
        try:
            return float(screen_text.get())
        except ValueError:
            return 0.0
    res, err = calcular_izq_a_der(expression)
    if err is None and res is not None:
        return res
    try:
        return float(screen_text.get())
    except ValueError:
        return 0.0

def memory_recall():
    global expression, resultado_mostrado
    if not calculadora_encendida:
        return
    formatted_mem = f"{memory_value:.2f}"
    screen_text.set(formatted_mem)
    expression = formatted_mem
    resultado_mostrado = True

def memory_clear():
    global memory_value
    if not calculadora_encendida:
        return
    memory_value = 0.0
    update_memory_indicator()

def memory_add():
    global memory_value
    if not calculadora_encendida:
        return
    val = get_active_number_value()
    memory_value += val
    update_memory_indicator()

def memory_subtract():
    global memory_value
    if not calculadora_encendida:
        return
    val = get_active_number_value()
    memory_value -= val
    update_memory_indicator()

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

layout = [
    [("ON", "ctrl"), ("OFF", "ctrl"), ("C", "ctrl"), ("CE", "ctrl")],
    [("MC", "mem"),  ("MR", "mem"),   ("M-", "mem"), ("M+", "mem")],
    [("%", "func"),  ("+/-", "func"), ("/", "op"),   ("*", "op")],
    [("7", "num"),   ("8", "num"),    ("9", "num"),  ("-", "op")],
    [("4", "num"),   ("5", "num"),    ("6", "num"),  ("+", "op")],
    [("1", "num"),   ("2", "num"),    ("3", "num"),  ("=", "eq")],
    [("0", "num"),   (".", "num"),    ("", "none"),  ("", "none")]
]

btn_actions = {
    "ON": encender, "OFF": apagar, "C": clear, "CE": backspace,
    "MC": memory_clear, "MR": memory_recall, "M-": memory_subtract, "M+": memory_add,
    "%": apply_percentage, "+/-": toggle_sign,
    "=": equalpress
}

for r, fila in enumerate(layout, start=1):
    for c, (texto, tipo) in enumerate(fila):
        if tipo == "none":
            continue

        colspan = 2 if (r == 7 and texto == "0") else 1
        col_pos = 0 if (r == 7 and texto == "0") else (2 if (r == 7 and texto == ".") else c)

        if tipo == "ctrl":
            bg_col = COLOR_BTN_SPECIAL
            fg_col = COLOR_TEXT_SPECIAL
            action = btn_actions[texto]
        elif tipo == "mem":
            bg_col = COLOR_BTN_MEM
            fg_col = COLOR_TEXT_MEM
            action = btn_actions[texto]
        elif tipo == "func":
            bg_col = COLOR_BTN_FUNC
            fg_col = COLOR_TEXT_FUNC
            action = btn_actions[texto]
        elif tipo == "op":
            bg_col = COLOR_BTN_OP
            fg_col = COLOR_TEXT_OP
            action = lambda op=texto: press_operator(op)
        elif tipo == "eq":
            bg_col = COLOR_BTN_EQUAL
            fg_col = COLOR_TEXT_EQUAL
            action = equalpress
        else:
            bg_col = COLOR_BTN_NUM
            fg_col = COLOR_TEXT_NUM
            action = lambda t=texto: press_num(t)

        boton = tk.Button(
            root, text=texto, font=('Segoe UI', 12, 'bold'),
            bg=bg_col, fg=fg_col, relief='flat', bd=0,
            activebackground=COLOR_ACTIVE_BG, activeforeground="#ffffff",
            cursor="hand2", command=action
        )
        boton.grid(row=r, column=col_pos, columnspan=colspan, padx=3, pady=3, sticky="nsew")

root.grid_rowconfigure(0, weight=2)
for i in range(1, 8):
    root.grid_rowconfigure(i, weight=1)
for j in range(4):
    root.grid_columnconfigure(j, weight=1)

root.mainloop()