<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Modules\Modulo;
use App\Http\Data\Modules\ModuloData;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModuloController extends Controller
{
    use ApiResponse;

    // SIDEBAR GET ID_PERSONA
    public function index(Request $request)
    {
        // Capturamos el id_persona enviado desde Angular
        $idPersona = $request->query('id_persona');

        if (!$idPersona) {
            return $this->error('El parámetro id_persona es requerido para cargar los módulos.', 400);
        }

        $r = ModuloData::getAll($idPersona); 
        
        return $r['success'] 
            ? $this->ok($r['data'], 'Módulos recuperados correctamente') 
            : $this->error($r['message'], 500);
    }


    // GET /api/modulo/admin-list
    public function listAllAdmin(Request $request)
{
    // Llamamos a la nueva función sin pasarle parámetros de persona
    $r = ModuloData::listAllAdmin(); 
    
    return $r['success'] 
        ? $this->ok($r['data'], 'Lista de módulos (Vista Admin) recuperada') 
        : $this->error($r['message'], 500);
}

    // POST /api/config/setup/modulos
public function storeAdmin(Request $request)
{
    $params = $request->all();

    // Validaciones básicas de campos obligatorios
    if (empty($params['nombre'])) {
        return $this->error('El nombre del módulo es obligatorio', 400);
    }

    $r = ModuloData::storeAdmin($params);

    return $r['success'] 
        ? $this->ok($r['data'], 'Módulo creado con éxito', 201) 
        : $this->error($r['message'], 500);
}

    // PUT /api/config/setup/modulos/{id}
public function updateAdmin(Request $request, $id)
{
    $params = $request->all();
    
    // Si sigue dando 422, comenta cualquier validación que tengas arriba de esto
    $r = ModuloData::updateAdmin((int)$id, $params);

    return $r['success'] 
        ? $this->ok($r['data'], 'Actualizado correctamente') 
        : $this->error($r['message'], 500);
}

    // DELETE /api/config/setup/modulos/{id}
public function destroyAdmin($id)
{
    // Llamamos a la lógica en el Data
    $r = ModuloData::deleteAdmin($id);

    return $r['success'] 
        ? $this->ok(null, 'Módulo eliminado correctamente') 
        : $this->error($r['message'], 400); // 400 si hay hijos o error
}



    public function store(Request $request)
    {
        $r = ModuloData::create($request->all());

        if (!$r['success']) {
            return isset($r['errors'])
                ? $this->information($r['message'] ?? 'Datos inválidos.', 422, $r['errors'])
                : $this->error($r['message'] ?? 'Error al crear módulo', 500);
        }

        return $this->ok($r['data'], 'Module created successfully', 201);
    }

    public function show($id)
    {
        $r = ModuloData::show($id);
        return $r['success'] ? $this->ok($r['data'], 'Module retrieved successfully')
                             : $this->error($r['message'], 404);
    }

    public function update(Request $request, $id)
    {
        $modulo = Modulo::find($id); 
        if (!$modulo) return $this->error('Module not found', 404);

        $r = ModuloData::update($modulo, $request->all());

        if (!$r['success']) {
            return isset($r['errors'])
                ? $this->information($r['message'], 422, $r['errors'])
                : $this->error($r['message'], 500);
        }

        return $this->ok($r['data'], 'Module updated successfully');
    }

    public function destroy($id)
    {
        $modulo = Modulo::find($id);
        if (!$modulo) return $this->error('Module not found', 404);

        $r = ModuloData::delete($modulo);
        return $r['success'] ? $this->ok($r['data'] ?? [], 'Module deleted successfully', 200)
                             : $this->error($r['message'], 500);
    }

    public function patch(Request $request, $id)
    {
        $modulo = Modulo::find($id);
        if (!$modulo) return $this->error('Module not found', 404);

        $response = ModuloData::update($modulo, $request->all());

        if (!$response['success']) {
            return isset($response['errors'])
                ? $this->information($response['message'], 422, $response['errors'])
                : $this->error($response['message'], 500);
        }

        return $this->ok($response['data'], 'Módulo actualizado parcialmente');
    }

    public function getAccessByModule($id)
    {
        $response = ModuloData::getAccessByModule($id);
        return $response['success']
            ? $this->ok($response['data'], 'Accesos obtenidos correctamente')
            : $this->error($response['message'], 500);
    }

    public function getParentModules(Request $request)
    {
        $includeInactives = filter_var($request->query('include_inactives', 'false'), FILTER_VALIDATE_BOOLEAN);
        $response = ModuloData::getParentModules($includeInactives);

        return $response['success']
            ? $this->ok($response['data'], 'Módulos padre obtenidos correctamente')
            : $this->error($response['message'], 500);
    }

    public function getHierarchy()
    {
        $response = ModuloData::getModuleHierarchy();
        return $response['success']
            ? $this->ok($response['data'], 'Jerarquía de módulos obtenida correctamente')
            : $this->error($response['message'], 500);
    }

    public function getTree(Request $request)
    {
        $parentId = (int) $request->query('parent_id', 1);
        $includeInactives = filter_var($request->query('include_inactives', 'false'), FILTER_VALIDATE_BOOLEAN);
        $response = ModuloData::getModulesTree($parentId, $includeInactives);

        return $response['success']
            ? $this->ok($response['data'], 'Árbol de módulos generado correctamente')
            : $this->error($response['message'], 500);
    }

    public function getOptions(Request $request)
    {
        $includeInactives = filter_var($request->query('include_inactives', 'true'), FILTER_VALIDATE_BOOLEAN);
        $response = ModuloData::getOptions($includeInactives);

        return $response['success']
            ? $this->ok($response['data'], 'Opciones de módulos obtenidas correctamente')
            : $this->error($response['message'], 500);
    }

    public function getHierarchyTree(Request $request)
    {
        $rootId = (int) $request->query('root_id', 0);
        $includeInactives = filter_var($request->query('include_inactives', 'false'), FILTER_VALIDATE_BOOLEAN);
        $response = ModuloData::getHierarchyTree($rootId, $includeInactives);

        return $response['success']
            ? $this->ok($response['data'], 'Jerarquía de módulos (árbol) obtenida correctamente')
            : $this->error($response['message'] ?? 'Error al obtener jerarquía', 500);
    }

    public function createInHierarchy(Request $request)
    {
        $resp = ModuloData::createChildInHierarchy($request->all());
        if (!$resp['success']) {
            return isset($resp['errors'])
                ? $this->information($resp['message'] ?? 'Datos inválidos.', 422, $resp['errors'])
                : $this->error($resp['message'] ?? 'Error al crear nodo', 500);
        }
        return $this->ok($resp['data'], 'Nodo creado en la jerarquía correctamente', 201);
    }

    public function updateHierarchyNode(Request $request, int $id)
    {
        $r = ModuloData::updateHierarchyNode($id, $request->all(), false);
        if (!$r['success']) {
            return isset($r['errors'])
                ? $this->information($r['message'], 422, $r['errors'])
                : $this->error($r['message'], 500);
        }
        return $this->ok($r['data'], 'Nodo actualizado correctamente');
    }

    public function patchHierarchyNode(Request $request, int $id)
    {
        $r = ModuloData::updateHierarchyNode($id, $request->all(), true);
        if (!$r['success']) {
            return isset($r['errors'])
                ? $this->information($r['message'], 422, $r['errors'])
                : $this->error($r['message'], 500);
        }
        return $this->ok($r['data'], 'Nodo actualizado parcialmente');
    }

    public function deleteHierarchyNode(Request $request, int $id)
    {
        $r = ModuloData::deleteHierarchyNode($id);
        if (!$r['success']) {
            return $this->error($r['message'], $r['status'] ?? 500);
        }
        return $this->ok($r['data'] ?? [], 'Nodo eliminado correctamente', 200);
    }
}