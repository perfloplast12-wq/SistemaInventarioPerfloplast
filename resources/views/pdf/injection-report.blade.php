<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Semanal de Actividades - Perflo Plast</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #2D3748;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .logo {
            width: 180px;
            height: auto;
        }
        .company-info {
            text-align: right;
            font-size: 9pt;
            color: #718096;
        }
        .report-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #1A202C;
            text-transform: uppercase;
            margin-bottom: 25px;
            border-bottom: 2px solid #4A5568;
            padding-bottom: 10px;
        }
        .user-info {
            margin-bottom: 20px;
            background-color: #F7FAFC;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2D3748;
        }
        .user-info table {
            width: 100%;
        }
        .user-info td {
            padding: 4px 0;
        }
        .label {
            font-weight: bold;
            color: #4A5568;
            width: 150px;
        }
        
        .activity-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .activity-table th {
            background-color: #2D3748;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            padding: 10px;
            border: 1px solid #2D3748;
            text-align: center;
        }
        .activity-table td {
            border: 1px solid #E2E8F0;
            padding: 10px;
            vertical-align: top;
            font-size: 9pt;
        }
        .activity-table tr:nth-child(even) {
            background-color: #F8FAFC;
        }
        
        .col-fecha { width: 12%; text-align: center; }
        .col-dia { width: 10%; text-align: center; font-weight: bold; }
        .col-actividad { width: 25%; }
        .col-descripcion { width: 28%; }
        .col-resultado { width: 25%; }
        
        .footer-section {
            margin-top: 30px;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2D3748;
            display: block;
        }
        .section-content {
            background-color: #FFF;
            border: 1px solid #E2E8F0;
            padding: 15px;
            min-height: 80px;
            border-radius: 5px;
            font-style: italic;
            color: #4A5568;
            white-space: pre-line;
        }
        .bullet {
            color: #2D3748;
            margin-right: 5px;
        }
        
        .footer-note {
            margin-top: 50px;
            text-align: center;
            font-size: 8pt;
            color: #A0AEC0;
            border-top: 1px solid #E2E8F0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <img src="{{ public_path('images/logo-perfloplast.png') }}" class="logo" alt="Logo Perflo Plast">
            </td>
            <td class="company-info">
                <strong>Perflo Plast, S.A.</strong><br>
                Departamento de Producción / Mantenimiento<br>
                Guatemala, {{ now()->format('Y') }}
            </td>
        </tr>
    </table>

    <div class="report-title">Reporte Semanal de Actividades</div>

    <div class="user-info">
        <table>
            <tr>
                <td class="label">Nombre:</td>
                <td>{{ $report->employee_name }}</td>
                <td class="label">Semana:</td>
                <td>{{ $report->week_range }}</td>
            </tr>
            <tr>
                <td class="label">Puesto:</td>
                <td>{{ $report->position }}</td>
                <td class="label">Área-departamento:</td>
                <td>{{ $report->department }}</td>
            </tr>
        </table>
    </div>

    <table class="activity-table">
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
            @php
                $days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            @endphp
            @foreach($days as $dayName)
                @php
                    $item = $report->items->where('day', $dayName)->first();
                @endphp
                <tr>
                    <td class="col-fecha">{{ $item && $item->date ? $item->date->format('d/m/Y') : '-' }}</td>
                    <td class="col-dia">{{ $dayName }}</td>
                    <td>{{ $item->activity ?? '' }}</td>
                    <td>{{ $item->description ?? '' }}</td>
                    <td>{{ $item->result ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-section">
        <span class="section-title"><span class="bullet">&bull;</span> Propuestas o mejoras</span>
        <div class="section-content">
            {{ $report->proposals ?: 'No se registraron propuestas adicionales para este periodo.' }}
        </div>
    </div>

    <div class="footer-section">
        <span class="section-title"><span class="bullet">&bull;</span> Plan de trabajo para la próxima semana</span>
        <div class="section-content">
            {{ $report->next_week_plan ?: 'Continuar con las actividades programadas de mantenimiento y producción.' }}
        </div>
    </div>

    <div class="footer-note">
        Este documento es un registro oficial de actividades de Perflo Plast. Generado automáticamente por el Sistema de Inventario.
    </div>

</body>
</html>
