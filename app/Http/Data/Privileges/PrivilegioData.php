<?php
namespace App\Http\Data\Privileges;

use Illuminate\Support\Facades\DB;

class PrivilegioData
{
    private static array $ACCIONES = ['Crear','Listar','Editar','Eliminar'];

    private static function b2s($v): string
    {
        return ($v ?? false) ? '1' : '0';
    }

    /* ============================================================
     *  NUEVO: crear catálogo de privilegios por módulo
     * ============================================================ */
    public static function createCatalog(
        int $id_modulo,
        string $nombre,
        array $acciones,
        string $estado = '1'
    ): array {
        try {
            DB::beginTransaction();

            $acciones = array_values(array_intersect(self::$ACCIONES, $acciones));
            if (empty($acciones)) {
                return ['success'=>false, 'message'=>'Acciones inválidas', 'data'=>[]];
            }

            $existentes = DB::table('efeso.privilegios')
                ->select('id_privilegio','clave')
                ->where('id_modulo', $id_modulo)
                ->where('nombre', $nombre)
                ->whereIn('clave', $acciones)
                ->get()
                ->keyBy('clave');

            $creados = [];

            foreach ($acciones as $accion) {

                if (isset($existentes[$accion])) {
                    DB::table('efeso.privilegios')
                        ->where('id_privilegio', $existentes[$accion]->id_privilegio)
                        ->update(['estado'=>$estado]);
                    continue;
                }

                // FIX IMPORTANTE — aquí fallaba:
                $id = DB::table('efeso.privilegios')->insertGetId([
                    'id_modulo'  => $id_modulo,
                    'nombre'     => $nombre,
                    'clave'      => $accion,
                    'valor'      => null,
                    'comentario' => null,
                    'estado'     => $estado,
                ], 'id_privilegio'); // ← ESTO SOLUCIONA EL ERROR

                $creados[] = $id;
            }

            DB::commit();

            return ['success'=>true, 'data'=>['created'=>$creados]];

        } catch (\Throwable $e) {
            DB::rollBack();
            return ['success'=>false, 'message'=>$e->getMessage(), 'data'=>[]];
        }
    }

    /* ============================================================
     *         MATRIZ POR MÓDULO (READ)
     * ============================================================ */
    public static function listMatrixByModulo(int $id_modulo): array
    {
        try {
            $rows = DB::table('efeso.privilegios')
                ->select('id_privilegio','id_modulo','nombre','clave','estado')
                ->where('id_modulo', $id_modulo)
                ->get();

            $matrix = [];

            foreach ($rows as $r) {
                $nombre = $r->nombre;
                $clave  = $r->clave ?? $r->nombre;

                if (!isset($matrix[$nombre])) {
                    $matrix[$nombre] = [
                        'nombre'=>$nombre,
                        'crear'=>'0','listar'=>'0','editar'=>'0','eliminar'=>'0'
                    ];
                }

                switch ($clave) {
                    case 'Crear':   $matrix[$nombre]['crear']    = $r->estado; break;
                    case 'Listar':  $matrix[$nombre]['listar']   = $r->estado; break;
                    case 'Editar':  $matrix[$nombre]['editar']   = $r->estado; break;
                    case 'Eliminar':$matrix[$nombre]['eliminar'] = $r->estado; break;
                }
            }

            return ['success'=>true, 'data'=>array_values($matrix)];

        } catch (\Throwable $e) {
            return ['success'=>false, 'message'=>$e->getMessage()];
        }
    }

