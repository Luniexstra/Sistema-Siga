<?php

namespace App\Http\Controllers;

use App\Models\Observacion;
use Illuminate\Http\Request;

class ObservacionController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->currentUser($request);
        $query = Observacion::with(['clase.alumno', 'clase.instructor']);

        if ($request->filled('clase_id')) {
            $request->validate([
                'clase_id' => ['integer', 'exists:clases,id'],
            ]);

            $query->where('clase_id', $request->integer('clase_id'));
        }

        if ($user->isInstructor()) {
            $query->whereHas('clase', fn ($clase) => $clase->where('instructor_id', $user->id));
        } elseif ($user->isAlumno()) {
            $query->whereHas('clase', fn ($clase) => $clase->where('alumno_id', $user->alumno?->id ?? 0));
        }

        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'clase_id' => ['required', 'integer', 'exists:clases,id'],
            'comentario' => ['required', 'string', 'max:2000'],
        ]);
        $clase = \App\Models\Clase::findOrFail($data['clase_id']);
        $this->ensureInstructorOwnsClase($this->currentUser($request), $clase);

        $observacion = Observacion::create($data);

        return response()->json(
            $observacion->load(['clase.alumno', 'clase.instructor']),
            201
        );
    }

    public function show(Request $request, $id)
    {
        $observacion = Observacion::with(['clase.alumno', 'clase.instructor'])
            ->findOrFail($id);
        $this->ensureClaseAccess($this->currentUser($request), $observacion->clase);

        return $observacion;
    }

    public function update(Request $request, $id)
    {
        $observacion = Observacion::with('clase')->findOrFail($id);
        $this->ensureInstructorOwnsClase($this->currentUser($request), $observacion->clase);

        $data = $request->validate([
            'clase_id' => ['required', 'integer', 'exists:clases,id'],
            'comentario' => ['required', 'string', 'max:2000'],
        ]);
        $clase = \App\Models\Clase::findOrFail($data['clase_id']);
        $this->ensureInstructorOwnsClase($this->currentUser($request), $clase);

        $observacion->update($data);

        return response()->json(
            $observacion->load(['clase.alumno', 'clase.instructor'])
        );
    }

    public function destroy(Request $request, $id)
    {
        $observacion = Observacion::with('clase')->findOrFail($id);
        $this->ensureInstructorOwnsClase($this->currentUser($request), $observacion->clase);
        $observacion->delete();

        return response()->json(['mensaje' => 'Observacion eliminada']);
    }
}
