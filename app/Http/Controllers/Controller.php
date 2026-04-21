<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Clase;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class Controller
{
    protected function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('alumno');

        return $user;
    }

    protected function userCanManageAcademicOverview(User $user): bool
    {
        return $user->isAdministrador() || $user->isRecepcionista() || $user->isInstructor();
    }

    protected function userCanManageAdministrativeData(User $user): bool
    {
        return $user->isAdministrador() || $user->isRecepcionista();
    }

    protected function ensureAlumnoOwnership(User $user, Alumno $alumno): void
    {
        if ($user->isAdministrador() || $user->isRecepcionista()) {
            return;
        }

        if ($user->isAlumno() && (int) $user->alumno?->id === (int) $alumno->id) {
            return;
        }

        throw new HttpException(403, 'No tienes permisos para acceder a este alumno.');
    }

    protected function ensureClaseAccess(User $user, Clase $clase): void
    {
        if ($user->isAdministrador() || $user->isRecepcionista()) {
            return;
        }

        if ($user->isInstructor() && (int) $clase->instructor_id === (int) $user->id) {
            return;
        }

        if ($user->isAlumno() && (int) $clase->alumno_id === (int) $user->alumno?->id) {
            return;
        }

        throw new HttpException(403, 'No tienes permisos para acceder a esta clase.');
    }

    protected function ensureInstructorOwnsClase(User $user, Clase $clase): void
    {
        if (! $user->isInstructor() || (int) $clase->instructor_id !== (int) $user->id) {
            throw new HttpException(403, 'Solo el instructor asignado puede modificar este recurso.');
        }
    }
}
