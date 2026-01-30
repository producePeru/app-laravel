<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>{{ $data['title'] }}</title>
</head>

<body style="font-family: Arial, sans-serif; background:#f9f9f9; padding:20px">

  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center">
        <table width="600" style="background:#ffffff; padding:24px; border-radius:8px">
          <tr>
            <td>
              <h2 style="color:#333">{{ $data['title'] }}</h2>

              <p>Te invitamos a participar en el siguiente evento:</p>

              <p><strong>📅 Fecha:</strong> {{ $data['date'] }}</p>
              <p><strong>⏰ Horario:</strong> {{ $data['hourStart'] }} – {{ $data['hourEnd'] }}</p>

              <p style="margin:24px 0">
                <a href="{{ $data['link'] }}"
                  style="background:#7d4fc6; color:#fff; padding:12px 18px;
                                          text-decoration:none; border-radius:6px; font-weight:bold">
                  Acceder al evento
                </a>
              </p>

              <p style="color:#666; font-size:14px">
                Este correo fue enviado a {{ $data['email'] }}
              </p>

              <hr>

              <p style="font-size:12px; color:#999">
                © {{ date('Y') }} Programa PNTE
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

</body>

</html>