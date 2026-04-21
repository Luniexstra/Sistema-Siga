<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlumnoController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->currentUser($request);

        if ($user->isAlumno()) {
            return $user->alumno ? [$user->alumno] : [];
        }

        return Alumno::with('user')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', \App\Models\User::ROLE_ALUMNO)),
                'unique:alumnos,user_id',
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'curp' => ['required', 'string', 'size:18', 'unique:alumnos,curp'],
            'telefono' => ['required', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:255', 'unique:alumnos,correo'],
            'fecha_ingreso' => ['required', 'date'],
            'costo_total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['costo_total'] = $data['costo_total'] ?? 0;

        $alumno = Alumno::create($data);

        return response()->json($alumno->load('user'), 201);
    }

    public function show(Request $request, $id)
    {
        $alumno = Alumno::with('user')->findOrFail($id);
        $this->ensureAlumnoOwnership($this->currentUser($request), $alumno);

        return $alumno;
    }

    public function update(Request $request, $id)
    {
        $alumno = Alumno::findOrFail($id);
        $user = $this->currentUser($request);
        $this->ensureAlumnoOwnership($user, $alumno);

        if ($user->isAlumno()) {
            $data = $request->validate([
                'telefono' => ['sometimes', 'required', 'string', 'max:20'],
                'correo' => ['sometimes', 'required', 'email', 'max:255', 'unique:alumnos,correo,' . $alumno->id],
            ]);
        } else {
            $data = $request->validate([
                'user_id' => [
                    'sometimes',
                    'nullable',
                    'integer',
                    Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_ALUMNO)),
                    'unique:alumnos,user_id,' . $alumno->id,
                ],
                'nombre' => ['sometimes', 'required', 'string', 'max:255'],
                'apellido' => ['sometimes', 'required', 'string', 'max:255'],
                'curp' => ['sometimes', 'required', 'string', 'size:18', 'unique:alumnos,curp,' . $alumno->id],
                'telefono' => ['sometimes', 'required', 'string', 'max:20'],
                'correo' => ['sometimes', 'required', 'email', 'max:255', 'unique:alumnos,correo,' . $alumno->id],
                'fecha_ingreso' => ['sometimes', 'required', 'date'],
                'costo_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            ]);
        }

        $alumno->update($data);

        return response()->json($alumno->load('user'));
    }

    public function destroy(Request $request, $id)
    {
        $alumno = Alumno::findOrFail($id);
        $this->ensureAlumnoOwnership($this->currentUser($request), $alumno);
        $alumno->delete();

        return response()->json(['mensaje' => 'Eliminado']);
    }
}
