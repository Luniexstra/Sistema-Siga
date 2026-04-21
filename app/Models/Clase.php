<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clase extends Model
{
    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function evaluacion()
    {
        return $this->hasOne(Evaluacion::class);
    }

    public function observaciones()
    {
        return $this->hasMany(Observacion::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
    
    protected $fillable = [
    'fecha',
    'hora',
    'alumno_id',
    'instructor_id'
];
}
