@extends('layouts.siga')

@php($title = 'SIGA | Alumnos')

@section('content')
    <div data-siga-page="resource" data-siga-resource="alumnos">
        <section class="siga-page-head">
            <div>
                <p class="siga-eyebrow">Modulo</p>
                <h2>Registro de alumnos</h2>
                <p class="siga-hero__copy">Gestiona expedientes, costo total y datos de ingreso sin mezclarlo con otros flujos.</p>
            </div>
        </section>

        <section class="siga-grid">
            <article class="siga-panel">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Nuevo registro</p>
                        <h3>Alta de alumno</h3>
                    </div>
                </div>
                <form class="siga-form-card" data-endpoint="/api/alumnos" data-method="POST">
                    <div class="siga-form-feedback" data-form-feedback hidden></div>
                    <label class="siga-field"><span>Nombre</span><input name="nombre" required /></label>
                    <label class="siga-field"><span>Apellido</span><input name="apellido" required /></label>
                    <label class="siga-field"><span>CURP</span><input name="curp" required /></label>
                    <label class="siga-field"><span>Telefono</span><input name="telefono" required /></label>
                    <label class="siga-field"><span>Correo</span><input name="correo" type="email" required /></label>
                    <label class="siga-field"><span>Fecha de ingreso</span><input name="fecha_ingreso" type="date" required /></label>
                    <label class="siga-field"><span>Costo total</span><input name="costo_total" type="number" min="0" step="0.01" value="0" /></label>
                    <button class="siga-button siga-button--primary" type="submit">Guardar alumno</button>
                </form>
            </article>

            <article class="siga-panel siga-panel--wide">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Listado</p>
                        <h3>Alumnos registrados</h3>
                    </div>
                </div>
                <div class="siga-table-wrap">
                    <table class="siga-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>CURP</th>
                                <th>Correo</th>
                                <th>Costo total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="resource-table">
                            <tr><td colspan="6">Sin datos cargados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
@endsection
