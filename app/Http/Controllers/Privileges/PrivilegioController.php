<?php
namespace App\Http\Controllers\Privileges;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Data\Privileges\PrivilegioData;
use App\Traits\ApiResponse;

class PrivilegioController extends Controller
{
    use ApiResponse;

    /** ============================================
     *   MATRIZ POR MÓDULO (solo lectura)
     * ============================================ */
    public function matrix(int $id_modulo)
    {
        $r = PrivilegioData::listMatrixByModulo($id_modulo);
        return $r['success']
            ? $this->ok($r['data'], 'Matriz de privilegios obtenida')
            : $this->error($r['message'], 500);
    }

    /** ============================================
     *   ASIGNACIÓN POR MÓDULO
     * ============================================ */
    public function assignMatrix(Request $req, int $id_modulo)
    {
        $items = $req->input('items', []);
        $r = PrivilegioData::bulkAssign($id_modulo, $items);

        return $r['success']
            ? $this->ok([], 'Cambios guardados')
            : $this->error($r['message'], 500);
    }

    /** ============================================
     *   MATRIZ POR ÁRBOL DE DESCENDIENTES
     * ============================================ */
    public function treeMatrix(int $id_parent)
    {
        $r = PrivilegioData::listTreeMatrix($id_parent);
        return $r['success']
            ? $this->ok($r['data'], 'Matriz de privilegios por árbol obtenida')
            : $this->error($r['message'], 500);
    }

    /** ============================================
     *   ASIGNACIÓN MASIVA POR ÁRBOL
     * ============================================ */
    public function assignTreeMatrix(Request $req, int $id_parent)
    {
        $modules = $req->input('modules', []);
        $normalized = [];

        foreach ($modules as $m) {
            $id_mod = (int)($m['id_modulo'] ?? 0);
            if ($id_mod <= 0) continue;

            if (isset($m['items']) && is_array($m['items'])) {
                $items = $m['items'];
            } else {
                $items = [[
                    'nombre'   => $m['nombre']   ?? '*',
                    'crear'    => $m['crear']    ?? null,
                    'listar'   => $m['listar']   ?? null,
                    'editar'   => $m['editar']   ?? null,
                    'eliminar' => $m['eliminar'] ?? null,
                ]];
            }

            $normalized[] = ['id_modulo' => $id_mod, 'items' => $items];
        }

        $r = PrivilegioData::bulkAssignForModules($id_parent, $normalized);
        return $r['success']
            ? $this->ok([], 'Cambios guardados')
            : $this->error($r['message'], 500);
    }

    /* ============================================================
     *   NUEVO: CATÁLOGO RAW (crear privilegios base)
     * ============================================================ */

    /** GET /modulos/{id_modulo}/privilegios/catalog */
    public function catalog(int $id_modulo)
    {
        $r = PrivilegioData::listMatrixByModulo($id_modulo);
        return $r['success']
            ? $this->ok($r['data'], 'Privilegios del módulo')
            : $this->error($r['message'], 500);
    }

    /** POST /modulos/{id_modulo}/privilegios/catalog */
    public function createCatalog(Request $request, int $id_modulo)
    {
        $payload = $request->validate([
            'nombre'     => 'required|string|max:120',
            'acciones'   => 'required|array|min:1',
            'acciones.*' => 'string|in:Crear,Listar,Editar,Eliminar',
            'estado'     => 'nullable|in:0,1',
        ]);

        $nombre   = $payload['nombre'];
        $acciones = $payload['acciones'];
        $estado   = $payload['estado'] ?? '1';

        $r = PrivilegioData::createCatalog($id_modulo, $nombre, $acciones, $estado);

        return $r['success']
            ? $this->ok($r['data'], 'Catálogo creado / actualizado')
            : $this->error($r['message'], 500);
    }
}
