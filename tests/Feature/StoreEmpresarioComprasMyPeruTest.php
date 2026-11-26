<?php

namespace Tests\Feature;

use App\Models\ActividadPnte;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StoreEmpresarioComprasMyPeruTest extends TestCase
{
    private ActividadPnte $actividad;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('empresarios', function (Blueprint $table) {
            $table->id();
            foreach ((new Empresario)->getFillable() as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('actividades_pnte', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->text('fechas');
            $table->boolean('switch_asistencias')->default(false);
            $table->unsignedInteger('total_participantes')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('empresario_actividad', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('empresario_id');
            $table->string('slug');
            $table->string('numero_dni');
            $table->date('fecha_seleccionada');
            $table->string('fecha_asistencia')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        $this->actividad = ActividadPnte::create(['slug' => 'curso-ejemplo', 'fechas' => ['2026-11-26']]);
    }

    private function payload(array $changes = []): array
    {
        return array_merge([
            'slug' => $this->actividad->slug,
            'ruc' => '10123456789', 'razon_social' => 'Empresa de ejemplo', 'nombre_comercial' => 'Marca nueva',
            'sector_economico_id' => 3, 'rubro_id' => 4, 'region_id' => 15, 'provincia_id' => 134,
            'distrito_id' => 1379, 'direccion' => 'Dirección nueva', 'pais_id' => 155,
            'tipo_documento_id' => 1, 'numero_dni' => '12345678', 'apellido_paterno' => 'Paterno',
            'apellido_materno' => 'Materno', 'nombres' => 'Nombre enviado', 'genero_id' => 2,
            'discapacidad' => 0, 'celular' => '999123456', 'correo_electronico' => 'participante@example.com',
            'actividad_comercial_nombre' => 'Venta de verduras', 'fecha_nacimiento' => '1990-08-08',
            'fecha_seleccionada' => '2026-11-26',
        ], $changes);
    }

    private function register(array $changes = [])
    {
        return $this->postJson('/api/event-pnte-public/registro-empresario-compras-myperu', $this->payload($changes));
    }

    public function test_completa_todos_los_datos_vacios_del_empresario_existente(): void
    {
        $empresario = Empresario::create([
            'tipo_documento_id' => 1, 'numero_dni' => '12345678', 'nombres' => 'Nombre original', 'razon_social' => '   ',
        ]);
        $this->register()->assertOk()->assertJsonPath('empresario_id', $empresario->id);
        $actual = $empresario->fresh();
        foreach ($this->payload() as $field => $value) {
            if (in_array($field, ['slug', 'fecha_seleccionada', 'nombres'], true)) {
                continue;
            }
            $this->assertSame((string) $value, (string) $actual->getAttribute($field), $field);
        }
        $this->assertSame('Nombre original', $actual->nombres);
        $this->assertSame(1, Empresario::count());
        $this->assertSame(1, EmpresarioActividad::count());
        $this->assertSame(1, $this->actividad->fresh()->total_participantes);
    }

    public function test_no_sobrescribe_valores_ya_llenos_ni_discapacidad_cero(): void
    {
        $original = [
            'tipo_documento_id' => 1, 'numero_dni' => '12345678', 'ruc' => '20123456789',
            'nombres' => 'Conservar', 'razon_social' => 'Empresa original', 'celular' => '912345678',
            'correo_electronico' => 'original@example.com', 'discapacidad' => 0, 'direccion' => 'Dirección original',
            'pais_id' => 200, 'fecha_nacimiento' => '0086-09-11',
        ];
        $empresario = Empresario::create($original);
        $this->register(['discapacidad' => 1])->assertOk();
        foreach ($original as $field => $value) {
            $this->assertSame((string) $value, (string) $empresario->fresh()->getAttribute($field), $field);
        }
    }

    public function test_no_rellena_un_campo_vacio_con_otro_valor_vacio(): void
    {
        $empresario = Empresario::create(['tipo_documento_id' => 1, 'numero_dni' => '12345678']);
        $this->register(['ruc' => null, 'correo_electronico' => '', 'apellido_materno' => '   ', 'fecha_nacimiento' => null])->assertOk();
        foreach (['ruc', 'correo_electronico', 'apellido_materno', 'fecha_nacimiento', 'cargo_empresa_id', 'edad'] as $field) {
            $this->assertNull($empresario->fresh()->getAttribute($field), $field);
        }
        $this->assertSame('Nombre enviado', $empresario->fresh()->nombres);
    }

    public function test_cero_del_payload_si_completa_un_campo_nulo(): void
    {
        $empresario = Empresario::create(['tipo_documento_id' => 1, 'numero_dni' => '12345678', 'discapacidad' => null]);
        $this->register(['discapacidad' => 0])->assertOk();
        $this->assertSame('0', (string) $empresario->fresh()->discapacidad);
    }

    public function test_completa_el_tipo_documento_faltante_sin_duplicar_al_empresario(): void
    {
        $empresario = Empresario::create(['numero_dni' => '12345678', 'tipo_documento_id' => null]);
        $this->register()->assertOk()->assertJsonPath('empresario_id', $empresario->id);
        $this->assertSame('1', (string) $empresario->fresh()->tipo_documento_id);
        $this->assertSame('Marca nueva', $empresario->fresh()->nombre_comercial);
        $this->assertSame(1, Empresario::count());
    }

    public function test_prefiere_la_identidad_exacta_y_no_mezcla_tipos_de_documentos(): void
    {
        $sinTipo = Empresario::create(['numero_dni' => '12345678']);
        $dni = Empresario::create(['numero_dni' => '12345678', 'tipo_documento_id' => 1]);
        $this->register()->assertOk()->assertJsonPath('empresario_id', $dni->id);
        $this->assertNull($sinTipo->fresh()->tipo_documento_id);
        $this->assertSame('Nombre enviado', $dni->fresh()->nombres);
    }

    public function test_conserva_la_validacion_de_inscripcion_duplicada_y_su_rollback(): void
    {
        $empresario = Empresario::create(['numero_dni' => '12345678', 'tipo_documento_id' => 1]);
        EmpresarioActividad::create([
            'empresario_id' => $empresario->id, 'actividad_id' => $this->actividad->id,
            'slug' => $this->actividad->slug, 'numero_dni' => '12345678', 'fecha_seleccionada' => '2026-11-26',
        ]);
        $this->register()->assertUnprocessable();
        $this->assertNull($empresario->fresh()->correo_electronico);
        $this->assertSame(1, EmpresarioActividad::count());
        $this->assertSame(0, $this->actividad->fresh()->total_participantes);
    }

    public function test_los_participantes_nuevos_siguen_guardandose_con_todo_el_payload(): void
    {
        $this->register()->assertOk();
        $empresario = Empresario::firstOrFail();
        $this->assertSame('Nombre enviado', $empresario->nombres);
        $this->assertSame('Venta de verduras', $empresario->actividad_comercial_nombre);
        $this->assertSame('1990-08-08', $empresario->fecha_nacimiento);
        $this->assertSame(1, Empresario::count());
        $this->assertSame(1, EmpresarioActividad::count());
    }

    public function test_guarda_el_cargo_institucional_personalizado_como_texto(): void
    {
        $this->register(['coop_rol_institucion' => 'Coordinadora de educación'])->assertOk();

        $this->assertDatabaseHas('empresarios', [
            'numero_dni' => '12345678',
            'coop_rol_institucion' => 'Coordinadora de educación',
            'cargo_empresa_id' => null,
        ]);
    }

    public function test_rechaza_un_cargo_institucional_mayor_a_255_caracteres(): void
    {
        $this->register(['coop_rol_institucion' => str_repeat('a', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('coop_rol_institucion');
    }
}
