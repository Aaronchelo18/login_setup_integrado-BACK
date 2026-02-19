<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        $rows = DB::table('efeso.usuario as u')
            ->leftJoin('global_config.persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->selectRaw("
                u.id_persona,
                COALESCE(u.correo, '')  as correo,
                COALESCE(p.nombre, '')  as nombre,
                COALESCE(p.paterno, '') as paterno,
                COALESCE(p.materno, '') as materno
            ")
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
        $q = trim((string) $request->query('q', ''));
        $minLen = 1;

        if (mb_strlen($q) < $minLen) {
            return $this->index($request); // Si el término es muy corto, devolvemos el index
        }

        $perPage = $request->query('per_page', 10);
        $needle = mb_strtolower($q, 'UTF-8');

        $rows = DB::table('efeso.usuario as u')
            ->leftJoin('global_config.persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->where(function ($w) use ($needle, $q) {
                $w->whereRaw("LOWER(COALESCE(p.nombre, '')) LIKE ?", ["%{$needle}%"])
                  ->orWhereRaw("LOWER(COALESCE(p.paterno, '')) LIKE ?", ["%{$needle}%"])
                  ->orWhereRaw("LOWER(COALESCE(p.materno, '')) LIKE ?", ["%{$needle}%"])
                  ->orWhereRaw("LOWER(COALESCE(u.correo, '')) LIKE ?", ["%{$needle}%"]);
                
                if (is_numeric($q)) {
                    // Prioridad a búsqueda exacta por ID y luego parcial
                    $w->orWhereRaw("u.id_persona::text = ?", [$q])
                      ->orWhereRaw("u.id_persona::text LIKE ?", ["%{$q}%"]);
                }
            })
            ->selectRaw("
                u.id_persona,
                COALESCE(u.correo, '')  as correo,
                COALESCE(p.nombre, '')  as nombre,
                COALESCE(p.paterno, '') as paterno,
                COALESCE(p.materno, '') as materno
            ")
            ->orderBy('p.paterno')
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

    public function saveForUser(int $id_persona, Request $request)
    {
        $roles = $request->input('roles', []);

        if (!is_array($roles)) {
            return response()->json(['success' => false, 'message' => 'Roles debe ser un array'], 422);
        }

        $roleIds = collect($roles)->map(fn($v) => (int)$v)->unique()->values();

        try {
            DB::beginTransaction();
            DB::table('efeso.usuario_rol')->where('id_persona', $id_persona)->delete();

            if ($roleIds->isNotEmpty()) {
                $insert = $roleIds->map(fn($id) => [
                    'id_persona' => $id_persona,
                    'id_rol' => $id
                ])->all();
                DB::table('efeso.usuario_rol')->insert($insert);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Roles actualizados']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function assignedToUser(int $id_persona)
    {
        $roles = DB::table('efeso.usuario_rol')
            ->where('id_persona', $id_persona)
            ->pluck('id_rol');

        return response()->json([
            'success' => true,
            'data'    => $roles->map(fn($id) => ['id_rol' => $id])->values(),
        ]);
    }
}