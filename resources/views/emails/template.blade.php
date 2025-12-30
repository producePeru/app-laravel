<!DOCTYPE html>
<html lang="es">

<head>
    <title>Programa Nacional Tu Empresa</title>
    <style>
        .masive br {
            display: none;
        }

        .masive h2 {
            line-height: 1.3;
        }
    </style>
</head>

<body>
    <h1>{{ $fairName }}</h1>

<p>Hola {{ $participantName }}, esta es tu entrada para el evento.</p>

<div style="text-align: center; margin-top: 20px;">
    <img src="data:image/png;base64,{{ $qrImage }}" alt="Código QR">
</div>
</body>

</html>
