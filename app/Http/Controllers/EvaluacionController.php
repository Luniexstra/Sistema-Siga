<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use Illuminate\Http\Request;

class EvaluacionController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->currentUser($request);
        $query = Evaluacion::with('clase');

        if ($user->isInstructor()) {
            $query->whereHas('clase', fn ($clase) => $clase->where('instructor_id', $user->id));
        } elseif ($user->isAlumno()) {
            $query->whereHas('clase', fn ($clase) => $clase->where('alumno_id', $user->alumno?->id ?? 0));
        }

        return $query->get();
    }

    public function show(Request $request, $id)
    {
        $evaluacion = Evaluacion::with('clase')->findOrFail($id);
        $this->ensureClaseAccess($this->currentUser($request), $evaluacion->clase);

        return $evaluacion;
    }

    public function store(Request $request)
    {
        $data = $this->validateEvaluacion($request);
        $clase = \App\Models\Clase::findOrFail($data['clase_id']);
        $this->ensureInstructorOwnsClase($this->currentUser($request), $clase);
        $evaluacion = Evaluacion::create($this->buildEvaluacionPayload($data));

        return response()->json($evaluacion, 201);
    }

    public function update(Request $request, $id)
    {
        $evaluacion = Evaluacion::with('clase')->findOrFail($id);
        $this->ensureInstructorOwnsClase($this->currentUser($request), $evaluacion->clase);
        $data = $this->validateEvaluacion($request, $evaluacion);
        $clase = \App\Models\Clase::findOrFail($data['clase_id']);
        $this->ensureInstructorOwnsClase($this->currentUser($request), $clase);
        $evaluacion->update($this->buildEvaluacionPayload($data));

        return response()->json($evaluacion);
    }

    public function destroy(Request $request, $id)
    {
        $evaluacion = Evaluacion::with('clase')->findOrFail($id);
        $this->ensureInstructorOwnsClase($this->currentUser($request), $evaluacion->clase);
        $evaluacion->delete();

        return response()->json(['mensaje' => 'Evaluacion eliminada']);
    }

    protected function validateEvaluacion(Request $request, ?Evaluacion $evaluacion = null): array
    {
        $claseRule = 'unique:evaluaciones,clase_id';

        if ($evaluacion) {
            $claseRule .= ',' . $evaluacion->id;
        }

        return $request->validate([
            'clase_id' => ['required', 'integer', 'exists:clases,id', $claseRule],
            'senales' => ['required', 'integer', 'min:1', 'max:5'],
            'frenado' => ['required', 'integer', 'min:1', 'max:5'],
            'seguridad' => ['required', 'integer', 'min:1', 'max:5'],
        ]);
    }

    protected function buildEvaluacionPayload(array $data): array
    {
        $promedio = round(
            ($data['senales'] + $data['frenado'] + $data['seguridad']) / 3,
            2
        );

        $metricasCriticas = ['senales', 'frenado', 'seguridad'];
        $hayRiesgoCritico = collect($metricasCriticas)
            ->contains(fn ($metrica) => $data[$metrica] <= 2);

        if ($hayRiesgoCritico) {
            $nivel = 'rojo';
        } elseif ($promedio >= 4) {
            $nivel = 'verde';
        } elseif ($promedio >= 3) {
            $nivel = 'amarillo';
        } else {
            $nivel = 'rojo';
        }

        return [
            ...$data,
            'promedio' => $promedio,
            'nivel' => $nivel,
        ];
    }
}
