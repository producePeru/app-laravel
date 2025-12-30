<!doctype html>
<html lang="es">

<head>
  <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
  <title>Mensaje de notificación</title>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
  <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8"
    style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;">
    <tr>
      <td>
        <table style="background-color: #f2f3f8; max-width:670px; margin:0 auto;" width="100%" border="0" align="center"
          cellpadding="0" cellspacing="0">
          <tr>
            <td style="height:80px;">&nbsp;</td>
          </tr>

          <tr>
            <td style="height:20px;">&nbsp;</td>
          </tr>
          <tr>
            <td>
              <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                style="max-width:670px; background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                <tr>
                  <td style="height:40px;">&nbsp;</td>
                </tr>
                <tr>
                  <td style="padding:0 35px;">
                    <img src="https://cdn-icons-png.freepik.com/256/12461/12461956.png?semt=ais_hybrid" alt="" width="50">
                    <h1
                      style="color:#1e1e2d; font-weight:500; margin:10px 0 14px;font-size:28px;font-family:'Rubik',sans-serif;">
                      Mensaje de notificación
                    </h1>
                    <p style="font-size:14px; color:#455056; margin:8px 0 0; line-height:24px;">

                      El convenio con: <strong style="text-transform: uppercase; color: #000; font-size: 15px; display: block;"> {{ $agreement['alliedEntity'] }} </strong>
                      <!-- de la región <span style="text-transform: capitalize;"> {{ $agreement['denomination'] }},</span>
                      provincia <span <span style="text-transform: capitalize;"> {{ $agreement['denomination'] }}</span>
                      del distrito de <span <span style="text-transform: capitalize;"> {{ $agreement['denomination'] }}</span> -->
                      está próximo a finalizar.

                      <span style="display: block; margin-bottom: 4px;"></span>

                    @php
                        $startDateTime = new \DateTime($agreement['startDate']);
                        $endDateTime = new \DateTime($agreement['endDate']);

                        $formattedStartDate = $startDateTime->format('d/m/Y');
                        $formattedEndDate = $endDateTime->format('d/m/Y');
                    @endphp

                      <span>Fecha inicio del convenio: <span>{{ $formattedStartDate }}</span>
                      <br>
                      <span>Fecha fin del convenio: <b style="color: #dc1c19;">{{ $formattedEndDate }}</b>

                      <br>
                      <br>
                      <strong>
                        Se recomienda actuar antes de la fecha de vencimiento.
                      </strong>.
                    </p>
                    <span
                      style="display:inline-block; vertical-align:middle; margin:29px 0 26px; border-bottom:1px solid #cecece; width:100px;"></span>
                    <p style="color:#455056; font-size:18px;line-height:20px; margin:0; font-weight: 500;">

                    </p>

                    <a href="https://programa.soporte-pnte.com/"
                      style="background:#dc1c19;text-decoration:none !important; display:inline-block; font-weight:500; margin-top:24px; color:#fff;text-transform:uppercase; font-size:14px;padding:10px 24px;display:inline-block;border-radius:8px;">
                      Actualizar</a>
                  </td>
                </tr>
                <tr>
                  <td style="height:40px;">&nbsp;</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="height:20px;">&nbsp;</td>
          </tr>

          <tr>
            <td style="height:80px;">&nbsp;</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>

</html>
