<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    public const ROLE_ADMINISTRADOR = 'administrador';
    public const ROLE_RECEPCIONISTA = 'recepcionista';
    public const ROLE_INSTRUCTOR = 'instructor';
    public const ROLE_ALUMNO = 'alumno';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function alumno()
    {
        return $this->hasOne(Alumno::class);
    }

    public function clasesComoInstructor()
    {
        return $this->hasMany(Clase::class, 'instructor_id');
    }

    public static function roles(): array
    {
        return [
            self::ROLE_ADMINISTRADOR,
            self::ROLE_RECEPCIONISTA,
            self::ROLE_INSTRUCTOR,
            self::ROLE_ALUMNO,
        ];
    }

    public function isAdministrador(): bool
    {
        return $this->role === self::ROLE_ADMINISTRADOR;
    }

    public function isRecepcionista(): bool
    {
        return $this->role === self::ROLE_RECEPCIONISTA;
    }

    public function isInstructor(): bool
    {
        return $this->role === self::ROLE_INSTRUCTOR;
    }

    public function isAlumno(): bool
    {
        return $this->role === self::ROLE_ALUMNO;
    }
}
