<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observacion extends Model
{
    protected $table = 'observaciones';

    protected $fillable = [
        'clase_id',
        'comentario',
    ];

    public function clase()
    {
        return $this->belongsTo(Clase::class);
    }
}
