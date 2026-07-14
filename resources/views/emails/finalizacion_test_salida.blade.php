<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gracias por participar</title>

<style>
body{
    margin:0;
    padding:30px 0;
    background:#eef3f7;
    font-family:Arial,Helvetica,sans-serif;
}

.container{
    width:620px;
    max-width:95%;
    margin:auto;
    background:#fff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.header{
    background:linear-gradient(135deg,#00A6DB,#0077B6);
    color:#fff;
    text-align:center;
    padding:45px 30px;
}

.header h1{
    margin:0;
    font-size:30px;
}

.header p{
    margin-top:10px;
    font-size:16px;
}

.content{
    padding:35px;
    color:#444;
    line-height:1.8;
    font-size:15px;
}

.card{
    margin:30px 0;
    padding:25px;
    background:#f7fbfd;
    border-left:5px solid #00A6DB;
    border-radius:8px;
}

.card h3{
    margin-top:0;
    color:#005187;
}

.note{
    background:#FFF8E5;
    border:1px solid #FFD56A;
    border-radius:8px;
    padding:20px;
    margin:25px 0;
}

.note strong{
    color:#b76b00;
}

.footer{
    background:#f5f7fa;
    text-align:center;
    padding:25px;
    color:#777;
    font-size:13px;
}

.badge{
    display:inline-block;
    margin-top:15px;
    padding:10px 20px;
    background:#00A6DB;
    color:#fff;
    border-radius:50px;
    font-weight:bold;
}

.highlight{
    color:#005187;
    font-weight:bold;
}
</style>

</head>

<body>

<div class="container">

    <div class="header">

        <h1>🎉 ¡Gracias por participar!</h1>

        <p>Programa Nacional Tu Empresa</p>

    </div>

    <div class="content">

        <p>

            Estimado(a)
            <span class="highlight">{{ $data['nombres'] }}</span>,

        </p>

        <p>

            Hemos recibido correctamente su <strong>prueba de salida</strong>.

            Agradecemos el tiempo dedicado y su participación en esta actividad de capacitación del <strong>Programa Nacional Tu Empresa</strong>.

        </p>

        <div class="card">

            <h3>✅ Evaluación registrada</h3>

            <p>

                Su evaluación ha sido registrada exitosamente en nuestro sistema.

            </p>

        </div>

        <div class="note">

            <strong>Importante</strong>

            <p>

                La <strong>Constancia de Participación</strong> será enviada únicamente a los participantes que hayan completado satisfactoriamente tanto la <strong>Prueba de Entrada</strong> como la <strong>Prueba de Salida</strong>.

            </p>

        </div>

        <p>

            Gracias por confiar en nosotros para fortalecer sus capacidades empresariales.

        </p>

        {{-- <center>

            <span class="badge">
                ¡Le esperamos en nuestras próximas capacitaciones!
            </span>

        </center> --}}

    </div>

    <div class="footer">

        <strong>Programa Nacional Tu Empresa</strong><br>
        Ministerio de la Producción

    </div>

</div>

</body>
</html>