<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';

    protected $fillable = [
        'clase_id',
        'senales',
        'frenado',
        'seguridad',
        'promedio',
        'nivel'
    ];

    public function clase()
    {
        return $this->belongsTo(Clase::class);
    }
}