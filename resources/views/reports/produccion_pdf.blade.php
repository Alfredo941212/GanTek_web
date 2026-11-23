<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>
        Reporte de producción GanTek
    </title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 5px;
        }

        h2 {
            font-size: 16px;
            margin-top: 25px;
            margin-bottom: 10px;
        }

        .info {
            margin-bottom: 20px;
        }

        .cards {
            width: 100%;
            margin-bottom: 15px;
        }

        .card {
            display: inline-block;
            width: 28%;
            padding: 10px;
            margin-right: 1%;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .card strong {
            display: block;
            font-size: 16px;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 7px;
            text-align: left;
        }

        th {
            background-color: #eeeeee;
        }

        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>

    <h1>
        GanTek - Reporte de producción
    </h1>

    <div class="info">
        <strong>Usuario:</strong>
        {{ $usuario->name ?? 'Usuario GanTek' }}
        <br>

        <strong>Periodo:</strong>
        {{ ucfirst($periodo) }}
        <br>

        <strong>Desde:</strong>
        {{ $desde }}
        <br>

        <strong>Hasta:</strong>
        {{ $hasta }}
    </div>

    <div class="cards">

        <div class="card">
            Total producido

            <strong>
                {{ number_format($resumen['total'], 2) }} L
            </strong>
        </div>

        <div class="card">
            Promedio diario

            <strong>
                {{ number_format($resumen['promedio'], 2) }} L
            </strong>
        </div>

        <div class="card">
            Días con registros

            <strong>
                {{ $resumen['dias'] }}
            </strong>
        </div>

    </div>

    <div class="cards">

        <div class="card">
            Sesiones realizadas

            <strong>
                {{ $resumen['sesiones'] }}
            </strong>
        </div>

        <div class="card">
            Vacas atendidas

            <strong>
                {{ $resumen['vacas_atendidas'] }}
            </strong>
        </div>

    </div>

    <h2>
        Producción diaria
    </h2>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Litros</th>
                <th>Sesiones</th>
            </tr>
        </thead>

        <tbody>

            @forelse($resumen['diaria'] as $registro)

                <tr>
                    <td>
                        {{ $registro['fecha'] }}
                    </td>

                    <td>
                        {{ number_format(
                            $registro['litros'],
                            2
                        ) }} L
                    </td>

                    <td>
                        {{ $registro['sesiones'] }}
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="3">
                        No hay registros de producción
                        para el periodo seleccionado.
                    </td>
                </tr>

            @endforelse

        </tbody>
    </table>

    <h2>
        Producción por grupo
    </h2>

    <table>
        <thead>
            <tr>
                <th>Grupo</th>
                <th>Litros</th>
                <th>Sesiones</th>
            </tr>
        </thead>

        <tbody>

            @forelse($resumen['grupos'] as $registro)

                <tr>
                    <td>
                        {{ $registro['grupo'] }}
                    </td>

                    <td>
                        {{ number_format(
                            $registro['litros'],
                            2
                        ) }} L
                    </td>

                    <td>
                        {{ $registro['sesiones'] }}
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="3">
                        No hay producción registrada por grupo.
                    </td>
                </tr>

            @endforelse

        </tbody>
    </table>

    <div class="footer">
        Reporte generado por GanTek el
        {{ now()->format('d/m/Y H:i') }}.
    </div>

</body>

</html>