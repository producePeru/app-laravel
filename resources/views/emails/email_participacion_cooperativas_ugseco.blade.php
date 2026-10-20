<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de inscripción</title>
    <style>
        body { margin: 0; padding: 0; background: #eef3f7; color: #333333; font-family: Arial, Helvetica, sans-serif; }
        .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #00a6db; padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px; font-size: 15px; line-height: 1.6; }
        .card-actividad { background-color: #f8fafc; border-left: 4px solid #00a6db; border-radius: 4px; padding: 20px; margin: 20px 0; }
        .card-title { font-size: 18px; font-weight: bold; color: #1e3a8a; margin: 0 0 10px; }
        .info-label { font-weight: 600; color: #475569; }
        .btn-link { display: inline-block; background-color: #00a6db; color: #ffffff !important; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; margin-top: 12px; }
        .link-text { font-size: 12px; color: #64748b; word-break: break-all; }
        .footer { background-color: #f1f5f9; text-align: center; padding: 20px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>

    <div class="email-container">
        <div class="header">
            <h1>¡Inscripción exitosa!</h1>
        </div>

        <div class="content">
            <p>Hola, {{ $nombres ? ' '.$nombres : '' }}:</p>

            <p>
                Mediante el presente le comunicamos que su <strong>SOLICITUD DE INSCRIPCIÓN</strong>
                a la capacitación <strong>{{ $capacitacion['tema'] }}</strong> se realizó con éxito.
            </p>

            <div class="card-actividad">
                <div class="card-title">{{ $capacitacion['tema'] }}</div>
                <div>
                    <span class="info-label">Fecha:</span> {{ $capacitacion['fecha'] }}<br>
                    <span class="info-label">Horario:</span> {{ $capacitacion['horario'] }}<br>
                    <span class="info-label">Modalidad:</span> {{ $capacitacion['modalidad'] }}
                    @if(!empty($capacitacion['direccion']))
                        <br><span class="info-label">Dirección:</span> {{ $capacitacion['direccion'] }}
                    @endif
                </div>
            </div>

            <p>
                Recuerde que el evento se realizará el <strong>{{ $capacitacion['fecha'] }}</strong>
                en el horario <strong>{{ $capacitacion['horario'] }}</strong>.
            </p>

            @if(!empty($capacitacion['link']))
                <p>Accede a la capacitación en el siguiente enlace:</p>
                <p>
                    <a href="{{ $capacitacion['link'] }}" target="_blank" class="btn-link">
                        Ingresar a la capacitación
                    </a>
                </p>
                <p class="link-text">{{ $capacitacion['link'] }}</p>
            @else
                <p>El enlace de acceso será compartido oportunamente.</p>
            @endif

            <p>
                Cualquier consulta escribir a
                <a href="mailto:servicios.coop@produce.gob.pe">servicios.coop@produce.gob.pe</a>.
            </p>

            <p>Saludos cordiales</p>
        </div>

        <div class="footer">
            Programa Nacional Tu Empresa
        </div>
    </div>

</body>
</html>