    /* ============================================================
     *  ASIGNACIÓN POR MÓDULO (NO CREA)
     * ============================================================ */
    public static function bulkAssign(int $id_modulo, array $items): array
    {
        try {
            DB::beginTransaction();

            $exist = DB::table('efeso.privilegios')
                ->select('id_privilegio','nombre','clave')
                ->where('id_modulo', $id_modulo)
                ->get();

            $index = [];
            foreach ($exist as $r) {
                $index[strtolower($r->nombre).'|'.$r->clave] = $r->id_privilegio;
            }

            foreach ($items as $row) {

                $nombre = $row['nombre'] ?? '';
                $targets = [
                    'Crear'    => $row['crear']    ?? null,
                    'Listar'   => $row['listar']   ?? null,
                    'Editar'   => $row['editar']   ?? null,
                    'Eliminar' => $row['eliminar'] ?? null,
                ];

                foreach ($targets as $accion => $flag) {
                    if ($flag === null) continue;

                    $key = strtolower($nombre).'|'.$accion;
                    if (!isset($index[$key])) continue;

                    DB::table('efeso.privilegios')
                        ->where('id_privilegio', $index[$key])
                        ->update(['estado'=>self::b2s($flag)]);
                }
            }

            DB::commit();
            return ['success'=>true];

        } catch (\Throwable $e) {
            DB::rollBack();
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }

    /* ============================================================
     *  ÁRBOL – READ
     * ============================================================ */
    private static function getDescendantModuleIds(int $id_parent): array
    {
        $sql = "
            WITH RECURSIVE tree AS (
                SELECT id_modulo,id_parent
                FROM efeso.modulo
                WHERE id_modulo=:root

                UNION ALL

                SELECT m.id_modulo,m.id_parent
                FROM efeso.modulo m
                JOIN tree t ON m.id_parent=t.id_modulo
            )
            SELECT id_modulo FROM tree WHERE id_modulo <> :root;
        ";

        $rows = DB::select($sql, ['root'=>$id_parent]);

        return array_map(fn($r)=>(int)$r->id_modulo, $rows);
    }

    public static function listTreeMatrix(int $id_parent): array
    {
        try {
            $ids = self::getDescendantModuleIds($id_parent);
            if (empty($ids)) return ['success'=>true,'data'=>['modules'=>[]]];

            $mods = DB::table('efeso.modulo')
                ->whereIn('id_modulo',$ids)
                ->orderBy('nivel')
                ->get()
                ->keyBy('id_modulo');

            $privs = DB::table('efeso.privilegios')
                ->whereIn('id_modulo',$ids)
                ->get();

            $by = [];
            foreach ($mods as $m) {
                $by[$m->id_modulo] = [
                    'module'=>$m,
                    'matrix'=>[]
                ];
            }

            foreach ($privs as $p) {

                if (!isset($by[$p->id_modulo]['matrix'][$p->nombre])) {
                    $by[$p->id_modulo]['matrix'][$p->nombre] = [
                        'nombre'=>$p->nombre,
                        'crear'=>'0','listar'=>'0','editar'=>'0','eliminar'=>'0'
                    ];
                }

                $clave = $p->clave ?? $p->nombre;

                switch ($clave) {
                    case 'Crear':   $by[$p->id_modulo]['matrix'][$p->nombre]['crear']    = $p->estado; break;
                    case 'Listar':  $by[$p->id_modulo]['matrix'][$p->nombre]['listar']   = $p->estado; break;
                    case 'Editar':  $by[$p->id_modulo]['matrix'][$p->nombre]['editar']   = $p->estado; break;
                    case 'Eliminar':$by[$p->id_modulo]['matrix'][$p->nombre]['eliminar'] = $p->estado; break;
                }
            }

            foreach ($by as $id=>&$row) {
                $row['matrix'] = array_values($row['matrix']);
            }

            return ['success'=>true,'data'=>['modules'=>array_values($by)]];

        } catch (\Throwable $e) {
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }

    /* ============================================================
     *  ÁRBOL – ASIGNACIÓN MASIVA (NO CREA)
     * ============================================================ */
    public static function bulkAssignForModules(int $id_parent, array $modulesPayload): array
    {
        try {
            DB::beginTransaction();

            $valid = array_flip(self::getDescendantModuleIds($id_parent));

            foreach ($modulesPayload as $m) {
                $id_mod = $m['id_modulo'] ?? 0;
                if (!isset($valid[$id_mod])) continue;

                $items = $m['items'] ?? [];
                self::bulkAssign($id_mod, $items);
            }

            DB::commit();
            return ['success'=>true];

        } catch (\Throwable $e) {
            DB::rollBack();
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }
}
