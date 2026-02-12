<?php
// app/Http/Data/Role/RolePrivilegeData.php
namespace App\Http\Data\Role;
use Illuminate\Support\Facades\DB;
use Throwable;

class RolePrivilegeData
{
    /**
     * Sincroniza los privilegios (IDs) de un módulo para un rol.
     * - Crea rol_modulo si no existe.
     * - Reemplaza rol_modulo_privilegio por lo enviado (sync).
     */
    public static function syncModuloPrivileges(int $idRol, int $idModulo, array $privIds): array
    {
        try {
            return DB::transaction(function () use ($idRol, $idModulo, $privIds) {

                // 1) Validar existencia de rol y módulo
                $rol = DB::table('rol')->where('id_rol', $idRol)->first();
                if (!$rol) return ['success'=>false,'message'=>'Rol no existe'];

                $mod = DB::table('modulo')->where('id_modulo', $idModulo)->first();
                if (!$mod) return ['success'=>false,'message'=>'Módulo no existe'];

                // 2) Validar que todos los privilegios pertenecen al módulo y (opcional) estén activos
                if (!empty($privIds)) {
                    $validPrivs = DB::table('privilegios')
                        ->where('id_modulo', $idModulo)
                        ->whereIn('id_privilegio', $privIds)
                        ->where('estado', 1) // opcional si usas estado
                        ->pluck('id_privilegio')
                        ->all();

                    if (count($validPrivs) !== count(array_unique($privIds))) {
                        return [
                            'success' => false,
                            'message' => 'Hay privilegios inválidos o que no pertenecen al módulo'
                        ];
                    }
                }

                // 3) Asegurar vínculo en rol_modulo
                $existsRM = DB::table('rol_modulo')
                    ->where('id_rol', $idRol)
                    ->where('id_modulo', $idModulo)
                    ->exists();

                if (!$existsRM) {
                    DB::table('rol_modulo')->insert([
                        'id_rol'    => $idRol,
                        'id_modulo' => $idModulo,
                    ]);
                }

                // 4) Sync: eliminar los que ya no vienen e insertar los nuevos
                // 4.1 Eliminar los que no están en la nueva lista
                if (empty($privIds)) {
                    DB::table('rol_modulo_privilegio')
                        ->where('id_rol', $idRol)
                        ->where('id_modulo', $idModulo)
                        ->delete();
                } else {
                    DB::table('rol_modulo_privilegio')
                        ->where('id_rol', $idRol)
                        ->where('id_modulo', $idModulo)
                        ->whereNotIn('id_privilegio', $privIds)
                        ->delete();

                    // 4.2 Insertar los faltantes (insert ignore-like)
                    $already = DB::table('rol_modulo_privilegio')
                        ->where('id_rol', $idRol)
                        ->where('id_modulo', $idModulo)
                        ->pluck('id_privilegio')
                        ->all();

                    $toInsert = array_diff($privIds, $already);
                    if (!empty($toInsert)) {
                        $rows = array_map(fn($pid) => [
                            'id_rol'        => $idRol,
                            'id_modulo'     => $idModulo,
                            'id_privilegio' => $pid,
                        ], $toInsert);

                        DB::table('rol_modulo_privilegio')->insert($rows);
                    }
                }

                // 5) Respuesta final
                $final = DB::table('rol_modulo_privilegio')
                    ->where('id_rol', $idRol)
                    ->where('id_modulo', $idModulo)
                    ->orderBy('id_privilegio')
                    ->pluck('id_privilegio')
                    ->all();

                return [
                    'success' => true,
                    'data'    => [
                        'id_rol'     => $idRol,
                        'id_modulo'  => $idModulo,
                        'privilegios'=> $final,
                    ],
                    'message' => 'Privilegios actualizados',
                ];
            });

        } catch (Throwable $e) {
            return ['success'=>false, 'message'=>'Error al asignar privilegios: '.$e->getMessage()];
        }
    }
}
