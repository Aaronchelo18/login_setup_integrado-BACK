<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UserAccessController extends Controller
{
public function searchUsuario(Request $request)
{
   // Acepta ?q= o ?search=
    $q = (string) $request->query('q', $request->query('search', ''));
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

    try {
        $rows = DB::table('efeso.usuario as u')
            ->leftJoin('global_config.persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->where(function ($w) use ($needle) {
                // 🔥 SOLO nombre y apellidos
                $w->whereRaw('LOWER(COALESCE(p.nombre,  \'\'))  LIKE ?', ["%{$needle}%"])
                  ->orWhereRaw('LOWER(COALESCE(p.paterno, \'\')) LIKE ?', ["%{$needle}%"])
                  ->orWhereRaw('LOWER(COALESCE(p.materno, \'\')) LIKE ?', ["%{$needle}%"]);
            })
            ->selectRaw('
                -- usamos id_persona como identificador para el front
                u.id_persona as id,
                u.id_persona,
                COALESCE(u.correo, \'\')  as correo,
                COALESCE(p.nombre, \'\')  as nombre,
                COALESCE(p.paterno, \'\') as paterno,
                COALESCE(p.materno, \'\') as materno,
                TRIM(
                    CONCAT(
                        COALESCE(p.nombre,  \'\'), \' \',
                        COALESCE(p.paterno, \'\'), \' \',
                        COALESCE(p.materno, \'\')
                    )
                ) as nombre_completo
            ')
            ->orderBy('p.paterno')
            ->orderBy('p.nombre')
            ->limit($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'query'   => $q,
            'data'    => $rows,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al consultar usuarios',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

  public function index($idPersona)
    {
        $rows = DB::table('global_config.usuario_programa_estudio as upe')
            ->join('global_config.campus as c', 'c.id_campus', '=', 'upe.id_campus')
            ->join('global_config.facultad as f', 'f.id_facultad', '=', 'upe.id_facultad')
            ->join('global_config.programa_estudio as pe', 'pe.id_programa_estudio', '=', 'upe.id_programa_estudio')
            ->where('upe.id_usuario', $idPersona) // aquí guardamos id_persona
            ->selectRaw('
                upe.id_usuario_programa_estudio as id,
                upe.id_usuario,
                upe.id_campus,
                c.campus,
                upe.id_facultad,
                f.nombre as facultad,
                upe.id_programa_estudio,
                pe.nombre as programa_estudio
            ')
            ->orderBy('upe.id_usuario_programa_estudio')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $rows,
        ]);
    }

      public function store(Request $request, $idPersona)
{
    try {
        // 1. Validar payload
        $data = $request->validate([
            'id_campus'           => 'required|integer',
            'id_facultad'         => 'required|integer',
            'id_programa_estudio' => 'required|integer',
        ]);

        // 2. 🔥 VALIDAR DUPLICIDAD
        $existe = DB::table('global_config.usuario_programa_estudio')
            ->where('id_usuario', $idPersona)
            ->where('id_campus', $data['id_campus'])
            ->where('id_facultad', $data['id_facultad'])
            ->where('id_programa_estudio', $data['id_programa_estudio'])
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuario ya tiene asignado este acceso',
            ], 422);
        }

        // 3. Calcular el próximo ID manualmente (MAX + 1)
        $maxId = DB::table('global_config.usuario_programa_estudio')
            ->max('id_usuario_programa_estudio');

        $nextId = ($maxId ?? 0) + 1;

        // 4. Armar datos a insertar
        $insertData = [
            'id_usuario_programa_estudio' => $nextId,
            'id_campus'                   => $data['id_campus'],
            'id_facultad'                 => $data['id_facultad'],
            'id_programa_estudio'         => $data['id_programa_estudio'],
            'id_usuario'                  => (int) $idPersona,
        ];

        // 5. Insertar
        DB::table('global_config.usuario_programa_estudio')
            ->insert($insertData);

        // 6. Leer el registro con joins para mandarlo bonito al front
        $row = DB::table('global_config.usuario_programa_estudio as upe')
            ->join('global_config.campus as c', 'c.id_campus', '=', 'upe.id_campus')
            ->join('global_config.facultad as f', 'f.id_facultad', '=', 'upe.id_facultad')
            ->join('global_config.programa_estudio as pe', 'pe.id_programa_estudio', '=', 'upe.id_programa_estudio')
            ->where('upe.id_usuario_programa_estudio', $nextId)
            ->selectRaw('
                upe.id_usuario_programa_estudio as id,
                upe.id_usuario,
                upe.id_campus,
                c.campus,
                upe.id_facultad,
                f.nombre as facultad,
                upe.id_programa_estudio,
                pe.nombre as programa_estudio
            ')
            ->first();

        return response()->json([
            'success' => true,
            'data'    => $row,
        ], 201);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al registrar acceso',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    public function destroy($idPersona, $accessId)
    {
        $deleted = DB::table('global_config.usuario_programa_estudio')
            ->where('id_usuario_programa_estudio', $accessId)
            ->where('id_usuario', $idPersona)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso no encontrado para este usuario.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Acceso eliminado correctamente.',
        ]);
    }
}