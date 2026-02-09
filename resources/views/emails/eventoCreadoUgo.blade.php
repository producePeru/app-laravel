<!DOCTYPE html>
<html>

<body style="font-family: Arial, sans-serif">

  <h3>
    {{ $isEdit ? 'Actividad actualizada' : 'Nueva actividad registrada' }}
  </h3>

  <p><strong>Título:</strong> {{ $attendance->title }}</p>
  <p><strong>Tema:</strong> {{ $attendance->theme }}</p>

  <p>
    <strong>Asesor:</strong>
    {{ $asesor->name }}
    {{ $asesor->middlename }}
    {{ $asesor->lastname }}
  </p>

  <p>
    <a href="{{ $link }}">Ver actividad</a>
  </p>

  @if($isEdit && count($changes))
  <hr>
  <h4>Campos modificados:</h4>
  <ul>
    @foreach($changes as $campo => $valores)
    <li>
      <strong>{{ $campo }}</strong>:
      {{ $valores['antes'] }} → {{ $valores['ahora'] }}
    </li>
    @endforeach
  </ul>
  @endif

</body>

</html>