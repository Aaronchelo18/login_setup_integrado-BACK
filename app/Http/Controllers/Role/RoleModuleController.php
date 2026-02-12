<?php

namespace App\Http\Controllers\Role;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Access\SyncRoleModulesRequest;

class RoleModuleController extends Controller
{
  // PUT /roles/{id_rol}/modulos
  public function sync(SyncRoleModulesRequest $req, int $id_rol)
{
    $ids = $req->validated('modulos'); // puede ser []

    DB::transaction(function() use ($id_rol, $ids) {
        // 1) Borra TODOS los privilegios del rol (en cualquier módulo)
        DB::table('rol_modulo_privilegio')
          ->where('id_rol', $id_rol)
          ->delete();

        // 2) Borra TODAS las relaciones rol_modulo del rol
        DB::table('rol_modulo')
          ->where('id_rol', $id_rol)
          ->delete();

        // 3) Inserta las nuevas (si enviaron algo)
        if (!empty($ids)) {
            $rows = array_map(fn($m) => ['id_rol'=>$id_rol, 'id_modulo'=>$m], $ids);
            DB::table('rol_modulo')->insert($rows);
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'Módulos sincronizados correctamente',
        'count'   => count($ids)
    ]);
}



}
