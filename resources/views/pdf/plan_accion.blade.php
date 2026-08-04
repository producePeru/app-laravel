<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; }
    table { width: 100%; border-collapse: collapse; }
    .title { text-align: center; font-size: 16px; font-weight: bold; padding: 8px 0; border: 1px solid #000; }
    .bar-blue { background-color: #29ABE2; color: #fff; font-weight: bold; padding: 4px 8px; border: 1px solid #000; }
    .bar-red { background-color: #ED1C24; color: #fff; font-weight: bold; text-align: center; padding: 6px 4px; border: 1px solid #000; }
    .datos td { border: 1px solid #000; padding: 4px 8px; }
    .label { font-weight: bold; }
    .rows td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
    .actividad { color: #1a4faf; font-weight: bold; }
    .destacado { color: #E00000; font-weight: bold; }
    .center { text-align: center; }
</style>
</head>
<body>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:15px;">
    <tr>
        <td width="50%" align="left" valign="middle">
            <img src="{{ public_path('images/tuempresalogo.png') }}" style="width:160px;">
        </td>

        <td width="50%" align="right" valign="middle">
            <img src="{{ public_path('images/mujerproducelogo.webp') }}" style="width:80px;">
        </td>
    </tr>
</table>

  <br />
  
<table>
    <tr><td class="title" colspan="4">PLAN DE ACCIÓN - MUJER PRODUCE</td></tr>
</table>

<table>
    <tr><td class="bar-blue" colspan="4">I. DATOS GENERALES</td></tr>
</table>

<table class="datos">
    <tr>
        <td class="label" width="15%">RAZÓN SOCIAL</td>
        <td width="35%">{{ $razon_social }}</td>
        <td class="label" width="10%">RUC</td>
        <td width="40%">{{ $ruc }}</td>
    </tr>
    <tr>
        <td class="label">NOMBRE DEL EMPRESARIO</td>
        <td>{{ $nombre_completo }}</td>
        <td class="label">CORREO</td>
        <td>{{ $email }}</td>
    </tr>
    <tr>
        <td class="label">CELULAR</td>
        <td colspan="3">{{ $celular }}</td>
    </tr>
</table>

<table>
    <tr><td class="bar-blue" colspan="4">II. PROPUESTA DEL PLAN DE ACCIÓN</td></tr>
</table>

<table class="rows">
    <tr>
        <td class="bar-red" width="5%">N°</td>
        <td class="bar-red" width="35%">ACTIVIDADES Y/O CAPACITACIONES</td>
        <td class="bar-red" width="15%">FECHA DE SESIÓN</td>
        <td class="bar-red" width="10%">ESTADO</td>
        <td class="bar-red" width="35%">COMENTARIOS</td>
    </tr>

    @php $n = 1; @endphp

    @foreach ($cursos as $curso)
        <tr>
            <td class="center">{{ $n++ }}</td>
            <td class="actividad">{{ $curso['titulo'] }}</td>
            <td class="center">{{ $curso['fecha'] }}</td>
            <td class="center">{{ $curso['estado'] }}</td>
            <td>{{ $curso['comentario'] }}</td>
        </tr>
    @endforeach

    @foreach ($eventos_correo as $evento)
        <tr>
            <td class="center">{{ $n++ }}</td>
            <td class="destacado">{{ $evento['titulo'] }}</td>
            <td class="center">{{ $evento['fecha'] }}</td>
            <td class="center">{{ $evento['estado'] }}</td>
            <td class="destacado">{{ $evento['comentario'] }}</td>
        </tr>
    @endforeach
</table>

</body>
</html>