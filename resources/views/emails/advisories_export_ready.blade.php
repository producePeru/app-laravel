<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Exportación Lista Completa</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f7f9fc;
      margin: 0;
      padding: 0;
      color: #333;
    }

    .email-box {
      max-width: 600px;
      margin: 40px auto;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 24px;
    }

    h2 {
      color: #004aad;
      font-size: 18px;
      margin-bottom: 16px;
      text-align: center;
    }

    p {
      font-size: 14px;
      line-height: 1.6;
    }

    .footer {
      text-align: center;
      font-size: 12px;
      color: #777;
      margin-top: 24px;
      border-top: 1px solid #eee;
      padding-top: 12px;
    }
  </style>
</head>

<body>
  <div class="email-box">
    <p>Hola,</p>

    <p>La exportación ha finalizado correctamente.</p>

    <h2>Exportación de {{ $filename }} está lista</h2>

    <p>Se adjunta el archivo <strong></strong>.</p>

    <p>Saludos,<br><strong>Equipo Tu Empresa - Support</strong></p>

    <p style="font-size: 24px; text-align: center;">😎</p>

    <div class="footer">
      © {{ date('Y') }} Programa Nacional Tu Empresa
    </div>
  </div>
</body>

</html>