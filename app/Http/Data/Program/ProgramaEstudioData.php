<?php

namespace App\Http\Data\Program;

use Illuminate\Support\Facades\DB;
use Throwable;

class ProgramaEstudioData
{
    /**
     * Obtiene programas de estudio generales o filtrados por permisos de usuario.
     */
    public static function getAll($idUsuario = null): array
    {
        try {
            $query = DB::table('global_config.programa_estudio as a')
                ->select('a.id_programa_estudio', 'a.codigo', 'a.nombre', 'a.abreviatura');

            // Si se proporciona idUsuario, filtramos por la tabla relacional de permisos
            if ($idUsuario) {
                $query->join('global_config.usuario_programa_estudio as b', 'a.id_programa_estudio', '=', 'b.id_programa_estudio')
                      ->where('b.id_usuario', (int)$idUsuario)
                      ->select('a.id_programa_estudio', 'b.id_usuario', 'a.codigo', 'a.nombre', 'a.abreviatura')
                      ->distinct();
            }

            $rows = $query->orderBy('a.nombre', 'asc')->get()->toArray();

            return ['success' => true, 'data' => $rows];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error en ProgramaEstudioData: ' . $e->getMessage()];
        }
    }
}