<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Declaración Jurada</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .title { text-align: center; font-weight: bold; margin-bottom: 20px; }
        p { text-align: justify; margin-bottom: 12px; }
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


<p>
Que, de manera voluntaria, solicito y acepto participar en el evento comercial denominado <b>“CYBER WOW”</b>,
          programado desde el 20 hasta el 23 de abril de 2026, a través de la plataforma administrada por IAB, habiendo
          sido canalizado para dicha participación por el <b>Programa Nacional “Tu Empresa”</b> del Ministerio de la
          Producción, en el marco de sus competencias de promoción de la productividad, digitalización y acceso a
          mercados de las micro y pequeñas empresas.
</p>

<p>
  Asimismo, declaro que mi participación en el referido evento es de carácter gratuito y que deriva de un
          proceso de postulación previo, en el cual cumplí con los requisitos y condiciones establecidos por el PNTE
          para acceder a dicho espacio.
</p>

<p>
  Declaro que toda la información que consigne la plataforma del evento en cuestión, incluyendo productos,
          servicios, precios, stock, condiciones de venta, tiempos de entrega, garantías, entre otros, es veraz, se
          encuentra actualizada y es de mi exclusiva responsabilidad. Asimismo, me comprometo a cumplir de manera
          oportuna, íntegra y conforme con todas las condiciones ofrecidas a los consumidores durante el evento,
          incluyendo la entrega de productos o la prestación de servicios en los términos ofertados.
</p>

<p>
  Reconozco que la relación contractual que se genere como consecuencia de la adquisición de bienes o
          contratación de servicios se establece exclusivamente entre la empresa que represento y el consumidor final,
          asumiendo plena responsabilidad frente a cualquier reclamo, queja, denuncia o controversia que pudiera
          derivarse de las transacciones realizadas durante el evento.
</p>

<p>
   Declaro expresamente que el PNTE no participa en la venta, comercialización ni distribución de los productos o
          servicios ofertados, ni actúa como proveedor, intermediario, garante ni responsable solidario de las
          transacciones realizadas, y no tiene injerencia en la gestión operativa, logística, financiera ni en el
          cumplimiento de las obligaciones comerciales asumidas por mi empresa.
</p>

<p>
  En ese sentido, exonero expresamente al PNTE y a sus funcionarios de cualquier responsabilidad administrativa,
          civil, comercial o de cualquier otra índole derivada del incumplimiento de mis obligaciones frente a los
          consumidores.
</p>

<p>
  Declaro que la empresa que represento cumple con la normativa vigente aplicable, incluyendo las disposiciones
          en materia de protección al consumidor, comercio electrónico, publicidad y protección de datos personales,
          comprometiéndome a atender de manera oportuna los reclamos de los consumidores conforme a la normativa
          vigente.
</p>

<p>
  Asimismo, declaro que toda la información proporcionada al PNTE es veraz y verificable, sometiéndome a las
          acciones legales correspondientes en caso de comprobar inexactitudes y/o falsedad.
</p>

<p>
  Declaro haber leído íntegramente la presente Declaración Jurada, comprendiendo plenamente su contenido y
          alcance legal, firmándola en señal de conformidad.
</p>

<p>
  {{ $data['fecha'] }}
</p>


</body>
</html>