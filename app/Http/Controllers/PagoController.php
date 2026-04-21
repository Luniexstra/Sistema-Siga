<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Pago;
use App\Support\SimplePdf;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->currentUser($request);
        $query = Pago::with('alumno')->latest('fecha_pago');

        if ($request->filled('alumno_id')) {
            $request->validate([
                'alumno_id' => ['integer', 'exists:alumnos,id'],
            ]);

            $query->where('alumno_id', $request->integer('alumno_id'));
        }

        if ($user->isAlumno()) {
            $query->where('alumno_id', $user->alumno?->id ?? 0);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'alumno_id' => ['required', 'integer', 'exists:alumnos,id'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'fecha_pago' => ['required', 'date'],
            'estado' => ['required', 'in:pagado,pendiente'],
        ]);

        $pago = Pago::create($data);

        return response()->json($pago->load('alumno'), 201);
    }

    public function show(Request $request, $id)
    {
        $pago = Pago::with('alumno')->findOrFail($id);
        $this->ensureAlumnoOwnership($this->currentUser($request), $pago->alumno);

        return $pago;
    }

    public function update(Request $request, $id)
    {
        $pago = Pago::findOrFail($id);

        $data = $request->validate([
            'alumno_id' => ['required', 'integer', 'exists:alumnos,id'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'fecha_pago' => ['required', 'date'],
            'estado' => ['required', 'in:pagado,pendiente'],
        ]);

        $pago->update($data);

        return response()->json($pago->load('alumno'));
    }

    public function destroy($id)
    {
        $pago = Pago::findOrFail($id);
        $pago->delete();

        return response()->json(['mensaje' => 'Pago eliminado']);
    }

    public function estadoCuenta(Request $request, Alumno $alumno)
    {
        $this->ensureAlumnoOwnership($this->currentUser($request), $alumno);

        return response()->json($this->buildEstadoCuenta($alumno));
    }

    public function estadoCuentaPdf(Request $request, Alumno $alumno)
    {
        $this->ensureAlumnoOwnership($this->currentUser($request), $alumno);
        $estadoCuenta = $this->buildEstadoCuenta($alumno);
        $pagos = $estadoCuenta['pagos'];
        $resumen = $estadoCuenta['resumen'];

        $lines = [
            'SIGA - Estado de Cuenta',
            'Alumno: ' . $alumno->nombre . ' ' . $alumno->apellido,
            'Correo: ' . $alumno->correo,
            'Fecha de ingreso: ' . $alumno->fecha_ingreso,
            'Costo total: $' . number_format($resumen['costo_total'], 2),
            'Total pagado: $' . number_format($resumen['total_pagado'], 2),
            'Saldo pendiente: $' . number_format($resumen['saldo_pendiente'], 2),
            'Estado de cuenta: ' . strtoupper($resumen['estado_cuenta']),
            ' ',
            'Historial de pagos:',
        ];

        if (count($pagos) === 0) {
            $lines[] = 'Sin pagos registrados.';
        } else {
            foreach ($pagos as $pago) {
                $lines[] = sprintf(
                    '%s | $%s | %s',
                    $pago['fecha_pago'],
                    number_format((float) $pago['monto'], 2),
                    strtoupper($pago['estado'])
                );
            }
        }

        $pdf = SimplePdf::fromLines($lines);
        $fileName = 'estado-cuenta-alumno-' . $alumno->id . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    protected function buildEstadoCuenta(Alumno $alumno): array
    {
        $pagos = $alumno->pagos()->orderByDesc('fecha_pago')->get();
        $totalPagado = (float) $alumno->pagos()
            ->where('estado', 'pagado')
            ->sum('monto');
        $costoTotal = (float) ($alumno->costo_total ?? 0);
        $saldoPendiente = max($costoTotal - $totalPagado, 0);

        return [
            'alumno' => $alumno,
            'resumen' => [
                'costo_total' => round($costoTotal, 2),
                'total_pagado' => round($totalPagado, 2),
                'saldo_pendiente' => round($saldoPendiente, 2),
                'estado_cuenta' => $saldoPendiente > 0 ? 'pendiente' : 'liquidado',
            ],
            'pagos' => $pagos->toArray(),
        ];
    }
}
