<?php
// app/Http/Data/Role/RoleModuleData.php
namespace App\Http\Data\Role;
use Illuminate\Support\Facades\DB;
use Throwable;

class RoleModuleData
{
    public static function syncModules(int $idRol, array $moduleIds): array
    {
        try {
            return DB::transaction(function () use ($idRol, $moduleIds) {

                // 1) Validar rol
                $rol = DB::table('rol')->where('id_rol', $idRol)->first();
                if (!$rol) {
                    return ['success' => false, 'message' => 'Rol no existe', 'data' => []];
                }

                // 2) Validar módulos (opcional: estado = 1)
                $moduleIds = array_values(array_unique($moduleIds));
                if (!empty($moduleIds)) {
                    $validMods = DB::table('modulo')
                        ->whereIn('id_modulo', $moduleIds)
                        ->where('estado', 1) 
                        ->pluck('id_modulo')->all();

                    if (count($validMods) !== count($moduleIds)) {
                        return ['success' => false, 'message' => 'Hay módulos inválidos o inactivos', 'data' => []];
                    }
                }

                // 3) Obtener actuales
                $current = DB::table('rol_modulo')
                    ->where('id_rol', $idRol)
                    ->pluck('id_modulo')->all();

                $toDelete = array_diff($current, $moduleIds);
                $toInsert = array_diff($moduleIds, $current);

                // 4) Primero, limpiar privilegios de los módulos que se quitan
                if (!empty($toDelete)) {
                    DB::table('rol_modulo_privilegio')
                        ->where('id_rol', $idRol)
                        ->whereIn('id_modulo', $toDelete)
                        ->delete();

                    // Luego borrar esas filas de rol_modulo
                    DB::table('rol_modulo')
                        ->where('id_rol', $idRol)
                        ->whereIn('id_modulo', $toDelete)
                        ->delete();
                }

                // 5) Insertar nuevas filas en rol_modulo
                if (!empty($toInsert)) {
                    $rows = array_map(fn($mid) => [
                        'id_rol'    => $idRol,
                        'id_modulo' => $mid,
                    ], $toInsert);

                    DB::table('rol_modulo')->insert($rows);
                }

                // 6) Respuesta final (ordenada)
                $final = DB::table('rol_modulo')
                    ->where('id_rol', $idRol)
                    ->orderBy('id_modulo')
                    ->pluck('id_modulo')->all();

                return [
                    'success' => true,
                    'message' => 'Accesos (módulos) actualizados',
                    'data'    => ['id_rol' => $idRol, 'modulos' => $final],
                ];
            });
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar accesos: '.$e->getMessage(),
                'data'    => [],
            ];
        }
    }
}
