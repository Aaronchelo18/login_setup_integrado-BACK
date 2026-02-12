<?php

namespace App\Http\Data\Modules;

use App\Models\Modules\Modulo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ModuloData
{
    /**
     * Obtiene los módulos raíz asociados a una persona según sus roles.
     * Basado en el query: efeso.usuario -> usuario_rol -> rol -> rol_modulo -> modulo
     */
   public static function getAll($idPersona = null): array
    {
        try {
            if (!$idPersona) {
                return ['success' => false, 'message' => 'ID de persona no proporcionado.'];
            }

            // Usamos el esquema efeso tal como está en tu base de datos
            $rows = DB::table('efeso.usuario as u')
                ->join('efeso.usuario_rol as ur', 'u.id_persona', '=', 'ur.id_persona')
                ->join('efeso.rol as r', 'ur.id_rol', '=', 'r.id_rol')
                ->join('efeso.rol_modulo as rm', 'r.id_rol', '=', 'rm.id_rol')
                ->join('efeso.modulo as m', 'rm.id_modulo', '=', 'm.id_modulo')
                ->select(
                    'm.id_modulo',
                    'm.id_parent',
                    DB::raw('(m.nivel)::int AS nivel'),
                    'm.nombre', // Aseguramos que devuelva 'nombre' para el Front
                    'm.url',
                    'm.imagen',
                    'm.estado'
                )
                ->where('u.id_persona', $idPersona)
                ->where('m.id_parent', 0)
                ->where('r.estado', '1')
                ->where('m.estado', '1')
                ->distinct()
                ->orderBy('m.id_modulo')
                ->get()
                ->toArray();

            return ['success' => true, 'data' => $rows];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al obtener módulos: ' . $e->getMessage()];
        }
    }


    // GET /api/modulo/admin-list
    public static function listAllAdmin(): array
{
    try {
        $sql = "
            SELECT 
                id_modulo, 
                id_parent, 
                nombre, 
                url, 
                imagen, 
                estado
            FROM efeso.modulo
            WHERE id_parent = 0
            ORDER BY id_modulo ASC
        ";

        $data = DB::select($sql);

        return ['success' => true, 'data' => $data];
    } catch (\Throwable $e) {
        return ['success' => false, 'message' => 'Error en DB: ' . $e->getMessage()];
    }
}

// POST /api/config/setup/modulos (Admin)
public static function storeAdmin(array $params): array
{
    try {
        $dataToInsert = [
            'nombre'    => $params['nombre'],
            'url'       => $params['url'] ?? null,
            'imagen'    => $params['imagen'] ?? 'default.png',
            'estado'    => $params['estado'] ?? 1,
            'id_parent' => $params['id_parent'] ?? 0,
            'nivel'     => $params['nivel'] ?? 0, 
        ];

        // Insertar y obtener el ID generado
        $id = DB::table('efeso.modulo')->insertGetId($dataToInsert);

        // Recuperar el registro para confirmación
        $newRecord = DB::table('efeso.modulo')
            ->where('id_modulo', $id)
            ->first();

        return ['success' => true, 'data' => $newRecord];

    } catch (\Throwable $e) {
        return ['success' => false, 'message' => 'Error al crear módulo: ' . $e->getMessage()];
    }
}

// PUT /api/config/setup/modulos/{id} (Admin)
public static function updateAdmin(int $id, array $params): array
{
    try {
        $dataToUpdate = [];
        // Definimos qué campos permitimos
        $fields = ['nombre', 'url', 'imagen', 'estado', 'id_parent'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                $value = $params[$field];

                // Normalizar el estado: Convertir true/false o "1"/"0" a entero 1/0
                if ($field === 'estado') {
                    $value = ($value === true || $value === 1 || $value === '1') ? 1 : 0;
                }
                
                // Evitar que el id_parent sea null si la DB no lo permite
                if ($field === 'id_parent' && is_null($value)) {
                    $value = 0;
                }

                $dataToUpdate[$field] = $value;
            }
        }

        // Ejecutar el update filtrando por id_modulo
        DB::table('efeso.modulo')
            ->where('id_modulo', $id)
            ->update($dataToUpdate);

        $updatedRecord = DB::table('efeso.modulo')->where('id_modulo', $id)->first();

        return ['success' => true, 'data' => $updatedRecord];

    } catch (\Throwable $e) {
        // El error 500 se captura aquí. El mensaje te dirá exactamente qué columna falla.
        return ['success' => false, 'message' => 'Error en DB: ' . $e->getMessage()];
    }
}

