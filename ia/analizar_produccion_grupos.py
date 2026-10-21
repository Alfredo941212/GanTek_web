import sys
import json
from datetime import timedelta

import numpy as np
import pandas as pd

from sklearn.ensemble import (
    RandomForestRegressor,
    IsolationForest,
)

from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score,
)

def error(message):
    print(
        json.dumps(
            {
                "success": False,
                "message": message,
            },
            ensure_ascii=False,
        )
    )
    sys.exit(1)


def clasificar(litros):
    if litros < 10:
        return {
            "nivel": "anomalia_baja",
            "color": "rojo_oscuro",
            "tipo": "alerta",
            "mensaje": (
                "La producción está por debajo de 10 L. "
                "Se recomienda revisar salud, alimentación, "
                "condiciones del grupo o equipo de ordeña."
            ),
        }

    if litros < 20:
        return {
            "nivel": "baja",
            "color": "rojo",
            "tipo": "seguimiento",
            "mensaje": (
                "La producción se encuentra en un nivel bajo. "
                "Se recomienda dar seguimiento al grupo."
            ),
        }

    if litros < 50:
        return {
            "nivel": "media",
            "color": "amarillo",
            "tipo": "informativo",
            "mensaje": (
                "La producción se encuentra dentro "
                "del rango medio esperado."
            ),
        }

    if litros <= 75:
        return {
            "nivel": "buena",
            "color": "verde",
            "tipo": "positivo",
            "mensaje": (
                "La producción se mantiene dentro "
                "de un rango favorable."
            ),
        }

    return {
        "nivel": "anomalia_alta",
        "color": "morado",
        "tipo": "sugerencia",
        "mensaje": (
            "La producción supera 75 L. "
            "Se sugiere verificar si hubo cambios en "
            "alimentación, cantidad de vacas, manejo "
            "o condiciones excepcionales."
        ),
    }


def obtener_tendencia(valores):
    if len(valores) < 2:
        return "sin_datos"

    diferencia = valores[-1] - valores[0]

    if diferencia > 2:
        return "ascendente"

    if diferencia < -2:
        return "descendente"

    return "estable"


def preparar_dataframe(registros):
    df = pd.DataFrame(registros)

    columnas = [
        "grupo_id",
        "grupo",
        "fecha",
        "litros_totales",
        "cantidad_vacas",
        "duracion_minutos",
    ]

    faltantes = [
        columna
        for columna in columnas
        if columna not in df.columns
    ]

    if faltantes:
        error(
            "Faltan campos requeridos: "
            + ", ".join(faltantes)
        )

    df["fecha"] = pd.to_datetime(
        df["fecha"],
        errors="coerce",
    )

    df["litros_totales"] = pd.to_numeric(
        df["litros_totales"],
        errors="coerce",
    )

    df["cantidad_vacas"] = pd.to_numeric(
        df["cantidad_vacas"],
        errors="coerce",
    ).fillna(0)

    df["duracion_minutos"] = pd.to_numeric(
        df["duracion_minutos"],
        errors="coerce",
    ).fillna(0)

    df = df.dropna(
        subset=[
            "fecha",
            "litros_totales",
        ]
    )

    if df.empty:
        error(
            "No existen datos válidos para analizar."
        )

    df = df.sort_values(
        [
            "grupo_id",
            "fecha",
        ]
    ).reset_index(drop=True)

    fecha_minima = df["fecha"].min()

    df["dia_indice"] = (
        df["fecha"] - fecha_minima
    ).dt.days

    df["dia_semana"] = (
        df["fecha"].dt.dayofweek
    )

    df["litros_por_vaca"] = np.where(
        df["cantidad_vacas"] > 0,
        df["litros_totales"]
        / df["cantidad_vacas"],
        0,
    )

    return df, fecha_minima


