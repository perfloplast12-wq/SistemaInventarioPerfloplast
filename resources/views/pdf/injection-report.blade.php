<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Actividades</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .header p {
            margin: 0;
        }
        .header strong {
            display: inline-block;
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .col-fecha { width: 10%; }
        .col-dia { width: 10%; }
        .col-actividad { width: 25%; }
        .col-descripcion { width: 25%; }
        .col-resultado { width: 30%; }
        
        .footer-section {
            margin-bottom: 25px;
        }
        .footer-section h4 {
            margin-bottom: 8px;
            font-size: 13px;
        }
        .footer-section p {
            margin: 0;
            padding-left: 20px;
            min-height: 40px;
            white-space: pre-line;
        }
    </style>
</head>
<body>

    <div class="header">
        <p><strong>Nombre:</strong> {{ $report->employee_name }}</p>
        <p><strong>Puesto:</strong> {{ $report->position }}</p>
        <p><strong>Área-departamento:</strong> {{ $report->department }}</p>
        <p><strong>Semana:</strong> {{ $report->week_range }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-fecha">Fecha</th>
                <th class="col-dia">Día</th>
                <th class="col-actividad">Actividad</th>
                <th class="col-descripcion">Descripción</th>
                <th class="col-resultado">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report->items as $item)
            <tr>
                <td>{{ $item->date ? $item->date->format('d/m/Y') : '' }}</td>
                <td>{{ $item->day }}</td>
                <td>{{ $item->activity }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->result }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center;">No hay actividades registradas para esta semana.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <h4>&bull; Propuestas o mejoras</h4>
        <p>{{ $report->proposals ?? 'Ninguna' }}</p>
    </div>

    <div class="footer-section">
        <h4>&bull; Plan de trabajo para la próxima semana</h4>
        <p>{{ $report->next_week_plan ?? 'Ninguno especifico' }}</p>
    </div>

</body>
</html>
