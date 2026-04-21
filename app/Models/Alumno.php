<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    public function clases()
    {
        return $this->hasMany(Clase::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $fillable = [
    'user_id',
    'nombre',
    'apellido',
    'curp',
    'telefono',
    'correo',
    'fecha_ingreso',
    'costo_total',
    ];
}
