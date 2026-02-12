<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Http\Data\Role\RoleData;
use App\Models\Role\Role;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class RoleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $response = RoleData::getAll();

        return $response['success']
            ? $this->ok($response['data'], 'Roles obtenidos exitosamente')
            : $this->error($response['message'], 500);
    }

     public function store(Request $request)
    {
        $response = RoleData::create($request->all());

        if (!$response['success']) {
            return isset($response['errors'])
                ? $this->information($response['message'], 422, $response['errors'])
                : $this->error($response['message'], 500);
        }

        return $this->ok($response['data'], 'Rol creado exitosamente', 201);
    }

    public function updateStatus(Request $request, $id_rol)
{
    $data = $request->validate([
        'estado' => ['required', 'in:0,1'],
    ]);

    $role = Role::where('id_rol', $id_rol)->firstOrFail();
    $role->estado = $data['estado'];

    if (!$role->save()) {
        return $this->error('No se pudo actualizar el estado del rol', 500);
    }

    return $this->ok([
        'id_rol' => (int)$role->id_rol,
        'nombre'  => (string)$role->nombre,
        'estado' => (string)$role->estado,
    ], 'Estado del rol actualizado');
}

public function updateName(Request $request, $id_rol)
{
    $data = $request->validate([
        'nombre' => ['required', 'string', 'max:255'],
    ]);

    $role = Role::where('id_rol', $id_rol)->firstOrFail();
    $role->nombre = $data['nombre'];

    if (!$role->save()) {
        return $this->error('No se pudo actualizar el nombre del rol', 500);
    }

    return $this->ok([
        'id_rol' => (int)$role->id_rol,
        'nombre' => (string)$role->nombre,
        'estado'  => (string)$role->estado,
    ], 'Nombre del rol actualizado');
}

    public function destroy($id_rol)
{
    $role = Role::where('id_rol', $id_rol)->first();

    if (!$role) {
        return $this->error('Rol no encontrado', 404);
    }

    // Si usas soft deletes: $role->delete(); sigue funcionando.
    if (!$role->delete()) {
        return $this->error('No se pudo eliminar el rol', 500);
    }

    return $this->ok(null, 'Rol eliminado correctamente');
}

    
}
