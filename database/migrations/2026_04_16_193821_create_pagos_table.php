<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('alumno_id')->constrained('alumnos');
    $table->decimal('monto', 8, 2);
    $table->date('fecha_pago');
    $table->enum('estado', ['pagado', 'pendiente']);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
