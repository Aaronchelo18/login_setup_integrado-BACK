<?php

namespace App\Http\Controllers\Config;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    /**
     * Listado plano de módulos asignados a un rol (depuración).
     */
    public function modulesFlat(int $id_rol)
    {
        $rows = DB::table('rol_modulo AS rm')
            ->join('modulo AS m', 'm.id_modulo', '=', 'rm.id_modulo')
            ->where('rm.id_rol', $id_rol)
            ->orderBy('m.nivel')
            ->orderBy('m.id_modulo')
            ->get([
                'm.id_modulo',
                'm.nombre',
                'm.nivel',
                'm.url',
                'm.imagen',
                'm.estado'
            ]);

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /**
     * Construye un árbol completo para un rol con flag hasPriv en cada nodo.
     * (No se usa en tu UI principal, pero lo dejo disponible).
     */
    public function modulesTree(int $idRol)
    {
        $modsConPriv = DB::table('privilegios')
            ->select('id_modulo')->distinct()
            ->pluck('id_modulo')->toArray();

        $mods = DB::table('modulo as m')
            ->leftJoin('rol_modulo as rm', function ($j) use ($idRol) {
                $j->on('rm.id_modulo', '=', 'm.id_modulo')
                    ->where('rm.id_rol', '=', $idRol);
            })
            ->selectRaw('m.id_modulo, m.nombre, m.id_parent,
                         CASE WHEN rm.id_rol IS NULL THEN 0 ELSE 1 END as checked')
            ->orderBy('m.id_parent')->orderBy('m.id_modulo')
            ->get();

        $byParent = $mods->groupBy('id_parent');

        $build = null;
        $build = function ($m) use (&$build, $byParent, $modsConPriv) {
            $children = $byParent->get($m->id_modulo, collect())
                ->map(fn($c) => $build($c))
                ->values()
                ->toArray();

            return [
                'id_modulo' => (int) $m->id_modulo,
                'nombre'    => $m->nombre,
                'checked'   => (bool) $m->checked,
                'hasPriv'   => in_array($m->id_modulo, $modsConPriv, true),
                'children'  => $children,
            ];
        };

        // raíces = id_parent 0 o NULL
        $roots = collect()
            ->merge($byParent->get(0, collect()))
            ->merge($byParent->get(null, collect()))
            ->map(fn($r) => $build($r))
            ->values()
            ->toArray();

        $role = DB::table('rol')->select('id_rol', 'nombre', 'estado')
            ->where('id_rol', $idRol)->first();

        return response()->json([
            'success' => true,
            'role'    => $role,
            'data'    => $roots,
        ]);
    }

    /**
     * Construye el árbol para un rol desde un "root" dado,
     * devolviendo hasPriv para cada módulo si existe en privilegios.
     * Este es el endpoint recomendado para tu Interfaz 3.
     */
    public function modulesTreeByRoot(int $idRol, int $idRoot)
    {
        // 1) Módulos que tienen privilegios definidos
        $modsConPriv = DB::table('privilegios')
            ->select('id_modulo')->distinct()->pluck('id_modulo')->toArray();

        // 2) Root del subárbol
        $root = DB::table('modulo')->where('id_modulo', $idRoot)->first();
        if (!$root) {
            return response()->json(['success' => false, 'message' => 'Root no encontrado'], 404);
        }

        // 3) Construir subárbol con flags
        $tree = [$this->buildNode($root, $idRol, $modsConPriv)];

        $role = DB::table('rol')->select('id_rol', 'nombre', 'estado')
            ->where('id_rol', $idRol)->first();

        return response()->json([
            'success' => true,
            'role'    => $role,
            'data'    => $tree,
        ]);
    }

    /**
     * Nodo recursivo con:
     *  - checked: si el módulo está asignado al rol
     *  - hasPriv: si el módulo tiene catálogo en `privilegios`
     */
    private function buildNode($mod, int $idRol, array $modsConPriv)
    {
        // ¿Asignado a este rol?
        $checked = DB::table('rol_modulo')
            ->where('id_rol', $idRol)
            ->where('id_modulo', $mod->id_modulo)
            ->exists();

        // Hijos
        $children = DB::table('modulo')
            ->where('id_parent', $mod->id_modulo)
            ->orderBy('nombre')
            ->get();

        $childrenNodes = [];
        foreach ($children as $child) {
            $childrenNodes[] = $this->buildNode($child, $idRol, $modsConPriv);
        }

        return [
            'id_modulo' => (int) $mod->id_modulo,
            'nombre'    => $mod->nombre,
            'checked'   => (bool) $checked,
            'hasPriv'   => in_array($mod->id_modulo, $modsConPriv, true), // 🔑 SOLO si existe en `privilegios`
            'children'  => $childrenNodes,
        ];
    }


    /**
     * Sincroniza los módulos del rol dentro del subárbol de "id_root".
     * - Elimina las tuplas existentes de ese subárbol y sus privilegios de rol_modulo_privilegio.
     * - Inserta las nuevas tuplas marcadas por el usuario.
     */
    public function syncRoleModulesByRoot(int $idRol, int $idRoot, Request $req)
{
    $selected = collect($req->input('modulos', []))
        ->map(fn($v)=> (int)$v)
        ->unique()
        ->values();

    // 1) Subárbol completo y validación
    $fullTree  = $this->buildTreeForRole($idRol);
    $rootNode  = $this->findInTree($fullTree, $idRoot);
    $idsSub    = $this->collectIds($rootNode);

    if (!$rootNode) {
        return response()->json(['success'=>false,'message'=>'Root no encontrado en árbol del rol'], 404);
    }
    if ($selected->diff($idsSub)->isNotEmpty()) {
        return response()->json(['success'=>false,'message'=>'Ids fuera del subárbol'], 422);
    }

    // 2) Estado actual en el subárbol para este rol
    $currentSub = DB::table('rol_modulo')
        ->where('id_rol', $idRol)
        ->whereIn('id_modulo', $idsSub)
        ->pluck('id_modulo')
        ->map(fn($v)=>(int)$v)
        ->toArray();

    $selArr   = $selected->toArray();
    $toDelete = array_values(array_diff($currentSub, $selArr)); // salen
    $toInsert = array_values(array_diff($selArr, $currentSub)); // entran

    DB::transaction(function() use ($idRol, $toDelete, $toInsert) {
        // 3) Primero borra privilegios SOLO de los que salen
        if (!empty($toDelete)) {
            DB::table('rol_modulo_privilegio')
              ->where('id_rol', $idRol)
              ->whereIn('id_modulo', $toDelete)
              ->delete();

            DB::table('rol_modulo')
              ->where('id_rol', $idRol)
              ->whereIn('id_modulo', $toDelete)
              ->delete();
        }

        // 4) Inserta los nuevos (sin tocar los que permanecen)
        if (!empty($toInsert)) {
            // Si prefieres bulk con ON CONFLICT (PostgreSQL):
            $table = DB::getTablePrefix().'rol_modulo';
            $values = collect($toInsert)
                ->map(fn($m) => '(' . (int)$idRol . ',' . (int)$m . ')')
                ->implode(',');
            DB::statement("
                INSERT INTO {$table} (id_rol, id_modulo)
                VALUES {$values}
                ON CONFLICT (id_rol, id_modulo) DO NOTHING
            ");
        }
    });

    return response()->json([
        'success' => true,
        'count'   => $selected->count()
    ]);
}

    /**
     * Helpers para construir / validar subárbol del rol.
     */

    private function buildTreeForRole(int $idRol): array
    {
        $modulos = DB::table('modulo')
            ->leftJoin('rol_modulo', function ($join) use ($idRol) {
                $join->on('modulo.id_modulo', '=', 'rol_modulo.id_modulo')
                    ->where('rol_modulo.id_rol', '=', $idRol);
            })
            ->select(
                'modulo.id_modulo',
                'modulo.nombre',
                'modulo.id_parent',
                DB::raw('CASE WHEN rol_modulo.id_rol IS NULL THEN 0 ELSE 1 END as checked')
            )
            ->orderBy('modulo.id_parent')
            ->get();

        $tree = [];
        $indexed = [];
        foreach ($modulos as $m) {
            $indexed[$m->id_modulo] = (array) $m;
            $indexed[$m->id_modulo]['children'] = [];
        }

        foreach ($indexed as $id => &$node) {
            if ($node['id_parent'] == 0 || $node['id_parent'] === null) {
                $tree[] = &$node;
            } else {
                if (isset($indexed[$node['id_parent']])) {
                    $indexed[$node['id_parent']]['children'][] = &$node;
                }
            }
        }

        return $tree;
    }

    private function findInTree(array $nodes, int $idRoot)
    {
        foreach ($nodes as $node) {
            if (($node['id_modulo'] ?? null) == $idRoot) return $node;

            if (!empty($node['children'])) {
                $found = $this->findInTree($node['children'], $idRoot);
                if ($found) return $found;
            }
        }
        return null;
    }

    private function collectIds(?array $node)
    {
        $out = collect();
        $walk = function ($n) use (&$walk, &$out) {
            if (!$n) return;
            $out->push((int)$n['id_modulo']);
            foreach ($n['children'] ?? [] as $c) $walk($c);
        };
        $walk($node);
        return $out->unique()->values();
    }
}
