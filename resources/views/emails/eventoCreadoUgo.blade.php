<!DOCTYPE html>

<html>

<body style="margin:0; padding:0; background-color:#f5f6fa; font-family: Arial, sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f6fa; padding:20px 0;">
    <tr>
      <td align="center">


        <table width="600" cellpadding="0" cellspacing="0"
          style="background:#ffffff; border-radius:8px; padding:30px; box-shadow:0 2px 6px rgba(0,0,0,0.05);">

          <!-- Header -->
          <tr>
            <td style="padding-bottom:20px;">
              <h2 style="margin:0; color:#2c3e50; font-weight:600;">
                {{ $isEdit ? 'Actividad actualizada' : 'Nueva actividad registrada' }}
              </h2>
              <p style="margin:8px 0 0 0; color:#7f8c8d; font-size:14px;">
                Sistema de Gestión UGO
              </p>
            </td>
          </tr>

          <!-- Divider -->
          <tr>
            <td style="border-top:1px solid #eeeeee; padding-top:20px;">

              <p style="margin:6px 0; font-size:14px; color:#34495e;">
                <strong>Título:</strong> {{ $attendance->title }}
              </p>

              <p style="margin:6px 0; font-size:14px; color:#34495e;">
                <strong>Tema:</strong> {{ $attendance->theme }}
              </p>

              <p style="margin:6px 0; font-size:14px; color:#34495e;">
                <strong>Asesor:</strong>
                {{ $asesor->name }}
                {{ $asesor->middlename }}
                {{ $asesor->lastname }}
              </p>

            </td>
          </tr>

          <!-- Button -->
          <tr>
            <td align="left" style="padding:25px 0;">
              <a href="{{ $link }}" style="background-color:#00a6db; 
                    color:#ffffff; 
                    padding:12px 20px; 
                    text-decoration:none; 
                    border-radius:6px; 
                    font-size:14px; 
                    font-weight:bold; 
                    display:inline-block;">
                Ver actividad
              </a>
            </td>
          </tr>

          <!-- Changes -->
          @if($isEdit && count($changes))
          <tr>
            <td style="border-top:1px solid #eeeeee; padding-top:20px;">
              <h4 style="margin:0 0 10px 0; color:#2c3e50;">
                Campos modificados
              </h4>
              <ul style="padding-left:18px; margin:0; font-size:14px; color:#34495e;">
                @foreach($changes as $campo => $valores)
                <li style="margin-bottom:6px;">
                  <strong>{{ $campo }}</strong>:
                  <span style="color:#c0392b;">{{ $valores['antes'] }}</span>
                  →
                  <span style="color:#27ae60;">{{ $valores['ahora'] }}</span>
                </li>
                @endforeach
              </ul>
            </td>
          </tr>
          @endif

          <!-- Footer -->
          <tr>
            <td style="padding-top:30px; font-size:12px; color:#95a5a6;">
              Este es un mensaje automático del sistema UGO.
              Por favor, no responder a este correo.
            </td>
          </tr>

        </table>

      </td>
    </tr>


  </table>

</body>

</html>