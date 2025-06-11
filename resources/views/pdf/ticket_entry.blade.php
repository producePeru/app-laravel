<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            padding: 40px 0;
            /* background-color: #e4eaf6; */
            font-family: sans-serif;
            color: #333;
        }

        .container {
            width: 100%;
            text-align: center;
        }

        .card {
            background-color: #ffffff;
            width: 400px;
            margin: 0 auto;
            padding: 20px 40px;
            border-radius: 20px;
            border: 1px solid #efefef;
            /* simula el contorno */
        }

        .card img.logo {
            width: 200px;
        }

        .card__title {
            font-size: 18px;
            color: #01e6a1;
            font-weight: bold;
            margin-top: 20px;
        }

        .card__info {
            margin: 20px 0;
            text-align: center;
            font-size: 13px;
        }

        .card__info b {
            display: inline-block;
        }

        .qr {
            margin: 0 auto;
            width: 200px;
            height: 200px;
        }

        .note {
            font-size: 12px;
            margin-top: 10px;
            color: #777;
        }

        .note p {
            margin: 0;
        }

        .important {
            margin-top: 10px;
            font-size: 13px;
            color: #777;
        }

        .important strong {
            color: #000;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">

            <img class="logo" src="{{ $logoDataUri }}" alt="Logo">

            <h1 class="card__title">{{$fair['title']}}</h1>

            <div class="card__info">

                <div>
                    <b>{{ $participantName }}</b>
                </div>
                <div style="margin-bottom: 10px" class="note">
                    <b>Lugar:</b>
                    <p>{{ $fair['place'] }}</p>
                </div>
                <div style="margin-bottom: 10px" class="note">
                    <b>Fecha:</b>
                    <p>{{ $fair['fecha'] }}</p>
                </div>
                <div class="note">
                    <b>Hora:</b>
                    <p>{{ $fair['hours'] }}</p>
                </div>
            </div>


            <img class="qr" src="data:image/png;base64,{{ $qrBase64 }}" alt="QR">


            <div class="note">
                Este documento es tu entrada oficial al evento.
            </div>

            <div class="important">
                <strong>Importante:</strong> Lleva esta entrada impresa o en formato digital. El ingreso está sujeto a
                validación del código QR.
            </div>
        </div>
    </div>
</body>

</html>
