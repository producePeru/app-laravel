<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Capacitación</title>
    <style>
        body { margin: 0; padding: 0; color: #333333; font-family: Arial, sans-serif; }
        .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; border: 1px solid #f1f5f9; overflow: hidden; }
        .header { background-color: #00a6db; padding: 25px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: bold; }
        .content { padding: 25px; }
        .welcome-text { font-size: 15px; line-height: 1.6; color: #334155; }
        .card-actividad { background-color: #f8fafc; border-left: 4px solid #f59e0b; border-radius: 4px; padding: 20px; margin: 20px 0; }
        .card-title { font-size: 17px; font-weight: bold; color: #1e3a8a; margin-bottom: 10px; }
        .info-grid { font-size: 14px; line-height: 1.6; }
        .info-label { font-weight: bold; color: #475569; }
        .btn-meet { display: inline-block; background-color: #16a34a; color: #ffffff !important; text-decoration: none; padding: 12px 24px; font-weight: bold; border-radius: 5px; margin-top: 15px; font-size: 15px; }
        .btn-test { display: inline-block; background-color: #00a6db; color: #ffffff !important; text-decoration: none; padding: 10px 18px; font-weight: bold; border-radius: 5px; margin-top: 10px; font-size: 16px; }
        .footer { background-color: #f1f5f9; text-align: center; padding: 15px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>

    <div class="email-container">
        <div class="header">
            <h1>⏰ ¡Tu Capacitación Inicia Pronto!</h1>
        </div>

        <div class="content">
            <p class="welcome-text">
                Hola <strong>{{ $dataUsuario['nombres'] }}</strong>,<br><br>
                Te recordamos que hoy tienes programada una sesión de capacitación correspondiente al <b>Programa Nacional Tu Empresa</b>. ¡No te la pierdas!
            </p>

            <div class="card-actividad">
                <div class="card-title">{{ $actividad['tema'] }}</div>
                <div class="info-grid">
                    <span class="info-label">📅 Fecha:</span> {{ $actividad['fecha_seleccionada'] }}<br>
                    <span class="info-label">⏰ Horario:</span> {{ $actividad['horario_inicio'] }} - {{ $actividad['horario_fin'] }}<br>
                    <span class="info-label">🏢 Organiza:</span> {{ $actividad['entidad_organizadora'] }}<br><br>

                    {{-- @if(!empty($actividad['link_meet']))
                        <a href="{{ $actividad['link_meet'] }}" target="_blank" class="btn-meet">
                            💻 UNIRSE A LA SESIÓN EN VIVO
                        </a>
                        <br>
                    @endif --}}

                    @if(!empty($actividad['link_test']))
                        <a href="{{ $actividad['link_test'] }}" target="_blank" class="btn-test">
                            📝 Confirmar Asistencia
                        </a>
                    @endif
                </div>
            </div>

            <p class="welcome-text">
                Agradecemos tu puntualidad y compromiso con tu desarrollo empresarial.
            </p>
        </div>

        <div class="footer">
            <p>Programa Nacional Tu Empresa — Ministerio de la Producción</p>
        </div>
    </div>

</body>
</html>