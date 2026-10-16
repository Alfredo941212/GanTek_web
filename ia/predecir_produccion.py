import json
import sys


def regresion_lineal(valores):
    n = len(valores)

    x = list(range(n))
    y = [float(valor) for valor in valores]

    promedio_x = sum(x) / n
    promedio_y = sum(y) / n

    numerador = sum(
        (x[i] - promedio_x) * (y[i] - promedio_y)
        for i in range(n)
    )

    denominador = sum(
        (x[i] - promedio_x) ** 2
        for i in range(n)
    )

    if denominador == 0:
        pendiente = 0
    else:
        pendiente = numerador / denominador

    intercepto = promedio_y - (pendiente * promedio_x)

    predicciones = [
        intercepto + pendiente * valor_x
        for valor_x in x
    ]

    suma_residuos = sum(
        (y[i] - predicciones[i]) ** 2
        for i in range(n)
    )

    suma_total = sum(
        (valor - promedio_y) ** 2
        for valor in y
    )

    if suma_total == 0:
        r2 = 1.0
    else:
        r2 = 1 - (suma_residuos / suma_total)

    return pendiente, intercepto, r2


def predecir_produccion(registros):
    if len(registros) < 7:
        return {
            "success": False,
            "message": (
                "Se requieren al menos 7 registros "
                "para realizar una predicción."
            )
        }

    registros = [float(valor) for valor in registros]

    pendiente, intercepto, r2 = regresion_lineal(registros)

    siguiente_dia = len(registros)

    prediccion = (
        intercepto
        + pendiente * siguiente_dia
    )

    tendencia = "estable"

    if pendiente > 0.1:
        tendencia = "ascendente"

    elif pendiente < -0.1:
        tendencia = "descendente"
        

    calidad_ajuste = "baja"

    if r2 >= 0.80:
        calidad_ajuste = "alta"
    elif r2 >= 0.50:
        calidad_ajuste = "media"

    return {
        "success": True,
        "prediccion": round(prediccion, 2),
        "tendencia": tendencia,
        "pendiente": round(pendiente, 4),
        "r2": round(r2, 4),
        "calidad_ajuste": calidad_ajuste,
        "registros_utilizados": len(registros),
        "modelo": "regresion_lineal"
    }


def main():
    try:
        entrada = sys.stdin.read()

        datos = json.loads(entrada)

        registros = datos.get("registros", [])

        resultado = predecir_produccion(registros)

        print(json.dumps(resultado))

    except Exception as error:
        print(json.dumps({
            "success": False,
            "message": str(error)
        }))


if __name__ == "__main__":
    main()