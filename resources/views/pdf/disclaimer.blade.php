<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Declaración Jurada</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .title { text-align: center; font-weight: bold; margin-bottom: 20px; }
        p { text-align: justify; margin-bottom: 12px; }
        .disclaimer-body ul, .disclaimer-body ol { padding-left: 20px; }
    </style>
</head>
<body>

<div class="title">
    DECLARACIÓN JURADA DE PARTICIPACIÓN EN EL EVENTO “CYBER WOW”
</div>

<p>
Yo, <b>{{ $data['nombre'] }}</b>, identificado(a) con DNI N° <b>{{ $data['dni'] }}</b>,
en calidad de representante legal de <b>{{ $data['empresa'] }}</b>, con RUC N° <b>{{ $data['ruc'] }}</b>, declaro bajo juramento lo siguiente:
</p>

<div class="disclaimer-body">
    @if(!empty($descripcion))
        {!! $descripcion !!}
    @else
        <p>
            Que, de manera voluntaria, solicito y acepto participar en el evento comercial denominado <b>“CYBER WOW”</b>,
            habiendo sido canalizado para dicha participación por el <b>Programa Nacional “Tu Empresa”</b> del Ministerio de la
            Producción, en el marco de sus competencias de promoción de la productividad, digitalización y acceso a
            mercados de las micro y pequeñas empresas.
        </p>
    @endif
</div>

<p>
    {{ $data['fecha'] }}
</p>


</body>
</html>