def entrenar_random_forest(df):
    features = [
        "grupo_id",
        "dia_indice",
        "dia_semana",
        "cantidad_vacas",
        "duracion_minutos",
    ]

    # Orden temporal para evitar usar datos futuros
    # al evaluar registros anteriores.
    df_ordenado = df.sort_values(
        "fecha"
    ).reset_index(drop=True)

    total = len(df_ordenado)

    # 80 % entrenamiento / 20 % prueba.
    # Se conservan al menos 2 registros de prueba
    # cuando existen suficientes datos.
    cantidad_prueba = max(
        2,
        int(round(total * 0.20)),
    )

    if cantidad_prueba >= total:
        cantidad_prueba = 1

    cantidad_entrenamiento = (
        total - cantidad_prueba
    )

    train_df = df_ordenado.iloc[
        :cantidad_entrenamiento
    ]

    test_df = df_ordenado.iloc[
        cantidad_entrenamiento:
    ]

    x_train = train_df[features]
    y_train = train_df["litros_totales"]

    x_test = test_df[features]
    y_test = test_df["litros_totales"]

    modelo_evaluacion = RandomForestRegressor(
        n_estimators=200,
        max_depth=8,
        random_state=42,
    )

    modelo_evaluacion.fit(
        x_train,
        y_train,
    )

    predicciones_test = (
        modelo_evaluacion.predict(
            x_test
        )
    )

    mae = mean_absolute_error(
        y_test,
        predicciones_test,
    )

    rmse = mean_squared_error(
        y_test,
        predicciones_test,
    ) ** 0.5

    if len(y_test) >= 2:
        r2 = r2_score(
            y_test,
            predicciones_test,
        )
    else:
        r2 = 0.0

    metricas = {
        "mae": round(
            float(mae),
            4,
        ),
        "rmse": round(
            float(rmse),
            4,
        ),
        "r2": round(
            float(r2),
            4,
        ),
        "registros_entrenamiento":
            len(train_df),
        "registros_prueba":
            len(test_df),
    }

    # Después de evaluar, se entrena el modelo final
    # con todos los registros disponibles.
    x_completo = df_ordenado[features]
    y_completo = df_ordenado[
        "litros_totales"
    ]

    modelo_final = RandomForestRegressor(
        n_estimators=200,
        max_depth=8,
        random_state=42,
    )

    modelo_final.fit(
        x_completo,
        y_completo,
    )

    return modelo_final, metricas


def aplicar_isolation_forest(df):
    if len(df) < 6:
        df["anomalia_modelo"] = False
        df["score_anomalia"] = 0.0
        return df

    variables = df[
        [
            "litros_totales",
            "cantidad_vacas",
            "duracion_minutos",
            "litros_por_vaca",
        ]
    ]

    modelo = IsolationForest(
        contamination="auto",
        random_state=42,
    )

    resultado = modelo.fit_predict(
        variables
    )

    scores = modelo.decision_function(
        variables
    )

    df["anomalia_modelo"] = (
        resultado == -1
    )

    df["score_anomalia"] = scores

    return df


