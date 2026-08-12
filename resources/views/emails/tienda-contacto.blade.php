<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gracias por tu interés</title>
</head>

<body style="margin:0; padding: 30px; background:#f4f4f4; font-family:Arial, Helvetica, sans-serif; color:#333;">

    <div style="max-width:650px; margin:30px auto; background:#ffffff; padding:35px; border-radius:12px;">

        <h2 style="margin-top:0;">
            ¡Hola {{ $contacto->nombre }}!
        </h2>

        <p>
            🙌 ¡Gracias por interesarte en el trabajo de nuestras micro y pequeñas empresas!
        </p>

        <p>
            Desde el <strong>Programa Nacional Tu Empresa del Ministerio de la Producción</strong>,
            generamos espacios que buscan conectar a las MYPE con nuevas oportunidades y acercar
            sus productos a más personas.
        </p>

        <p>
            Detrás de cada producto hay una historia, esfuerzo y el sueño de un emprendedor
            que apuesta cada día por hacer crecer su negocio. Nos alegra que uno de sus
            productos haya despertado tu interés.
        </p>

        <p>
            Hemos registrado tus datos y próximamente la MYPE podrá ponerse en contacto contigo
            para brindarte mayor información y atender tus consultas.
        </p>

        <p>
            Mientras tanto, puedes conocer más sobre ella y sus productos:
        </p>

        <div style="
            margin:25px 0;
            padding:20px;
            background:#f8f8f8;
            border-radius:10px;
        ">

            <h3 style="margin-top:0;">
                {{ $tienda->nombre }}
            </h3>

            @php
                $socials = $tienda->socials ?? [];
            @endphp

            @foreach ($socials as $social)

                @if (!empty($social['name']) && !empty($social['link']))

                    @php
                        $name = strtolower($social['name']);
                    @endphp

                    <p style="margin:12px 0;">

                        @if ($name === 'facebook')
                            🔵
                        @elseif ($name === 'instagram')
                            📸
                        @elseif ($name === 'tiktok')
                            🎵
                        @else
                            🔗
                        @endif

                        <strong>{{ $social['name'] }}:</strong>

                        <a
                            href="{{ $social['link'] }}"
                            target="_blank"
                            style="color:#1677ff; text-decoration:none;"
                        >
                            {{ $social['link'] }}
                        </a>

                    </p>

                @endif

            @endforeach

        </div>

        <p>
            ✨ Gracias por apoyar a nuestras MYPE y por ser parte de estas conexiones
            que generan nuevas oportunidades.
        </p>

        <p style="margin-top:30px;">
            <strong>Programa Nacional Tu Empresa</strong><br>
            Ministerio de la Producción
        </p>

        <p style="color:#777; font-size:13px;">
            Conectamos MYPE con oportunidades.
        </p>

    </div>

</body>

</html>