<?php

namespace App\Http\Data\Faculty;

use Illuminate\Support\Facades\DB;
use Throwable;

class FacultadData
{
    /**
     * Obtiene facultades generales o filtradas por usuario.
     */
    public static function getAll($idUsuario = null): array
    {
        try {
            $query = DB::table('global_config.facultad as a')
                ->select('a.id_facultad', 'a.nombre');

            // Si el idUsuario no es nulo, aplicamos la lógica de relación por permisos
            if ($idUsuario) {
                $query->join('global_config.usuario_programa_estudio as b', 'a.id_facultad', '=', 'b.id_facultad')
                      ->where('b.id_usuario', (int)$idUsuario)
                      ->select('a.id_facultad', 'b.id_usuario', 'a.nombre') // Incluimos id_usuario como en tu query
                      ->distinct();
            }

            $rows = $query->orderBy('a.nombre', 'asc')->get()->toArray();

            return ['success' => true, 'data' => $rows];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error en FacultadData: ' . $e->getMessage()];
        }
    }
}