<!DOCTYPE html>
<html>

<head>
    <title>{{ $distrito }}</title>
</head>

<body>

    <style>
        table {
            font-size: 13px;

            border-collapse: collapse;
        }

        td,
        th {
            padding: 4px;
            font-family: arial, sans-serif;
        }

        td {
            border: 1px solid #dddddd;
        }

        strong {
            font-family: arial, sans-serif;
            font-weight: 400;
        }
    </style>

    <table border="1" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td><strong>Región</strong></td>
            <td>{{ $region }}</td>
            <td><strong>Provincia</strong></td>
            <td>{{ $provincia }}</td>
            <td><strong>Distrito</strong></td>
            <td>{{ $distrito }}</td>
        </tr>
        <tr>
            <td><strong>Inicio convenio</strong></td>
            <td>{{ $inicioConvenio }}</td>
            <td><strong>Fin convenio</strong></td>
            <td>{{ $finConvenio }}</td>
            <td><strong>Renovación</strong></td>
            <td>{{ $renovacion }}</td>
        </tr>
        <tr>
            <td><strong>Punto Focal</strong></td>
            <td>{{ $puntoFocal }}</td>
            <td><strong>Punto Focal cargo</strong></td>
            <td>{{ $puntoFocalCargo }}</td>
            <td><strong>Focal Num. celular</strong></td>
            <td>{{ $puntoFocalTelf }}</td>
        </tr>

        <tr>
            <td><strong>Representante Legal</strong></td>
            <td>{{ $aliado }}</td>
            <td><strong>Representante Legal Telf.</strong></td>
            <td>{{ $aliadoPhone }}</td>
            <td></td>
            <td></td>
        </tr>



        <tr>
            <td><strong>Comentarios</strong></td>
            <td colspan="5">{{ $detalles }}</td>
        </tr>
    </table>

</body>

</html>
