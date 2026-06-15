<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Registro</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; color: #333333; }
        .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #1e3a8a; padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px; }
        .welcome-text { font-size: 16px; line-height: 1.6; margin-bottom: 25px; }
        .card-actividad { background-color: #f8fafc; border-left: 4px solid #3b82f6; border-radius: 4px; padding: 20px; margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: bold; color: #1e3a8a; margin-top: 0; margin-bottom: 10px; }
        .info-grid { font-size: 14px; line-height: 1.5; }
        .info-label { font-weight: 600; color: #475569; }
        .btn-link { display: inline-block; background-color: #10b981; color: white !important; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; margin-top: 12px; }
        .footer { background-color: #f1f5f9; text-align: center; padding: 20px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>

    <div class="email-container">
        <!-- Encabezado -->
        <div class="header">
            <h1>¡Registro Confirmado con Éxito!</h1>
        </div>

        <!-- Contenido Principal -->
        <div class="content">
            <p class="welcome-text">
                Hola <strong>{{ $dataUsuario['nombres'] }}</strong>,<br>
                Queremos confirmarte que te has registrado correctamente en nuestro sistema de capacitaciones. A continuación, te detallamos la información de tus próximas actividades programadas:
            </p>

            <h3 style="color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Mis Actividades</h3>

            @foreach($actividadesDetalle as $act)
                <div class="card-actividad">
                    <!-- Mostramos el Tema de la actividad -->
                    <div class="card-title">{{ $act['tema'] }}</div>
                    
                    <div class="info-grid">
                        <span class="info-label">Fecha del evento:</span> {{ $act['fecha_seleccionada'] }}<br>
                        <span class="info-label">Horario:</span> {{ $act['horario_inicio'] }} - {{ $act['horario_fin'] }}<br>
                        <span class="info-label">Organiza:</span> {{ $act['entidad_organizadora'] }}<br>
                        <span class="info-label">Lugar / Modalidad:</span> {{ $act['lugar'] }}<br>
                        
                        @if(!empty($act['link']))
                            <a href="{{ $act['link'] }}" target="_blank" class="btn-link">Unirse a la Sesión / Ver Enlace</a>
                        @endif
                    </div>
                </div>
            @endforeach

            <p class="welcome-text" style="margin-top: 25px;">
                Por favor, conéctate puntualmente en los horarios establecidos. Si tienes alguna duda, puedes responder directamente a este correo electrónico.
            </p>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            Este es un correo automático del Sistema de Gestión de Plataforma.<br>
            RUC de la Empresa Asociada: {{ $dataUsuario['ruc'] }} | Razón Social: {{ $dataUsuario['razon_social'] }}
        </div>
    </div>

</body>
</html>