<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Clase;
use App\Models\Pago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function apiHeadersFor(User $user): array
    {
        return ['X-User-Id' => $user->id];
    }

    public function test_it_creates_an_alumno_with_required_fields(): void
    {
        $recepcionista = User::factory()->create([
            'role' => User::ROLE_RECEPCIONISTA,
        ]);

        $response = $this->withHeaders($this->apiHeadersFor($recepcionista))->postJson('/api/alumnos', [
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'curp' => 'PEPJ800101HDFABC01',
            'telefono' => '5512345678',
            'correo' => 'juan@example.com',
            'fecha_ingreso' => '2026-04-16',
            'costo_total' => 3500,
        ]);

        $response->assertCreated()
            ->assertJsonPath('nombre', 'Juan')
            ->assertJsonPath('costo_total', 3500);
    }

    public function test_it_prevents_overlapping_classes_for_the_same_instructor_or_student(): void
    {
        $recepcionista = User::factory()->create([
            'role' => User::ROLE_RECEPCIONISTA,
        ]);
        $instructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
        ]);
        $otroInstructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'curp' => 'LOPA800101MDFABC02',
            'telefono' => '5511111111',
            'correo' => 'ana@example.com',
            'fecha_ingreso' => '2026-04-16',
        ]);

        $otroAlumno = Alumno::create([
            'nombre' => 'Luis',
            'apellido' => 'Ramirez',
            'curp' => 'RARL800101HDFABC03',
            'telefono' => '5522222222',
            'correo' => 'luis@example.com',
            'fecha_ingreso' => '2026-04-16',
        ]);

        Clase::create([
            'fecha' => '2026-04-20',
            'hora' => '10:00',
            'alumno_id' => $alumno->id,
            'instructor_id' => $instructor->id,
        ]);

        $conflictoInstructor = $this->withHeaders($this->apiHeadersFor($recepcionista))->postJson('/api/clases', [
            'fecha' => '2026-04-20',
            'hora' => '10:00',
            'alumno_id' => $otroAlumno->id,
            'instructor_id' => $instructor->id,
        ]);

        $conflictoInstructor->assertStatus(422);

        $conflictoAlumno = $this->withHeaders($this->apiHeadersFor($recepcionista))->postJson('/api/clases', [
            'fecha' => '2026-04-20',
            'hora' => '10:00',
            'alumno_id' => $alumno->id,
            'instructor_id' => $otroInstructor->id,
        ]);

        $conflictoAlumno->assertStatus(422);
    }

    public function test_it_assigns_red_level_when_a_critical_metric_is_two_or_less(): void
    {
        $instructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
        ]);
        $alumno = Alumno::create([
            'nombre' => 'Mario',
            'apellido' => 'Garcia',
            'curp' => 'GARM800101HDFABC04',
            'telefono' => '5533333333',
            'correo' => 'mario@example.com',
            'fecha_ingreso' => '2026-04-16',
        ]);

        $clase = Clase::create([
            'fecha' => '2026-04-21',
            'hora' => '11:00',
            'alumno_id' => $alumno->id,
            'instructor_id' => $instructor->id,
        ]);

        $response = $this->withHeaders($this->apiHeadersFor($instructor))->postJson('/api/evaluaciones', [
            'clase_id' => $clase->id,
            'senales' => 5,
            'frenado' => 2,
            'seguridad' => 5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('promedio', 4)
            ->assertJsonPath('nivel', 'rojo');
    }

    public function test_it_creates_an_observacion_for_an_existing_clase(): void
    {
        $instructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
        ]);
        $alumno = Alumno::create([
            'nombre' => 'Elena',
            'apellido' => 'Soto',
            'curp' => 'SOEE800101MDFABC05',
            'telefono' => '5544444444',
            'correo' => 'elena@example.com',
            'fecha_ingreso' => '2026-04-16',
        ]);

        $clase = Clase::create([
            'fecha' => '2026-04-22',
            'hora' => '12:00',
            'alumno_id' => $alumno->id,
            'instructor_id' => $instructor->id,
        ]);

        $response = $this->withHeaders($this->apiHeadersFor($instructor))->postJson('/api/observaciones', [
            'clase_id' => $clase->id,
            'comentario' => 'El alumno necesita practicar mas estacionamiento.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('clase_id', $clase->id)
            ->assertJsonPath('comentario', 'El alumno necesita practicar mas estacionamiento.');
    }

    public function test_it_filters_observaciones_by_clase(): void
    {
        $instructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
        ]);
        $alumno = Alumno::create([
            'nombre' => 'Rosa',
            'apellido' => 'Diaz',
            'curp' => 'DIRR800101MDFABC06',
            'telefono' => '5555555555',
            'correo' => 'rosa@example.com',
            'fecha_ingreso' => '2026-04-16',
        ]);

        $claseUno = Clase::create([
            'fecha' => '2026-04-23',
            'hora' => '09:00',
            'alumno_id' => $alumno->id,
            'instructor_id' => $instructor->id,
        ]);

        $claseDos = Clase::create([
            'fecha' => '2026-04-24',
            'hora' => '09:00',
            'alumno_id' => $alumno->id,
            'instructor_id' => $instructor->id,
        ]);

        $this->withHeaders($this->apiHeadersFor($instructor))->postJson('/api/observaciones', [
            'clase_id' => $claseUno->id,
            'comentario' => 'Observacion clase uno.',
        ])->assertCreated();

        $this->withHeaders($this->apiHeadersFor($instructor))->postJson('/api/observaciones', [
            'clase_id' => $claseDos->id,
            'comentario' => 'Observacion clase dos.',
        ])->assertCreated();

        $response = $this->withHeaders($this->apiHeadersFor($instructor))
            ->getJson('/api/observaciones?clase_id=' . $claseUno->id);

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.clase_id', $claseUno->id);
    }

    public function test_it_creates_a_pago_and_generates_estado_de_cuenta(): void
    {
        $recepcionista = User::factory()->create([
            'role' => User::ROLE_RECEPCIONISTA,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Paola',
            'apellido' => 'Mendez',
            'curp' => 'MEPP800101MDFABC07',
            'telefono' => '5566666666',
            'correo' => 'paola@example.com',
            'fecha_ingreso' => '2026-04-16',
            'costo_total' => 5000,
        ]);

        $this->withHeaders($this->apiHeadersFor($recepcionista))->postJson('/api/pagos', [
            'alumno_id' => $alumno->id,
            'monto' => 1500,
            'fecha_pago' => '2026-04-18',
            'estado' => 'pagado',
        ])->assertCreated();

        $this->withHeaders($this->apiHeadersFor($recepcionista))->postJson('/api/pagos', [
            'alumno_id' => $alumno->id,
            'monto' => 500,
            'fecha_pago' => '2026-04-19',
            'estado' => 'pendiente',
        ])->assertCreated();

        $response = $this->withHeaders($this->apiHeadersFor($recepcionista))
            ->getJson('/api/alumnos/' . $alumno->id . '/estado-cuenta');

        $response->assertOk()
            ->assertJsonPath('resumen.costo_total', 5000)
            ->assertJsonPath('resumen.total_pagado', 1500)
            ->assertJsonPath('resumen.saldo_pendiente', 3500)
            ->assertJsonPath('resumen.estado_cuenta', 'pendiente')
            ->assertJsonCount(2, 'pagos');
    }

    public function test_it_generates_estado_de_cuenta_pdf(): void
    {
        $recepcionista = User::factory()->create([
            'role' => User::ROLE_RECEPCIONISTA,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Sofia',
            'apellido' => 'Navarro',
            'curp' => 'NASO800101MDFABC08',
            'telefono' => '5577777777',
            'correo' => 'sofia@example.com',
            'fecha_ingreso' => '2026-04-16',
            'costo_total' => 4200,
        ]);

        Pago::create([
            'alumno_id' => $alumno->id,
            'monto' => 2000,
            'fecha_pago' => '2026-04-20',
            'estado' => 'pagado',
        ]);

        $response = $this->withHeaders($this->apiHeadersFor($recepcionista))
            ->get('/api/alumnos/' . $alumno->id . '/estado-cuenta/pdf');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_it_blocks_a_recepcionista_from_creating_evaluaciones(): void
    {
        $recepcionista = User::factory()->create([
            'role' => User::ROLE_RECEPCIONISTA,
        ]);
        $instructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Diego',
            'apellido' => 'Torres',
            'curp' => 'TODD800101HDFABC09',
            'telefono' => '5588888888',
            'correo' => 'diego@example.com',
            'fecha_ingreso' => '2026-04-16',
        ]);

        $clase = Clase::create([
            'fecha' => '2026-04-25',
            'hora' => '13:00',
            'alumno_id' => $alumno->id,
            'instructor_id' => $instructor->id,
        ]);

        $response = $this->withHeaders($this->apiHeadersFor($recepcionista))->postJson('/api/evaluaciones', [
            'clase_id' => $clase->id,
            'senales' => 5,
            'frenado' => 5,
            'seguridad' => 5,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('current_role', User::ROLE_RECEPCIONISTA);
    }

    public function test_it_updates_and_deletes_an_alumno(): void
    {
        $recepcionista = User::factory()->create([
            'role' => User::ROLE_RECEPCIONISTA,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Lucia',
            'apellido' => 'Campos',
            'curp' => 'CALU800101MDFABC10',
            'telefono' => '5599999999',
            'correo' => 'lucia@example.com',
            'fecha_ingreso' => '2026-04-16',
            'costo_total' => 2500,
        ]);

        $updateResponse = $this->withHeaders($this->apiHeadersFor($recepcionista))->putJson('/api/alumnos/' . $alumno->id, [
            'nombre' => 'Lucia Maria',
            'apellido' => 'Campos',
            'curp' => 'CALU800101MDFABC10',
            'telefono' => '5599999999',
            'correo' => 'lucia@example.com',
            'fecha_ingreso' => '2026-04-16',
            'costo_total' => 3000,
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('nombre', 'Lucia Maria')
            ->assertJsonPath('costo_total', 3000);

        $deleteResponse = $this->withHeaders($this->apiHeadersFor($recepcionista))
            ->deleteJson('/api/alumnos/' . $alumno->id);

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('alumnos', ['id' => $alumno->id]);
    }

    public function test_an_instructor_can_update_and_delete_an_evaluacion(): void
    {
        $instructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
        ]);

        $alumno = Alumno::create([
            'nombre' => 'Nora',
            'apellido' => 'Salas',
            'curp' => 'SANO800101MDFABC11',
            'telefono' => '5510101010',
            'correo' => 'nora@example.com',
            'fecha_ingreso' => '2026-04-16',
        ]);

        $clase = Clase::create([
            'fecha' => '2026-04-26',
            'hora' => '14:00',
            'alumno_id' => $alumno->id,
            'instructor_id' => $instructor->id,
        ]);

        $evaluacion = $this->withHeaders($this->apiHeadersFor($instructor))->postJson('/api/evaluaciones', [
            'clase_id' => $clase->id,
            'senales' => 5,
            'frenado' => 4,
            'seguridad' => 5,
        ])->assertCreated()->json();

        $updateResponse = $this->withHeaders($this->apiHeadersFor($instructor))->putJson('/api/evaluaciones/' . $evaluacion['id'], [
            'clase_id' => $clase->id,
            'senales' => 5,
            'frenado' => 1,
            'seguridad' => 5,
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('nivel', 'rojo');

        $deleteResponse = $this->withHeaders($this->apiHeadersFor($instructor))
            ->deleteJson('/api/evaluaciones/' . $evaluacion['id']);

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('evaluaciones', ['id' => $evaluacion['id']]);
    }

    public function test_an_alumno_can_only_update_allowed_profile_fields(): void
    {
        $userAlumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
        ]);

        $alumno = Alumno::create([
            'user_id' => $userAlumno->id,
            'nombre' => 'Carla',
            'apellido' => 'Ruiz',
            'curp' => 'RUCX800101MDFABC12',
            'telefono' => '5512121212',
            'correo' => 'carla@example.com',
            'fecha_ingreso' => '2026-04-16',
            'costo_total' => 2800,
        ]);

        $response = $this->withHeaders($this->apiHeadersFor($userAlumno))->putJson('/api/alumnos/' . $alumno->id, [
            'correo' => 'carla.nuevo@example.com',
            'telefono' => '5534343434',
            'costo_total' => 9999,
            'nombre' => 'Cambio no permitido',
        ]);

        $response->assertOk()
            ->assertJsonPath('correo', 'carla.nuevo@example.com')
            ->assertJsonPath('telefono', '5534343434')
            ->assertJsonPath('nombre', 'Carla')
            ->assertJsonPath('costo_total', 2800);
    }

    public function test_an_alumno_can_only_list_their_own_pagos(): void
    {
        $userAlumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
        ]);

        $otroUserAlumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
        ]);

        $alumno = Alumno::create([
            'user_id' => $userAlumno->id,
            'nombre' => 'Julia',
            'apellido' => 'Mora',
            'curp' => 'MOJX800101MDFABC13',
            'telefono' => '5551112233',
            'correo' => 'julia@example.com',
            'fecha_ingreso' => '2026-04-16',
            'costo_total' => 3000,
        ]);

        $otroAlumno = Alumno::create([
            'user_id' => $otroUserAlumno->id,
            'nombre' => 'Tania',
            'apellido' => 'Paz',
            'curp' => 'PATX800101MDFABC14',
            'telefono' => '5554445566',
            'correo' => 'tania@example.com',
            'fecha_ingreso' => '2026-04-16',
            'costo_total' => 3200,
        ]);

        Pago::create([
            'alumno_id' => $alumno->id,
            'monto' => 1200,
            'fecha_pago' => '2026-04-20',
            'estado' => 'pagado',
        ]);

        Pago::create([
            'alumno_id' => $otroAlumno->id,
            'monto' => 900,
            'fecha_pago' => '2026-04-20',
            'estado' => 'pagado',
        ]);

        $response = $this->withHeaders($this->apiHeadersFor($userAlumno))
            ->getJson('/api/pagos');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.alumno_id', $alumno->id);
    }
}
