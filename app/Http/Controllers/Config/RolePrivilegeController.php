<?php

namespace App\Http\Controllers\Config;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolePrivilegeController extends Controller
{

  use ApiResponse;
  // GET /modulos/{id_modulo}/privilegios
  public function catalogByModule(int $id_modulo): JsonResponse
    {
        try {
            $rows = DB::table('privilegios as p')
                ->where('p.id_modulo', $id_modulo)
                ->where('p.estado', 1)                   // si manejas estado
                ->orderBy('p.nombre')
                ->get([
                    'p.id_privilegio',
                    'p.nombre',
                    'p.clave',
                    'p.valor',
                    'p.comentario'
                ]);

            return $this->ok($rows, 'Catálogo');
        } catch (\Throwable $e) {
            return $this->error('Error al obtener catálogo: ' . $e->getMessage(), 500);
        }
    }

  // GET /roles/{id_rol}/modulos/{id_modulo}/privilegios
  public function assigned(int $id_rol, int $id_modulo): JsonResponse
    {
        try {
            $ids = DB::table('rol_modulo_privilegio')
                ->where('id_rol', $id_rol)
                ->where('id_modulo', $id_modulo)
                ->pluck('id_privilegio');

            return $this->ok($ids, 'Asignados');
        } catch (\Throwable $e) {
            return $this->error('Error al obtener asignados: ' . $e->getMessage(), 500);
        }
    }

  // PUT /roles/{id_rol}/modulos/{id_modulo}/privilegios
 public function store(int $id_rol, int $id_modulo, Request $req): JsonResponse
    {
        $privs = $req->input('privilegios', []);
        if (!is_array($privs)) $privs = [];

        try {
            DB::beginTransaction();

            // Validar que los privilegios pertenezcan al módulo
            $valid = DB::table('privilegios')
                ->where('id_modulo', $id_modulo)
                ->pluck('id_privilegio')
                ->toArray();

            $insert = [];
            foreach ($privs as $pid) {
                if (in_array((int)$pid, $valid, true)) {
                    $insert[] = [
                        'id_rol'        => $id_rol,
                        'id_modulo'     => $id_modulo,
                        'id_privilegio' => (int)$pid,
                    ];
                }
            }

            // Reemplazo
            DB::table('rol_modulo_privilegio')
                ->where('id_rol', $id_rol)
                ->where('id_modulo', $id_modulo)
                ->delete();

            if ($insert) {
                DB::table('rol_modulo_privilegio')->insert($insert);
            }

            DB::commit();
            return $this->ok(['count' => count($insert)], 'Guardado');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error('Error al guardar privilegios: ' . $e->getMessage(), 500);
        }
    }
}
