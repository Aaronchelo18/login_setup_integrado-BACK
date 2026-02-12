<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRoleController extends Controller
{

    public function index(Request $request)
    {
        $perPage = 10;

        $rows = DB::table('efeso.usuario as u')
            ->leftJoin('global_config.persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->selectRaw('
                u.id_persona,
                COALESCE(u.correo, \'\')  as correo,
                COALESCE(p.nombre, \'\')  as nombre,
                COALESCE(p.paterno, \'\') as paterno,
                COALESCE(p.materno, \'\') as materno
            ')
            ->orderBy('p.paterno')
            ->orderBy('p.nombre')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $rows->items(),
            'meta'    => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
        ]);
    }

    public function search(Request $request)
    {
        $q = (string) $request->query('q', '');
        $q = trim($q);

        $minLen = 4;
        if (mb_strlen($q) < $minLen) {
            return response()->json([
                'success' => false,
                'message' => "Ingresa al menos {$minLen} caracteres para buscar.",
            ], 422);
        }

        $perPage = 10;
        $needle  = mb_strtolower($q, 'UTF-8');

        $rows = DB::table('efeso.usuario as u')
            ->leftJoin('global_config.persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->where(function ($w) use ($needle) {
                $w->whereRaw('LOWER(COALESCE(p.nombre,  \'\' ))  LIKE ?', ["%{$needle}%"])
                  ->orWhereRaw('LOWER(COALESCE(p.paterno, \'\' )) LIKE ?', ["%{$needle}%"])
                  ->orWhereRaw('LOWER(COALESCE(p.materno, \'\' )) LIKE ?', ["%{$needle}%"])
                  ->orWhereRaw('LOWER(COALESCE(u.correo,  \'\' )) LIKE ?', ["%{$needle}%"])
                  ->orWhereRaw('CAST(u.id_persona AS TEXT) LIKE ?', ["%{$needle}%"]);
            })
            ->selectRaw('
                u.id_persona,
                COALESCE(u.correo, \'\')  as correo,
                COALESCE(p.nombre, \'\')  as nombre,
                COALESCE(p.paterno, \'\') as paterno,
                COALESCE(p.materno, \'\') as materno
            ')
            ->orderBy('p.paterno')
            ->orderBy('p.nombre')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'query'   => $q,
            'data'    => $rows->items(),
            'meta'    => [
                'current_page' => $rows->currentPage(),
                'per_page'     => $rows->perPage(),
                'total'        => $rows->total(),
                'last_page'    => $rows->lastPage(),
            ],
        ]);
    }

    public function saveForUser(int $id_persona, Request $request)
{
    // Espera un body tipo: { "roles": [1, 2, 3] }
    $roles = $request->input('roles', []);

    if (!is_array($roles)) {
        return response()->json([
            'success' => false,
            'message' => 'El campo "roles" debe ser un arreglo de IDs de rol.',
        ], 422);
    }

    // Normalizar: solo enteros, únicos
    $roleIds = collect($roles)
        ->filter(fn ($v) => is_numeric($v))
        ->map(fn ($v) => (int) $v)
        ->unique()
        ->values();

    try {
        DB::beginTransaction();

        // 1) Borrar los roles actuales del usuario
        DB::table('efeso.usuario_rol')
            ->where('id_persona', $id_persona)
            ->delete();

        // 2) Insertar los nuevos (si hay)
        if ($roleIds->isNotEmpty()) {
            $rows = $roleIds->map(fn ($id_rol) => [
                'id_persona' => $id_persona,
                'id_rol'     => $id_rol,
            ])->all();

            DB::table('efeso.usuario_rol')->insert($rows);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Roles asignados correctamente.',
            'count'   => $roleIds->count(),
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Error al asignar roles: ' . $e->getMessage(),
        ], 500);
    }
}

public function assignedToUser(int $id_persona)
{
    // trae solo los id_rol del usuario
    $roles = DB::table('efeso.usuario_rol')
        ->where('id_persona', $id_persona)
        ->pluck('id_rol');

    // respuesta compatible con lo que espera el front
    return response()->json([
        'success' => true,
        'data'    => $roles->map(fn ($id) => ['id_rol' => $id])->values(),
    ]);
}


}
