<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Registro</title>
    <style>
        body { margin: 0; padding: 0; color: #333333; }
        .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #00a6db; padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px; }
        .welcome-text { font-size: 16px; line-height: 1.6; margin-bottom: 25px; }
        .card-actividad { background-color: #f8fafc; border-left: 4px solid #3b82f6; border-radius: 4px; padding: 20px; margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: bold; color: #1e3a8a; margin-top: 0; margin-bottom: 10px; }
        .info-grid { font-size: 14px; line-height: 1.5; }
        .info-label { font-weight: 600; color: #475569; }
        .btn-link { display: inline-block; background-color: #00a6db; color: white !important; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; margin-top: 12px; }
        .footer { background-color: #f1f5f9; text-align: center; padding: 20px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body style="background-color: #f4f6f9;">

    <div class="email-container">
        <!-- Encabezado -->
        <div class="header">
            <h1>¡Registro Confirmado con Éxito!</h1>
        </div>

        <!-- Contenido Principal -->
        <div class="content">
            <p class="welcome-text">
                Hola <strong>{{ $dataUsuario['nombres'] }}</strong>,<br>
              Reciba un cordial saludo del <b>Programa Nacional Tu Empresa</b>.
                Gracias por completar el formulario. Su información ha sido registrada satisfactoriamente
            </p>

            <h3 style="color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Mis Actividades</h3>

            @foreach($actividadesDetalle as $act)
                <div class="card-actividad">
                    <div class="card-title">{{ $act['tema'] }}</div>
                    <div class="info-grid">
                        <span class="info-label">Fecha del evento:</span> {{ $act['fecha_seleccionada'] }}<br>
                        <span class="info-label">Horario:</span> {{ $act['horario_inicio'] }} - {{ $act['horario_fin'] }}<br>
                        <span class="info-label">Organiza:</span> {{ $act['entidad_organizadora'] }}<br>
                        <span class="info-label">Lugar / Modalidad:</span> {{ $act['lugar'] }}<br>
                       
                        @if(!empty($act['link_test']))
                            <a href="{{ $act['link_test'] }}" target="_blank" class="btn-link">
                                📝 Confirmar Asistencia
                            </a>
                        @endif
                        
                    </div>
                </div>

                {{-- ← Mensaje personalizado por actividad --}}
                {{-- @if(!empty($act['mensaje_correo']))
                    <div class="mensaje-correo" style="margin-top: -10px; margin-bottom: 20px; padding: 15px; background-color: #eff6ff; border-radius: 4px; font-size: 14px; line-height: 1.6; color: #1e3a8a;">
                        {!! $act['mensaje_correo'] !!}
                    </div>
                @endif --}}

            @endforeach

            <p class="welcome-text" style="margin-top: 25px; margin-bottom: 0;">
                Hemos habilitado un grupo exclusivo para participantes del curso, donde compartiremos:
              </p>
          <ul style="list-style: none;">
            <li>✔ Material complementario</li>
            <li>✔ Lecturas y recursos</li>
            <li>✔ Convocatorias y novedades del sector</li>
            <li>✔ Espacios de interacción con otros empresarios</li>
          </ul>

            
        </div>

        <!-- Pie de página -->
        <div class="footer">
          <p>
            Durante la sesión, el expositor explicará detalladamente cada una de las actividades mencionadas y absolverá cualquier consulta que pueda presentarse.
          </p>

          <p>
            Agradecemos de antemano su puntualidad, compromiso y participación.
          </p>

          <p>Saludos cordiales</p>
        </div>
    </div>

</body>
</html>