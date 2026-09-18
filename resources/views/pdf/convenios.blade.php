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
        }

        td,
        th {
            padding: 6px 8px;
        }

        .table td {
            border: 1px solid #dddddd;
        }

        .label-cell {
            background-color: #fafafa;
            font-weight: 700;
            width: 22%;
        }

        .label-cell strong {
            font-size: 10px;
        }

        .estado-text {
            font-size: 10px;
        }

        strong {
            font-weight: 400;
        }

        .title-entity {
            color: #009ed0;
            font-size: 14px;
            margin: 0 0 10px 0;
        }

        .mini-label {
            font-size: 10px;
            color: #888888;
        }

        .badge-plan {
            display: inline-block;
            border: 1px solid #b7eb8f;
            background-color: #f6ffed;
            color: #389e0d;
            border-radius: 4px;
            padding: 1px 8px;
            font-size: 11px;
        }

        .badge-plan-no {
            border-color: #ffa39e;
            background-color: #fff1f0;
            color: #cf1322;
        }

        .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .acciones {
            line-height: 1.4;
        }

        .acciones h4 {
            margin-top: 0;
        }

        .acciones-box {
            border: 1px solid rgba(12, 12, 12, 0.14);
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

    <h4 class="title-entity">{{ $entity }}</h4>

    <table class="table" border="1" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="label-cell"><strong>ENTIDAD ALIADA</strong></td>
            <td style="width: 38%;">{{ $entity }}</td>
            <td class="label-cell"><strong>ESTADO DEL CONVENIO</strong></td>
            <td><span class="dot" style="background-color: {{ $estadoColor }};"></span><span class="estado-text">{{ $estado }}</span></td>
        </tr>

        <tr>
            <td class="label-cell"><strong>NOMBRE DEL CONVENIO</strong></td>
            <td colspan="3">{{ $nombre ?? '' }}</td>
        </tr>

        <tr>
            <td class="label-cell"><strong>FECHA DE SUSCRIPCIÓN</strong></td>
            <td>{{ $fechaSuscripcion }}</td>
            <td class="label-cell"><strong>FECHA DE CULMINACIÓN</strong></td>
            <td>{{ $finConvenio }}</td>
        </tr>

        <tr>
            <td class="label-cell"><strong>FECHA DE ADENDA</strong></td>
            <td colspan="3">{{ $fechaAdenda }}</td>
        </tr>

        <tr>
            <td class="label-cell"><strong>PERIODO DE VIGENCIA</strong></td>
            <td colspan="3">{{ $periodoVigencia ?? '' }}</td>
        </tr>

        <tr>
            <td class="label-cell"><strong>PROFESIONAL RESPONSABLE</strong></td>
            <td colspan="3">
                <div class="mini-label">TITULAR:</div>
                <div>{{ $titular ?? '' }}</div>
                @if (!empty($alternos))
                    <div class="mini-label" style="margin-top: 4px;">ALTERNOS:</div>
                    @foreach ($alternos as $alterno)
                        <div>{{ $loop->iteration }}. {{ $alterno }}</div>
                    @endforeach
                @endif
            </td>
        </tr>

        <tr>
            <td class="label-cell"><strong>RESPONSABLE ENTIDAD ALIADA</strong></td>
            <td colspan="3">
                <div class="mini-label">TITULAR:</div>
                <div>{{ $titularAliado ?? '' }}</div>
                @if (!empty($alternosAliados))
                    <div class="mini-label" style="margin-top: 4px;">ALTERNOS:</div>
                    @foreach ($alternosAliados as $alterno)
                        <div>{{ $loop->iteration }}. {{ $alterno }}</div>
                    @endforeach
                @endif
            </td>
        </tr>

        <tr>
            <td class="label-cell"><strong>PLAN DE TRABAJO</strong></td>
            <td colspan="3">
                @if ($planTrabajo === '-')
                    -
                @else
                    <span class="badge-plan {{ $planTrabajo === 'NO' ? 'badge-plan-no' : '' }}">{{ $planTrabajo }}</span>
                @endif
            </td>
        </tr>

        {{-- <tr>
            <td class="label-cell"><strong>Archivos</strong></td>
            <td colspan="3">
                @forelse ($archivos ?? [] as $archivo)
                    <div>{{ $archivo->name ?? '' }}</div>
                @empty
                    -
                @endforelse
            </td>
        </tr> --}}
    </table>

    <div>
        @if (!empty($compromisos) && $compromisos->isNotEmpty())
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
                            {{ optional($compromiso->profile)->name }} {{ optional($compromiso->profile)->lastname }}
                            {{ optional($compromiso->profile)->middlename }}
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
                                                        {{ optional($accion->profile)->name }} {{ optional($accion->profile)->lastname }}
                                                        {{ optional($accion->profile)->middlename }}
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