// DELETE /api/config/setup/modulos/{id} (Admin)
public static function deleteAdmin(int $id): array
{
    try {
        // 1. Verificar si tiene submódulos (hijos)
        $hasChildren = DB::table('efeso.modulo')
            ->where('id_parent', $id)
            ->exists();

        if ($hasChildren) {
            return [
                'success' => false, 
                'message' => 'No se puede eliminar: el módulo tiene submódulos asignados.'
            ];
        }

        $deleted = DB::table('efeso.modulo')
            ->where('id_modulo', $id)
            ->delete();

        if ($deleted) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'El módulo no existe o ya fue eliminado.'];

    } catch (\Throwable $e) {
        return ['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()];
    }
}
    
    public static function show($id): array
    {
        try {
            $row = DB::table('efeso.modulo')
                ->select('id_modulo', 'id_parent', DB::raw('(nivel)::int AS nivel'), 'nombre', 'url', 'imagen', 'estado')
                ->where('id_modulo', (int)$id)
                ->first();

            if (!$row) return ['success' => false, 'message' => 'Módulo no encontrado'];

            return ['success' => true, 'data' => $row];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al mostrar módulo: ' . $e->getMessage()];
        }
    }

    public static function create(array $requestData): array
    {
        $v = Validator::make($requestData, [
            'id_parent' => 'required|integer|min:0',
            'nombre'    => 'required|string|max:128',
            'nivel'     => 'nullable|integer|min:0',
            'url'       => 'nullable|string|max:264',
            'imagen'    => 'nullable|string|max:128',
            'estado'    => 'nullable|in:0,1',
        ]);

        if ($v->fails()) return ['success' => false, 'message' => 'Datos inválidos.', 'errors' => $v->errors()];

        try {
            $data = $v->validated();
            $data['id_parent'] = (int)($data['id_parent'] ?? 0);
            $data['nombre']    = trim($data['nombre']);
            $data['url']       = !empty($data['url']) ? trim($data['url']) : null;
            $data['imagen']    = !empty($data['imagen']) ? trim($data['imagen']) : 'default.png';
            $data['estado']    = ($data['estado'] ?? '1') === '1' ? '1' : '0';

            if (!array_key_exists('nivel', $data) || $data['nivel'] === null) {
                if ($data['id_parent'] === 0) {
                    $data['nivel'] = 0;
                } else {
                    $parent = DB::table('efeso.modulo')->select(DB::raw('(nivel)::int AS nivel'))
                        ->where('id_modulo', $data['id_parent'])->first();
                    if (!$parent) return ['success' => false, 'message' => 'Módulo padre no encontrado.'];
                    $data['nivel'] = ((int)$parent->nivel) + 1;
                }
            }

            $newId = DB::table('efeso.modulo')->insertGetId([
                'id_parent' => $data['id_parent'],
                'nombre'    => $data['nombre'],
                'nivel'     => (int)$data['nivel'],
                'url'       => $data['url'],
                'imagen'    => $data['imagen'],
                'estado'    => $data['estado'],
            ], 'id_modulo');

            return self::show($newId);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al crear el módulo: ' . $e->getMessage()];
        }
    }

    public static function update(Modulo $eloquent, array $requestData): array
    {
        $req = Validator::make($requestData, [
            'id_parent' => 'sometimes|required|integer|min:0',
            'nombre'    => 'sometimes|required|string|max:128',
            'nivel'     => 'sometimes|required|integer|min:0',
            'url'       => 'nullable|string|max:264',
            'imagen'    => 'nullable|string|max:128',
            'estado'    => 'sometimes|required|in:0,1',
        ]);

        if ($req->fails()) return ['success' => false, 'message' => 'Datos inválidos.', 'errors' => $req->errors()];

        try {
            $data = $req->validated();
            if (array_key_exists('url', $data))    $data['url']    = trim((string)$data['url']) ?: null;
            if (array_key_exists('imagen', $data)) $data['imagen'] = trim((string)$data['imagen']) ?: 'default.png';

            if (array_key_exists('id_parent', $data)) {
                $pid = (int)$data['id_parent'];
                if ($pid === 0) {
                    $data['nivel'] = 0;
                } else {
                    $parent = DB::table('efeso.modulo')->select(DB::raw('(nivel)::int AS nivel'))
                        ->where('id_modulo', $pid)->first();
                    if (!$parent) return ['success' => false, 'message' => 'Módulo padre no encontrado.'];
                    $data['nivel'] = ((int)$parent->nivel) + 1;
                }
            }

            DB::table('efeso.modulo')->where('id_modulo', $eloquent->id_modulo)->update($data);
            return self::show($eloquent->id_modulo);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al actualizar el módulo: ' . $e->getMessage()];
        }
    }

    public static function delete(Modulo $eloquent): array
    {
        try {
            $id = (int)$eloquent->id_modulo;
            $refsRole = DB::table('efeso.rol_modulo')->where('id_modulo', $id)->count();
            $refsPriv = DB::table('efeso.privilegios')->where('id_modulo', $id)->count();

            if ($refsRole > 0 || $refsPriv > 0) {
                return ['success' => false, 'message' => 'No se puede eliminar porque está relacionado con roles o privilegios.'];
            }

            DB::table('efeso.modulo')->where('id_modulo', $id)->delete();
            return ['success' => true, 'data' => ['id' => $id]];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al eliminar el módulo: ' . $e->getMessage()];
        }
    }

    public static function getAccessByModule($id): array
    {
        try {
            $resultados = DB::select("
                SELECT r.nombre AS rol, m.nombre AS modulo, p.nombre AS privilegio, p.clave, p.valor, p.comentario
                FROM efeso.rol_modulo_privilegio rmp
                JOIN efeso.rol r ON r.id_rol = rmp.id_rol
                JOIN efeso.modulo m ON m.id_modulo = rmp.id_modulo
                JOIN efeso.privilegios p ON p.id_privilegio = rmp.id_privilegio
                WHERE rmp.id_modulo = ?
                ORDER BY r.nombre, m.nombre, p.nombre
            ", [(int)$id]);

            return ['success' => true, 'data' => $resultados];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getParentModules(bool $includeInactives = false): array
    {
        try {
            $q = DB::table('efeso.modulo')
                ->select('id_modulo', 'nombre', 'id_parent', DB::raw('(nivel)::int AS nivel'), 'url', 'imagen', 'estado')
                ->where('nivel', 0)
                ->orderBy('nombre');

            if (!$includeInactives) $q->where('estado', '1');

            return ['success' => true, 'data' => $q->get()->toArray()];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getModuleHierarchy(): array
    {
        try {
            $resultado = DB::select("
                WITH RECURSIVE jerarquia AS (
                    SELECT id_modulo,nombre,id_parent, 1 AS nivel, ('/'||url)::TEXT AS ruta
                    FROM efeso.modulo
                    WHERE id_parent = 1
                    UNION ALL
                    SELECT m.id_modulo,m.nombre,m.id_parent, j.nivel+1, j.ruta || ' > ' || m.nombre
                    FROM efeso.modulo m JOIN jerarquia j ON m.id_parent = j.id_modulo
                )
                SELECT * FROM jerarquia ORDER BY ruta
            ");
            return ['success' => true, 'data' => $resultado];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getModulesTree(int $parentId = 0, bool $includeInactives = false): array
    {
        try {
            $rows = DB::table('efeso.modulo')
                ->select('id_modulo', 'nombre', 'id_parent', DB::raw('(nivel)::int AS nivel'), 'url', 'estado', 'imagen')
                ->when(!$includeInactives, fn($q) => $q->where('estado', '1'))
                ->orderBy('nivel')->orderBy('nombre')
                ->get()->map(fn($m) => (array)$m)->toArray();

            return ['success' => true, 'data' => self::buildTree($rows, $parentId)];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function buildTree(array $elements, int $parentId): array
    {
        $branch = [];
        foreach ($elements as $e) {
            if ((int)$e['id_parent'] === $parentId) {
                $children = self::buildTree($elements, (int)$e['id_modulo']);
                $e['children'] = $children ?: [];
                $branch[] = $e;
            }
        }
        return $branch;
    }

    public static function getOptions(bool $includeInactives = true): array
    {
        try {
            $sql = "
                WITH RECURSIVE t AS (
                    SELECT id_modulo,nombre,id_parent,(nivel)::int AS nivel,url,imagen,estado,
                           nombre::text AS path
                    FROM efeso.modulo WHERE id_parent = 0
                    UNION ALL
                    SELECT m.id_modulo,m.nombre,m.id_parent,(m.nivel)::int,m.url,m.imagen,m.estado,
                           (t.path || ' > ' || m.nombre)
                    FROM efeso.modulo m JOIN t ON m.id_parent = t.id_modulo
                )
                SELECT * FROM t
            ";
            if (!$includeInactives) $sql = "SELECT * FROM ({$sql}) x WHERE estado='1'";
            $sql .= " ORDER BY path";

            return ['success' => true, 'data' => DB::select($sql)];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getHierarchyTree(int $rootId = 0, bool $includeInactives = false): array
    {
        try {
            $q = DB::table('efeso.modulo')
                ->select('id_modulo', 'nombre', 'id_parent', 'nivel', 'url', 'estado', 'imagen')
                ->orderBy('nivel')->orderBy('nombre');

            if (!$includeInactives) $q->where('estado', '1');

            $rows = $q->get()->map(fn($x) => (array)$x)->toArray();

            if ($rootId === 0) {
                $roots = array_values(array_filter($rows, fn($m) => (int)$m['id_parent'] === 0));
                $tree = array_map(fn($r) => self::buildHierarchyBranch($rows, (int)$r['id_modulo']), $roots);
                return ['success' => true, 'data' => $tree];
            }

            return ['success' => true, 'data' => [self::buildHierarchyBranch($rows, $rootId)]];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function buildHierarchyBranch(array $all, int $parentId): array
    {
        $node = collect($all)->firstWhere('id_modulo', $parentId);
        if (!$node) return ['id_modulo' => $parentId, 'nombre' => '(no encontrado)', 'children' => []];

        $node['children'] = array_values(array_map(
            fn($m) => self::buildHierarchyBranch($all, (int)$m['id_modulo']),
            array_filter($all, fn($m) => (int)$m['id_parent'] === $parentId)
        ));
        return $node;
    }

    public static function createChildInHierarchy(array $data): array
    {
        $v = Validator::make($data, [
            'parent_id' => 'required|integer',
            'nombre'    => 'required|string|max:128',
            'url'       => 'nullable|string|max:264',
            'imagen'    => 'nullable|string|max:128',
            'estado'    => 'nullable|in:0,1',
        ]);

        if ($v->fails()) return ['success' => false, 'message' => 'Datos inválidos.', 'errors' => $v->errors()];

        try {
            $payload = $v->validated();
            $parent = DB::table('efeso.modulo')->select('id_modulo', DB::raw('(nivel)::int AS nivel'))
                ->where('id_modulo', (int)$payload['parent_id'])->first();

            if (!$parent) return ['success' => false, 'message' => 'Padre no encontrado'];

            $newId = DB::table('efeso.modulo')->insertGetId([
                'id_parent' => (int)$payload['parent_id'],
                'nombre'    => $payload['nombre'],
                'nivel'     => (int)$parent->nivel + 1,
                'url'       => $payload['url'] ?? null,
                'imagen'    => $payload['imagen'] ?? null,
                'estado'    => $payload['estado'] ?? '1',
            ], 'id_modulo');

            return self::show($newId);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function updateHierarchyNode(int $id, array $payload, bool $isPatch = false): array
    {
        $req = $isPatch ? 'sometimes' : 'required';
        $v = Validator::make($payload, [
            'parent_id' => "$req|integer|min:0",
            'nombre'    => "$req|string|max:128",
            'url'       => 'nullable|string|max:264',
            'imagen'    => 'nullable|string|max:128',
            'estado'    => "$req|in:0,1",
        ]);

        if ($v->fails()) return ['success' => false, 'errors' => $v->errors()];

        try {
            $data = $v->validated();
            if (isset($data['parent_id'])) {
                $parentId = (int)$data['parent_id'];
                $nivel = 0;
                if ($parentId !== 0) {
                    $p = DB::table('efeso.modulo')->where('id_modulo', $parentId)->first();
                    if (!$p) return ['success' => false, 'message' => 'Padre inexistente'];
                    $nivel = (int)$p->nivel + 1;
                }
                $data['id_parent'] = $parentId;
                $data['nivel'] = $nivel;
                unset($data['parent_id']);
            }

            DB::table('efeso.modulo')->where('id_modulo', $id)->update($data);
            return self::show($id);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function deleteHierarchyNode(int $id): array
    {
        try {
            $children = DB::table('efeso.modulo')->where('id_parent', $id)->count();
            if ($children > 0) return ['success' => false, 'status' => 409, 'message' => 'Tiene hijos'];

            DB::table('efeso.modulo')->where('id_modulo', $id)->delete();
            return ['success' => true, 'data' => ['id' => $id]];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}