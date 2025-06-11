<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Bienvenido al Evento</title>
</head>

<body style="margin:0; padding:0; background-color:#f2f2f2;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0"
        style="background-color:#f2f2f2; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 20px;">
                            <img src="https://apituempresa.soporte-pnte.com/images/tuempresalogo.png"
                                alt="Programa Tu Empresa" width="120" style="display:block;">
                        </td>
                    </tr>

                    <!-- Main Image -->
                    <tr>
                        <td align="center" style="padding: 20px;">
                            <img src="{{ $qr }}" alt="Código QR" />
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td align="center"
                            style="font-family: Arial, sans-serif; font-size: 24px; color: #333; padding: 10px;">
                            Hola, empresario
                        </td>
                    </tr>

                    <!-- Message -->
                    <tr>
                        <td align="center"
                            style="font-family: Arial, sans-serif; font-size: 16px; color: #666; padding: 0 40px 30px; line-height: 1.4">
                            Las capacitaciones programadas para este 2025 se desarrollarán vía TEAMS, para lo cual
                            deberá seleccionar el día y la hora de capacita
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center"
                            style="padding: 20px; font-family: Arial, sans-serif; font-size: 12px; color: #aaa;">
                            © Programa Nacional Tu Empresa.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
