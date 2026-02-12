<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicCatalogController extends Controller
{

    public function campus()
    {
        $rows = DB::table('global_config.campus')
            ->select('id_campus', 'campus')
            ->orderBy('orden')
            ->orderBy('campus')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $rows,
        ]);
    }

    public function facultades(Request $request)
{
    $idCampus = $request->query('id_campus');

    $query = DB::table('global_config.programa_estudio_facultad as pef')
        ->join(
            'global_config.facultad as f',
            'f.id_facultad',
            '=',
            'pef.id_facultad'
        )
        ->select('f.id_facultad', 'f.nombre')
        ->orderBy('f.nombre')
        ->distinct();

    if (!empty($idCampus)) {
        $query->where('pef.id_campus', $idCampus);
    }

    $rows = $query->get();

    return response()->json([
        'success' => true,
        'data'    => $rows,
    ]);
}


 public function programasEstudio(Request $request)
{
    $idFacultad = $request->query('id_facultad');
    $idCampus   = $request->query('id_campus'); 

    try {
        $query = DB::table('global_config.programa_estudio_facultad as pef')
            ->join(
                'global_config.programa_estudio as pe',
                'pe.id_programa_estudio',
                '=',
                'pef.id_programa_estudio'
            )
            ->select('pe.id_programa_estudio', 'pe.nombre')
            ->orderBy('pe.nombre');

        if (!empty($idFacultad)) {
            $query->where('pef.id_facultad', $idFacultad);
        }

        if (!empty($idCampus)) {
            $query->where('pef.id_campus', $idCampus);
        }

        $rows = $query->distinct()->get();

        return response()->json([
            'success' => true,
            'data'    => $rows,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al listar programas de estudio',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function getAllAccessReports(Request $request)
{
    try {
        $perPage = (int) $request->query('per_page', 10);

        $search     = trim((string) $request->query('search', ''));
        $idCampus   = $request->query('id_campus');
        $idFacultad = $request->query('id_facultad');
        $idPrograma = $request->query('id_programa_estudio');

        $query = DB::table('global_config.usuario_programa_estudio as upe')
            // FILTROS PRIMERO (sobre upe) para que el planner reduzca rápido
            ->when($idCampus,   fn ($q) => $q->where('upe.id_campus', $idCampus))
            ->when($idFacultad, fn ($q) => $q->where('upe.id_facultad', $idFacultad))
            ->when($idPrograma, fn ($q) => $q->where('upe.id_programa_estudio', $idPrograma))

            // JOINS
            ->join('efeso.usuario as u', 'u.id_persona', '=', 'upe.id_usuario')
            ->leftJoin('global_config.persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->join('global_config.campus as c', 'c.id_campus', '=', 'upe.id_campus')
            ->join('global_config.facultad as f', 'f.id_facultad', '=', 'upe.id_facultad')
            ->join('global_config.programa_estudio as pe', 'pe.id_programa_estudio', '=', 'upe.id_programa_estudio')

            // SEARCH (lo dejamos, pero la optimización real viene con índices trigram / FTS)
            ->when($search !== '', function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where(function ($qq) use ($like) {
                    $qq->where('p.nombre', 'ILIKE', $like)
                       ->orWhere('p.paterno', 'ILIKE', $like)
                       ->orWhere('p.materno', 'ILIKE', $like)
                       ->orWhere('u.correo', 'ILIKE', $like)
                       ->orWhere('pe.nombre', 'ILIKE', $like);
                });
            })

            // SELECT (sin TO_CHAR por fila)
            ->select(
                'upe.id_usuario_programa_estudio as id',
                'upe.id_usuario as id_persona',
                DB::raw("TRIM(CONCAT_WS(' ', p.nombre, p.paterno, p.materno)) as nombre_completo"),
                DB::raw("COALESCE(u.correo, '') as correo"),
                'c.campus',
                'f.nombre as facultad',
                'pe.nombre as programa_estudio'
            )
            ->orderBy('p.paterno', 'asc')
            ->orderBy('p.nombre', 'asc');

        $reports = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => array_map(function ($row) {
                $row->fecha_asignacion = now()->toDateString(); // constante, no por fila en SQL
                return $row;
            }, $reports->items()),
            'pagination' => [
                'total' => $reports->total(),
                'per_page' => $reports->perPage(),
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener el reporte de accesos',
            'error' => $e->getMessage()
        ], 500);
    }
}


}