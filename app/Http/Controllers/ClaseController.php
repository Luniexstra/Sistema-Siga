<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
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
}
