<?php

namespace Tests\Feature;

use App\Models\ActividadPnte;
use App\Models\ActividadPntePapa;
use App\Models\Archivo;
use App\Models\ArchivoFeria;
use App\Models\Empresario;
use App\Models\EmpresarioActividad;
use App\Models\EmpresarioEmprendimiento;
use App\Models\Image;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditarDatosFerianteTest extends TestCase
{
    private Empresario $participante;
    private ActividadPnte $actividad;
    private EmpresarioActividad $inscripcion;
    private EmpresarioEmprendimiento $negocio;

    protected function setUp(): void
    {
        parent::setUp();
        // Esta suite usa exclusivamente una base desechable en memoria; no ejecuta migraciones.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Storage::fake('public');

        foreach ([new Empresario, new EmpresarioEmprendimiento, new Archivo, new Image] as $model) {
            Schema::create($model->getTable(), function (Blueprint $table) use ($model) {
                $table->id();
                foreach ($model->getFillable() as $field) {
                    $table->text($field)->nullable();
                }
                $table->timestamps();
            });
        }
        Schema::create('actividades_pnte', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('tema')->nullable();
            $table->text('fechas')->nullable();
            $table->string('especial')->nullable();
            $table->string('descripcion')->nullable();
            $table->integer('prendido')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('empresario_actividad', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresario_id');
            $table->unsignedBigInteger('actividad_id');
            $table->string('slug');
            $table->string('numero_dni')->nullable();
            $table->string('fecha_asistencia')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('actividad_pnte_papa', function (Blueprint $table) {
            $table->id();
            foreach (['empresario_id', 'actividad_id', 'prioridad_1', 'prioridad_2', 'id_image_1', 'id_image_2'] as $field) {
                $table->unsignedBigInteger($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('archivos_ferias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('archivo_id');
            $table->unsignedBigInteger('empresario_id');
            $table->timestamps();
        });
        foreach ([
            \App\Models\EconomicSector::class, \App\Models\Category::class, \App\Models\Activity::class,
            \App\Models\City::class, \App\Models\Province::class, \App\Models\District::class,
            \App\Models\Typedocument::class, \App\Models\Gender::class, \App\Models\RoleCompany::class, \App\Models\Country::class,
        ] as $modelClass) {
            $name = (new $modelClass)->getTable();
            Schema::create($name, function (Blueprint $table) { $table->id(); });
            DB::table($name)->insert(['id' => 1]);
        }

        $this->participante = Empresario::create([
            'numero_dni' => '12345678', 'tipo_documento_id' => 1, 'nombres' => 'Nombre original',
            'apellido_paterno' => 'Paterno', 'apellido_materno' => 'Materno', 'celular' => '912345678',
            'fecha_nacimiento' => '0086-09-11', 'edad' => '40', 'discapacidad' => 0, 'pais_id' => 1,
        ]);
        $this->actividad = ActividadPnte::create(['slug' => 'feria-ejemplo', 'tema' => 'Feria', 'fechas' => ['2020-01-01']]);
        $this->inscripcion = EmpresarioActividad::create([
            'empresario_id' => $this->participante->id, 'actividad_id' => $this->actividad->id,
            'slug' => $this->actividad->slug, 'numero_dni' => '12345678', 'fecha_asistencia' => 'Asistencia original',
        ]);
        $this->negocio = EmpresarioEmprendimiento::create([
            'empresario_id' => $this->participante->id, 'actividad_id' => $this->actividad->id,
            'cap_prod_mensual' => '50 dos', 'porc_prod_planta' => '100porciento', 'pos' => 1,
            'pertenece_gremio' => 1, 'nombre_gremio' => 'Gremio original', 'terminos_condiciones' => 1,
            'redes_sociales' => [['name' => 'Facebook', 'link' => 'Cuenta sin URL']],
        ]);
    }

    private function payload(array $changes = []): array
    {
        return array_merge(['id' => $this->inscripcion->id, 'slug' => $this->actividad->slug], $changes);
    }

    public function test_edita_el_mismo_participante_y_marca_origen_f_sin_recrear_inscripciones(): void
    {
        $response = $this->putJson('/api/public/editar-datos-feriante', $this->payload([
            'nombres' => 'Nombre editado', 'discapacidad' => 0, 'pos' => 'NO', 'cap_prod_mensual' => '60 docenas',
        ]));
        $response->assertOk()->assertJsonPath('status', 200)->assertJsonPath('data.id', $this->inscripcion->id);
        $this->assertSame('Nombre editado', $this->participante->fresh()->nombres);
        $this->assertSame('F', $this->participante->fresh()->origen);
        $this->assertSame('0086-09-11', $this->participante->fresh()->fecha_nacimiento);
        $this->assertSame('1', (string) $this->participante->fresh()->pais_id);
        $this->assertFalse($this->negocio->fresh()->pos);
        $this->assertSame('60 docenas', $this->negocio->fresh()->cap_prod_mensual);
        $this->assertSame('100porciento', $this->negocio->fresh()->porc_prod_planta);
        $this->assertSame('Asistencia original', $this->inscripcion->fresh()->fecha_asistencia);
        $this->assertSame(1, Empresario::count());
        $this->assertSame(1, EmpresarioActividad::count());
        $this->assertSame(1, EmpresarioEmprendimiento::count());
    }

    public function test_no_modifica_el_emprendimiento_de_otra_actividad(): void
    {
        $otro = EmpresarioEmprendimiento::create([
            'empresario_id' => $this->participante->id, 'actividad_id' => 999, 'desc_negocio' => 'Otro evento',
        ]);
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['desc_negocio' => 'Descripción editada']))->assertOk();
        $this->assertSame('Descripción editada', $this->negocio->fresh()->desc_negocio);
        $this->assertSame('Otro evento', $otro->fresh()->desc_negocio);
    }

    public function test_normaliza_si_no_y_otro_sin_perder_el_valor_tres(): void
    {
        $this->putJson('/api/public/editar-datos-feriante', $this->payload([
            'tiene_tiendas' => 'OTRO', 'nombre_tienda' => 'Tienda virtual',
            'yape_plim' => false, 'terminos_condiciones' => false,
        ]))->assertOk()->assertJsonPath('data.emprendimiento.tiene_tiendas', 3);
        $this->assertSame('3', (string) $this->negocio->fresh()->getRawOriginal('tiene_tiendas'));
        $this->assertFalse($this->negocio->fresh()->yape_plim);
        $this->assertSame('0', (string) $this->negocio->fresh()->terminos_condiciones);
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['pertenece_gremio' => 0]))->assertOk();
        $this->assertNull($this->negocio->fresh()->nombre_gremio);
    }

    public function test_rechaza_un_id_o_feria_que_no_corresponde(): void
    {
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['slug' => 'otra-feria', 'nombres' => 'No guardar']))->assertNotFound();
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['empresario_id' => 999, 'nombres' => 'No guardar']))->assertNotFound();
        $this->assertSame('Nombre original', $this->participante->fresh()->nombres);
        $this->assertNull($this->participante->fresh()->origen);
    }

    public function test_cambiar_documento_actualiza_el_empresario_y_sus_inscripciones_sin_crear_otro(): void
    {
        $otraInscripcion = EmpresarioActividad::create([
            'empresario_id' => $this->participante->id, 'actividad_id' => 999, 'slug' => 'otra-feria', 'numero_dni' => '12345678',
        ]);
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['numero_dni' => '87654321']))->assertOk();
        $this->assertSame('87654321', $this->participante->fresh()->numero_dni);
        $this->assertSame('87654321', $this->inscripcion->fresh()->numero_dni);
        $this->assertSame('87654321', $otraInscripcion->fresh()->numero_dni);
        $this->assertSame(1, Empresario::count());
    }

    public function test_rechaza_documentos_duplicados_y_revierten_los_otros_cambios(): void
    {
        Empresario::create(['numero_dni' => '87654321']);
        $this->putJson('/api/public/editar-datos-feriante', $this->payload([
            'numero_dni' => '87654321', 'nombres' => 'No guardar', 'desc_negocio' => 'No guardar',
        ]))->assertUnprocessable()->assertJsonValidationErrors('numero_dni');
        $this->assertSame('Nombre original', $this->participante->fresh()->nombres);
        $this->assertNull($this->negocio->fresh()->desc_negocio);
        $this->assertNull($this->participante->fresh()->origen);
    }

    public function test_corrige_fecha_en_formato_dia_mes_ano_y_calcula_edad(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 23));
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['fecha_nacimiento' => '11/09/1986']))->assertOk();
        $this->assertSame('1986-09-11', $this->participante->fresh()->fecha_nacimiento);
        $this->assertSame('40', (string) $this->participante->fresh()->edad);
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['fecha_nacimiento' => '31/02/1986']))
            ->assertUnprocessable()->assertJsonValidationErrors('fecha_nacimiento');
        $this->travelBack();
    }

    public function test_no_permite_borrar_un_detalle_requerido_y_valida_catalogos(): void
    {
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['nombre_gremio' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('nombre_gremio');
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['cargo_empresa_id' => 999]))
            ->assertUnprocessable()->assertJsonValidationErrors('cargo_empresa_id');
        $this->assertSame('Gremio original', $this->negocio->fresh()->nombre_gremio);
    }

    public function test_actualiza_redes_como_json_sin_exigir_que_los_textos_antiguos_sean_urls(): void
    {
        $redes = [['name' => 'Facebook', 'link' => 'Mi cuenta comercial']];
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['redes_sociales' => json_encode($redes)]))->assertOk();
        $this->assertSame($redes, $this->negocio->fresh()->redes_sociales);
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['redes_sociales' => []]))->assertOk();
        $this->assertSame([], $this->negocio->fresh()->redes_sociales);
    }

    public function test_conserva_los_adjuntos_existentes_y_solo_reemplaza_si_llegan_nuevos(): void
    {
        $this->actividad->update(['especial' => 'feria_papa']);
        $image = Image::create(['name' => 'Anterior', 'url' => 'imagenes_papa/anterior.png']);
        $papa = ActividadPntePapa::create([
            'empresario_id' => $this->participante->id, 'actividad_id' => $this->actividad->id,
            'prioridad_1' => 1, 'prioridad_2' => 2, 'id_image_1' => $image->id,
        ]);
        $archivo = Archivo::create(['nombre_original' => 'Anterior.pdf', 'ruta' => 'reportes_tributarios/anterior.pdf']);
        ArchivoFeria::create(['archivo_id' => $archivo->id, 'empresario_id' => $this->participante->id]);
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['nombres' => 'Editado']))
            ->assertOk()->assertJsonPath('data.archivo.archivo_id', $archivo->id);
        $this->assertSame((string) $image->id, (string) $papa->fresh()->id_image_1);
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['feria_opcional' => 1]))
            ->assertUnprocessable()->assertJsonValidationErrors('feria_principal');
        $response = $this->put('/api/public/editar-datos-feriante', $this->payload([
            'reporte_tributario' => UploadedFile::fake()->create('Nuevo.pdf', 10, 'application/pdf'),
            'imagenes_papa' => [UploadedFile::fake()->image('Nuevo.png')], 'feria_principal' => 3, 'feria_opcional' => null,
        ]), ['Accept' => 'application/json']);
        $response->assertOk()->assertJsonPath('data.archivo.nombre_original', 'Nuevo.pdf');
        $this->assertSame('3', (string) $papa->fresh()->prioridad_1);
        $this->assertNull($papa->fresh()->prioridad_2);
        $this->assertNotEquals($image->id, $papa->fresh()->id_image_1);
        $this->assertSame(1, ActividadPntePapa::count());
        $this->assertSame(2, count(Storage::disk('public')->allFiles()));
    }

    public function test_un_archivo_invalido_no_guarda_los_datos_personales(): void
    {
        $this->put('/api/public/editar-datos-feriante', $this->payload([
            'nombres' => 'No guardar', 'reporte_tributario' => UploadedFile::fake()->create('No.txt', 1, 'text/plain'),
        ]), ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('reporte_tributario');
        $this->assertSame('Nombre original', $this->participante->fresh()->nombres);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_el_envio_multipart_post_resuelve_la_ruta_put(): void
    {
        \Illuminate\Http\Request::enableHttpMethodParameterOverride();
        $this->post('/api/public/editar-datos-feriante', $this->payload([
            '_method' => 'PUT', 'nombres' => 'Editado con PDF',
            'reporte_tributario' => UploadedFile::fake()->create('Reporte.pdf', 10, 'application/pdf'),
        ]), ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.archivo.nombre_original', 'Reporte.pdf');
        $this->assertSame('Editado con PDF', $this->participante->fresh()->nombres);
        $this->assertSame('F', $this->participante->fresh()->origen);
    }

    public function test_acepta_un_emprendimiento_ausente_solo_para_el_participante_existente(): void
    {
        $this->negocio->delete();
        $this->putJson('/api/public/editar-datos-feriante', $this->payload(['desc_negocio' => 'Nueva información']))->assertOk();
        $this->assertSame(1, Empresario::count());
        $this->assertSame(1, EmpresarioActividad::count());
        $this->assertSame(1, EmpresarioEmprendimiento::count());
        $this->assertSame('Nueva información', EmpresarioEmprendimiento::first()->desc_negocio);
    }

    public function test_limpia_archivos_nuevos_y_revierte_la_edicion_si_falla_la_transaccion(): void
    {
        DB::statement("CREATE TRIGGER fallar_archivo BEFORE INSERT ON archivos BEGIN SELECT RAISE(FAIL, 'fallo controlado'); END");
        $this->put('/api/public/editar-datos-feriante', $this->payload([
            'nombres' => 'No guardar', 'reporte_tributario' => UploadedFile::fake()->create('Reporte.pdf', 10, 'application/pdf'),
        ]), ['Accept' => 'application/json'])->assertStatus(500);
        $this->assertSame('Nombre original', $this->participante->fresh()->nombres);
        $this->assertNull($this->participante->fresh()->origen);
        $this->assertSame(0, Archivo::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