def generar_resultados(
    df,
    modelo,
    fecha_minima,
    horizonte,
):
    grupos = []

    ultima_fecha = df["fecha"].max()

    for grupo_id, grupo_df in df.groupby(
        "grupo_id"
    ):
        grupo_df = grupo_df.sort_values(
            "fecha"
        )

        if len(grupo_df) < 2:
            continue

        nombre_grupo = (
            grupo_df["grupo"].iloc[-1]
        )

        promedio_vacas = max(
            1,
            int(
                round(
                    grupo_df[
                        "cantidad_vacas"
                    ].tail(7).mean()
                )
            ),
        )

        promedio_duracion = max(
            1,
            float(
                grupo_df[
                    "duracion_minutos"
                ].tail(7).mean()
            ),
        )

        predicciones = []

        for dia in range(
            1,
            horizonte + 1,
        ):
            fecha_futura = (
                ultima_fecha
                + timedelta(days=dia)
            )

            dia_indice = (
                fecha_futura
                - fecha_minima
            ).days

            entrada = pd.DataFrame([
                {
                    "grupo_id": grupo_id,
                    "dia_indice": dia_indice,
                    "dia_semana":
                        fecha_futura.weekday(),
                    "cantidad_vacas":
                        promedio_vacas,
                    "duracion_minutos":
                        promedio_duracion,
                }
            ])

            litros_predichos = float(
                modelo.predict(
                    entrada
                )[0]
            )

            litros_predichos = max(
                0,
                litros_predichos,
            )

            predicciones.append({
                "fecha":
                    fecha_futura.strftime(
                        "%Y-%m-%d"
                    ),
                "litros":
                    round(
                        litros_predichos,
                        2,
                    ),
                "clasificacion":
                    clasificar(
                        litros_predichos
                    ),
            })

        historico = (
            grupo_df[
                "litros_totales"
            ].tail(7).tolist()
        )

        historial = (
            grupo_df[
                    [
                        "fecha",
                        "litros_totales",
                    ]
                ]
                .sort_values("fecha")
                .copy()
            )

        historial = [
                {
                    "fecha": fila["fecha"].strftime("%Y-%m-%d"),
                    "litros": round(float(fila["litros_totales"]), 2),
                }
                for _, fila in historial.iterrows()
            ]

        ultimo = grupo_df.iloc[-1]

        produccion_actual = float(
            ultimo["litros_totales"]
        )

        grupos.append({
            "grupo_id":
                int(grupo_id),

            "grupo":
                nombre_grupo,

            "produccion_actual":
                round(
                    produccion_actual,
                    2,
                ),

            "cantidad_vacas":
                int(
                    ultimo[
                        "cantidad_vacas"
                    ]
                ),

            "duracion_minutos":
                int(
                    ultimo[
                        "duracion_minutos"
                    ]
                ),

            "promedio_por_vaca":
                round(
                    float(
                        ultimo[
                            "litros_por_vaca"
                        ]
                    ),
                    2,
                ),

            "tendencia":
                obtener_tendencia(
                    historico
                ),

            "clasificacion":
                clasificar(
                    produccion_actual
                ),

            "anomalia_modelo":
                bool(
                    ultimo[
                        "anomalia_modelo"
                    ]
                ),

            "score_anomalia":
                round(
                    float(
                        ultimo[
                            "score_anomalia"
                        ]
                    ),
                    4,
                ),

            "historial":
            historial,

            "predicciones":
            predicciones,
        })

    return grupos


def main():
    try:
        entrada = json.load(
            sys.stdin
        )
    except Exception:
        error(
            "No se pudo leer el JSON de entrada."
        )

    registros = entrada.get(
        "registros",
        []
    )

    horizonte = int(
        entrada.get(
            "horizonte",
            7,
        )
    )

    if horizonte not in [
        7,
        30,
    ]:
        error(
            "El horizonte debe ser 7 o 30 días."
        )

    if len(registros) < 2:
        error(
            "Se requieren al menos 2 registros "
            "de producción."
        )

    df, fecha_minima = (
        preparar_dataframe(
            registros
        )
    )

    df = aplicar_isolation_forest(
        df
    )

    modelo, metricas = entrenar_random_forest(
        df
    )

    grupos = generar_resultados(
        df,
        modelo,
        fecha_minima,
        horizonte,
    )

    salida = {
        "success": True,

        "modelo": {
            "prediccion":
                "RandomForestRegressor",

            "deteccion_anomalias":
                "IsolationForest",

            "total_registros":
                len(df),

            "horizonte_dias":
                horizonte,
            
            "metricas": metricas,
        },

        "grupos":
            grupos,
    }

    print(
        json.dumps(
            salida,
            ensure_ascii=False,
        )
    )


if __name__ == "__main__":
    main()