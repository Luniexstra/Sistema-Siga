<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    private const FESTIVOS = [
        '2026-01-01',
        '2026-02-02',
        '2026-03-16',
        '2026-05-01',
        '2026-09-16',
        '2026-11-16',
        '2026-12-25',
    ];

    public function index(Request $request)
    {
        $user = $this->currentUser($request);
        $query = Clase::with(['alumno', 'instructor']);

        if ($user->isInstructor()) {
            $query->where('instructor_id', $user->id);
        } elseif ($user->isAlumno()) {
            $query->where('alumno_id', $user->alumno?->id ?? 0);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'alumno_id' => ['required', 'exists:alumnos,id'],
            'instructor_id' => ['required', 'exists:users,id'],
        ]);

        if ($errorResponse = $this->validarReglasClase($data['fecha'], $data['hora'])) {
            return $errorResponse;
        }

        $conflictoInstructor = Clase::where('fecha', $data['fecha'])
            ->where('hora', $data['hora'])
            ->where('instructor_id', $data['instructor_id'])
            ->exists();

        if ($conflictoInstructor) {
            return response()->json([
                'error' => 'El instructor ya tiene una clase en ese horario.',
            ], 422);
        }

        $conflictoAlumno = Clase::where('fecha', $data['fecha'])
            ->where('hora', $data['hora'])
            ->where('alumno_id', $data['alumno_id'])
            ->exists();

        if ($conflictoAlumno) {
            return response()->json([
                'error' => 'El alumno ya tiene una clase asignada en ese horario.',
            ], 422);
        }

        $clase = Clase::create($data);

        return response()->json($clase, 201);
    }

    public function show(Request $request, $id)
    {
        $clase = Clase::with(['alumno', 'instructor'])->findOrFail($id);
        $this->ensureClaseAccess($this->currentUser($request), $clase);

        return $clase;
    }

    public function update(Request $request, $id)
    {
        $clase = Clase::findOrFail($id);

        $data = $request->validate([
            'fecha' => ['sometimes', 'required', 'date'],
            'hora' => ['sometimes', 'required', 'date_format:H:i'],
            'alumno_id' => ['sometimes', 'required', 'exists:alumnos,id'],
            'instructor_id' => ['sometimes', 'required', 'exists:users,id'],
        ]);

        $payload = [
            'fecha' => $data['fecha'] ?? $clase->fecha,
            'hora' => $data['hora'] ?? $clase->hora,
            'alumno_id' => $data['alumno_id'] ?? $clase->alumno_id,
            'instructor_id' => $data['instructor_id'] ?? $clase->instructor_id,
        ];

        if ($errorResponse = $this->validarReglasClase($payload['fecha'], $payload['hora'])) {
            return $errorResponse;
        }

        $conflictoInstructor = Clase::where('fecha', $payload['fecha'])
            ->where('hora', $payload['hora'])
            ->where('instructor_id', $payload['instructor_id'])
            ->where('id', '!=', $clase->id)
            ->exists();

        if ($conflictoInstructor) {
            return response()->json([
                'error' => 'El instructor ya tiene una clase en ese horario.',
            ], 422);
        }

        $conflictoAlumno = Clase::where('fecha', $payload['fecha'])
            ->where('hora', $payload['hora'])
            ->where('alumno_id', $payload['alumno_id'])
            ->where('id', '!=', $clase->id)
            ->exists();

        if ($conflictoAlumno) {
            return response()->json([
                'error' => 'El alumno ya tiene una clase asignada en ese horario.',
            ], 422);
        }

        $clase->update($payload);

        return response()->json($clase);
    }

    public function destroy($id)
    {
        $clase = Clase::findOrFail($id);
        $clase->delete();

        return response()->json(['mensaje' => 'Clase eliminada']);
    }

    private function validarReglasClase(string $fecha, string $hora)
    {
        $fechaClase = Carbon::parse($fecha);

        if ($fechaClase->isPast()) {
            return response()->json([
                'error' => 'No puedes seleccionar fechas pasadas.',
            ], 422);
        }

        if ($fechaClase->isWeekend()) {
            return response()->json([
                'error' => 'Solo se permiten dias laborales de lunes a viernes.',
            ], 422);
        }

        if ($hora < '08:00' || $hora > '18:00') {
            return response()->json([
                'error' => 'Horario permitido: 08:00 a 18:00.',
            ], 422);
        }

        if (in_array($fecha, self::FESTIVOS)) {
            return response()->json([
                'error' => 'Dia festivo no disponible.',
            ], 422);
        }

        return null;
    }
}
