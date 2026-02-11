<?php

namespace App\Http\Data\Campus;

use Illuminate\Support\Facades\DB;
use Throwable;

class CampusData
{
    /**
     * Obtiene los campus generales o filtrados por los permisos del usuario.
     */
    public static function getAll($idUsuario = null): array
    {
        try {
            $query = DB::table('global_config.campus as a')
                ->select('a.id_campus', 'a.campus', 'a.codigo', 'a.estado', 'a.orden')
                ->where('a.estado', '1'); // Filtro base solicitado

            // Si recibimos idUsuario, aplicamos el JOIN de seguridad
            if ($idUsuario) {
                $query->join('global_config.usuario_programa_estudio as b', 'a.id_campus', '=', 'b.id_campus')
                      ->where('b.id_usuario', (int)$idUsuario)
                      ->select('a.id_campus', 'b.id_usuario', 'a.campus', 'a.codigo', 'a.estado', 'a.orden')
                      ->distinct();
            }

            $rows = $query->orderBy('a.orden', 'asc')->get()->toArray();

            return ['success' => true, 'data' => $rows];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error en CampusData: ' . $e->getMessage()];
        }
    }
}