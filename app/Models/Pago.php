<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'alumno_id',
        'monto',
        'fecha_pago',
        'estado',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }
}
