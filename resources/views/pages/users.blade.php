@extends('layouts.siga')

@php($title = 'SIGA | Usuarios')

@section('content')
    <div data-siga-page="resource" data-siga-resource="users">
        <section class="siga-page-head">
            <div>
                <p class="siga-eyebrow">Modulo</p>
                <h2>Usuarios y roles</h2>
                <p class="siga-hero__copy">Administra cuentas del sistema y manten los roles separados de los demas procesos operativos.</p>
            </div>
        </section>

        <section class="siga-grid">
            <article class="siga-panel">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Nuevo usuario</p>
                        <h3>Alta de acceso</h3>
                    </div>
                </div>
                <form class="siga-form-card" data-endpoint="/api/users" data-method="POST">
                    <div class="siga-form-feedback" data-form-feedback hidden></div>
                    <label class="siga-field"><span>Nombre</span><input name="name" required /></label>
                    <label class="siga-field"><span>Correo</span><input name="email" type="email" required /></label>
                    <label class="siga-field">
                        <span>Rol</span>
                        <select name="role" required>
                            <option value="administrador">Administrador</option>
                            <option value="recepcionista">Recepcionista</option>
                            <option value="instructor">Instructor</option>
                            <option value="alumno">Alumno</option>
                        </select>
                    </label>
                    <label class="siga-field"><span>Contrasena</span><input name="password" type="password" required /></label>
                    <button class="siga-button siga-button--primary" type="submit">Guardar usuario</button>
                </form>
            </article>

            <article class="siga-panel siga-panel--wide">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Listado</p>
                        <h3>Usuarios del sistema</h3>
                    </div>
                </div>
                <div class="siga-table-wrap">
                    <table class="siga-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="resource-table">
                            <tr><td colspan="5">Sin datos cargados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
@endsection
