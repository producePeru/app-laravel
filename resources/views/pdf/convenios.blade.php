<!DOCTYPE html>
<html>

<head>
    <title>{{ $entity }}</title>
</head>

<body>

    <style>
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
        }

        .table {
            font-size: 12px;
            /* border-collapse: collapse; */
        }

        td,
        th {
            padding: 4px;
        }

        .table td {
            border: 1px solid #dddddd;
        }

        strong {
            font-weight: 400;
        }

        .acciones {
            /* margin: 1rem 0; */
            line-height: 1.4;
        }

        .acciones h4 {
            margin-top: 0;
        }

        .acciones-box {
            border: 1px solid rgba(12, 12, 12, 0.14);
            /* width: 100%; */
            margin-bottom: .7rem;
            border-radius: 8px;
            padding: .5rem;
        }

        .acciones-box div {
            font-size: 12px;
        }

        .acciones-bg {
            background-color: rgba(0, 0, 0, 0.02);
        }
    </style>

    <h4>Convenio: {{ $entity }}</h4>

    <table class="table" border="1" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td><strong>Región</strong></td>
            <td>{{ $region }}</td>
            <td><strong>Provincia</strong></td>
            <td>{{ $provincia }}</td>
            <td><strong>Distrito</strong></td>
            <td>{{ $distrito }}</td>
        </tr>

        <tr>
            <td><strong>RUC</strong></td>
            <td>{{ $ruc }}</td>
            <td><strong>Componente</strong></td>
            <td>{{ $componente }}</td>
            <td></td>
            <td></td>
        </tr>

        <tr>
            <td><strong>Inicio convenio</strong></td>
            <td>{{ $inicioConvenio }}</td>
            <td><strong>Fin convenio</strong></td>
            <td>{{ $finConvenio }}</td>
            <td><strong>Renovación</strong></td>
            <td>{{ $renovacion == 1 ? 'SI' : 'NO' }}</td>
        </tr>
        <tr>
            <td><strong>Punto Focal</strong></td>
            <td>{{ $puntoFocal }}</td>
            <td><strong>Punto Focal cargo</strong></td>
            <td>{{ $puntoFocalCargo }}</td>
            <td><strong>Focal Num. Telf.</strong></td>
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

    <div>
        @if ($compromisos->isNotEmpty())
            <h4>Compromisos</h4>


            @foreach ($compromisos as $compromiso)
                <div class="acciones-box" style="">
                    <div style="width: 100%;">

                        <div style="margin-bottom: 6px;">
                            <b>{{ $loop->iteration }}. </b>
                            Título: <b>{{ $compromiso->title }}</b>
                        </div>

                        <div>Tipo: <span style="text-transform: capitalize;">{{ $compromiso->type }}</span></div>

                        @if (!empty($compromiso->meta))
                            <div>Meta: {{ $compromiso->meta }}</div>
                        @endif
                        @if (!empty($compromiso->description))
                            <div>Descripción: {{ $compromiso->description }}</div>
                        @endif

                        <div>Registrado por:
                            {{ $compromiso->profile->name }} {{ $compromiso->profile->lastname }}
                            {{ $compromiso->profile->middlename }}
                        </div>

                        @if ($compromiso->acciones->isNotEmpty())
                            <div class="acciones">
                                <h4 style="margin: 10px 0;">Acciones</h4>

                                @foreach ($compromiso->acciones as $accion)
                                    <div class="acciones-box acciones-bg">

                                        <table width="100%">
                                            <tr>
                                                <td style="width: 4%">{{ $loop->iteration }}</td>
                                                <td style="width: 80%">
                                                    <div>Conferencia: {{ $accion->accion }}</div>
                                                    <div>Fecha:
                                                        {{ \Carbon\Carbon::parse($accion->date)->format('d/m/Y') }}
                                                    </div>
                                                    <div>Modalidad:
                                                        {{ $accion->modality == 'v' ? 'Virtual' : 'Presencial' }}</div>
                                                    @if (!empty($accion->address))
                                                        <div>Lugar: {{ $accion->address }}</div>
                                                    @endif
                                                    @if (!empty($accion->participants))
                                                        <div>Participantes: {{ $accion->participants }} personas</div>
                                                    @endif
                                                    @if (!empty($accion->details))
                                                        <div>Detalle: {{ $accion->details }}</div>
                                                    @endif
                                                    <div>Registrado por:
                                                        {{ $accion->profile->name }} {{ $accion->profile->lastname }}
                                                        {{ $accion->profile->middlename }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                    </div>
                                @endforeach

                            </div>
                        @endif
                    </div>
                </div>
            @endforeach



        @endif
    </div>

</body>

</html>
