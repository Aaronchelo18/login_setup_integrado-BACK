<?php

namespace App\Http\Data\Access;

use Illuminate\Support\Facades\DB;
use Throwable;

class PrivilegeData
{
   
    public static function getPrivilegeCatalogByModule(int $idModulo): array
    {
        try {
            $q = DB::table('privilegios as p')
                ->where('p.id_modulo', $idModulo);

            $q->where('p.estado', 1);

            $rows = $q->orderBy('p.nombre')
                ->get([
                    'p.id_privilegio',
                    'p.nombre',
                    'p.clave',
                    'p.valor',
                    'p.comentario',
                    'p.estado',
                ]);

            return ['success' => true, 'data' => $rows];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

  
    public static function getAssignedPrivileges(int $idRol, int $idModulo): array
    {
        try {
            $ids = DB::table('rol_modulo_privilegio')
                ->where('id_rol', $idRol)
                ->where('id_modulo', $idModulo)
                ->pluck('id_privilegio');

            return ['success' => true, 'data' => $ids];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

 
    public static function saveRoleModulePrivileges(int $idRol, int $idModulo, array $privs): array
    {
        try {
            DB::transaction(function () use ($idRol, $idModulo, $privs) {

        
                $valid = DB::table('privilegios')
                    ->where('id_modulo', $idModulo)
                    ->pluck('id_privilegio')
                    ->toArray();

                foreach ($privs as $pid) {
                    if (!in_array($pid, $valid, true)) {
                        throw new \RuntimeException("Privilegio $pid no pertenece al módulo $idModulo");
                    }
                }

         
                DB::table('rol_modulo_privilegio')
                    ->where('id_rol', $idRol)
                    ->where('id_modulo', $idModulo)
                    ->delete();

                if (!empty($privs)) {
                    $rows = array_map(fn($pid) => [
                        'id_rol'        => $idRol,
                        'id_modulo'     => $idModulo,
                        'id_privilegio' => $pid,
                    ], $privs);

                    DB::table('rol_modulo_privilegio')->insert($rows);
                }
            });

            return ['success' => true, 'data' => []];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al guardar privilegios: '.$e->getMessage(), 'data' => []];
        }
    }
}
