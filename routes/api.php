<?php

use Illuminate\Support\Facades\Route;

/** ===== INTEGRAR (NUEVO) ===== */
use App\Http\Controllers\Utility\UserInfoController;
use App\Http\Controllers\Modules\ModuloController;
use App\Http\Controllers\Privileges\PrivilegioController;
use App\Http\Controllers\Faculty\FacultadController;
use App\Http\Controllers\Campus\CampusController;
use App\Http\Controllers\Program\ProgramaEstudioController;

/** ===== VIEJO ===== */
use App\Http\Controllers\Academic\AcademicCatalogController;
use App\Http\Controllers\Config\AccessController;
use App\Http\Controllers\Role\RoleModuleController;
use App\Http\Controllers\Config\RolePrivilegeController;
use App\Http\Controllers\Config\UserRoleController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Users\UserAccessController;


Route::get('modulo/admin-list', [ModuloController::class, 'listAllAdmin']);


Route::prefix('managemt')->group(function () {
    // INTEGRAR (nuevo) - users CRUD
    Route::get('users', [UserInfoController::class, 'index']);
    Route::post('users', [UserInfoController::class, 'store']);
    Route::get('users/{user}', [UserInfoController::class, 'show']);
    Route::put('users/{user}', [UserInfoController::class, 'update']);
    Route::patch('users/{user}', [UserInfoController::class, 'update']);
    Route::delete('users/{user}', [UserInfoController::class, 'destroy']);
});


Route::prefix('config/setup')->group(function () {
    // INTEGRAR (nuevo) - módulos
    Route::get('modulos/arbol', [ModuloController::class, 'getTree']);
    Route::get('modulos/padres', [ModuloController::class, 'getParentModules']);
    Route::get('modulos/opciones', [ModuloController::class, 'getOptions']);

    // MODULO ADMIN
    Route::post('modulos', [ModuloController::class, 'storeAdmin']);
    Route::put('modulos/{modulo}', [ModuloController::class, 'updateAdmin']);
    Route::patch('modulos/{modulo}', [ModuloController::class, 'updateAdmin']);
    Route::delete('modulos/{modulo}', [ModuloController::class, 'destroyAdmin']);
    
    Route::get('modulos/jerarquia/tree', [ModuloController::class, 'getHierarchyTree']);
    Route::post('modulos/jerarquia', [ModuloController::class, 'createInHierarchy']);
    Route::put('modulos/jerarquia/{id}', [ModuloController::class, 'updateHierarchyNode']);
    Route::patch('modulos/jerarquia/{id}', [ModuloController::class, 'patchHierarchyNode']);
    Route::delete('modulos/jerarquia/{id}', [ModuloController::class, 'deleteHierarchyNode']);

    Route::get('modulos', [ModuloController::class, 'index']); // Este sigue pidiendo id_persona para el sidebar
    Route::post('modulos', [ModuloController::class, 'store']);
    Route::get('modulos/{modulo}', [ModuloController::class, 'show']);
    Route::put('modulos/{modulo}', [ModuloController::class, 'update']);
    Route::patch('modulos/{modulo}', [ModuloController::class, 'update']);
    Route::delete('modulos/{modulo}', [ModuloController::class, 'destroy']);

    // INTEGRAR (nuevo) - access (queda igual)
    Route::get('modules/{id}/access', [ModuloController::class, 'getAccessByModule']);


    // INTEGRAR (nuevo) - privilegios
    Route::get('modulos/{id_modulo}/privilegios', [PrivilegioController::class, 'index']);
    Route::post('modulos/{id_modulo}/privilegios', [PrivilegioController::class, 'store']);
    Route::put('modulos/privilegios/{id_privilegio}', [PrivilegioController::class, 'update']);
    Route::patch('modulos/privilegios/{id_privilegio}', [PrivilegioController::class, 'patch']);
    Route::delete('modulos/privilegios/{id_privilegio}', [PrivilegioController::class, 'destroy']);
    Route::post('modulos/{id_modulo}/privilegios/bulk', [PrivilegioController::class, 'bulkUpsert']);

    Route::get('modulos/{id_modulo}/privilegios/matrix', [PrivilegioController::class, 'matrix']);
    Route::post('modulos/{id_modulo}/privilegios/matrix', [PrivilegioController::class, 'assignMatrix']);

    Route::get('modulos/{id_parent}/privilegios/tree-matrix', [PrivilegioController::class, 'treeMatrix']);
    Route::post('modulos/{id_parent}/privilegios/tree-matrix', [PrivilegioController::class, 'assignTreeMatrix']);

    Route::get('modulos/{id_modulo}/privilegios/catalog', [PrivilegioController::class, 'catalog']);
    Route::post('modulos/{id_modulo}/privilegios/catalog-create', [PrivilegioController::class, 'createCatalog']);
    Route::post('modulos/{id_modulo}/privilegios/catalog', [PrivilegioController::class, 'createForModule']);
    Route::delete('modulos/privilegios/row/{id_privilegio}', [PrivilegioController::class, 'destroyRow']);


    // INTEGRAR (nuevo) - catálogos académicos
    Route::get('facultades', [FacultadController::class, 'index']);
    Route::get('facultades/{id}', [FacultadController::class, 'show']);

    Route::get('campus', [CampusController::class, 'index']);
    Route::get('campus/{id}', [CampusController::class, 'show']);

    Route::get('programas', [ProgramaEstudioController::class, 'index']);
    Route::get('programas/{id}', [ProgramaEstudioController::class, 'show']);


    /**
     * ==========================
     * VIEJO: se “encaja” aquí
     * SIN tocar lo del integrar
     * ==========================
     */

    // Roles (v1/config/roles -> config/setup/roles)
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::put('roles/{id_rol}/status', [RoleController::class, 'updateStatus']);
    Route::put('roles/{id_rol}', [RoleController::class, 'updateName']);
    Route::delete('roles/{id_rol}', [RoleController::class, 'destroy']);

    // Accesos
    Route::get('roles/{id_rol}/modulos-flat', [AccessController::class, 'modulesFlat']);
    Route::get('roles/{id_rol}/modulos-tree', [AccessController::class, 'modulesTree']);
    Route::get('roles/{id_rol}/modulos/{id_root}/tree', [AccessController::class, 'modulesTreeByRoot']);
    Route::match(['put','post'], 'roles/{id_rol}/modulos', [RoleModuleController::class,'sync']);
    Route::put('roles/{id_rol}/modulos/{id_root}', [AccessController::class, 'syncRoleModulesByRoot']);

    @Route::get('roles/{id_rol}/modulos-levels', [AccessController::class, 'modulesByLevels']);
    Route::get('roles/{id_rol}/modulos-level-tree', [AccessController::class, 'modulesLevelTree']);

    // Privilegios del viejo (ojo: NO choca porque son rutas distintas a las del integrar)
    Route::get('roles/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'assigned']);
    Route::post('roles/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'store']);
    Route::get('modulos/{id_modulo}/privilegios-catalogo-viejo', [RolePrivilegeController::class, 'catalogByModule']);
});



Route::prefix('v1/config')->group(function () {
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);

    Route::get('roles/{id_rol}/modulos-flat', [AccessController::class, 'modulesFlat']);
    Route::get('roles/{id_rol}/modulos-tree', [AccessController::class, 'modulesTree']);
    Route::get('roles/{id_rol}/modulos/{id_root}/tree', [AccessController::class, 'modulesTreeByRoot']);

    Route::match(['put','post'],'roles/{id_rol}/modulos', [RoleModuleController::class,'sync']);
    Route::put('roles/{id_rol}/modulos/{id_root}', [AccessController::class, 'syncRoleModulesByRoot']);

    Route::put('roles/{id_rol}/status', [RoleController::class, 'updateStatus']);
    Route::put('roles/{id_rol}', [RoleController::class, 'updateName']);
    Route::delete('roles/{id_rol}', [RoleController::class, 'destroy']);

    Route::get('modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'catalogByModule']);
    Route::get('roles/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'assigned']);
    Route::post('roles/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'store']);

    Route::get('roles/{id_rol}/modulos-levels', [AccessController::class, 'modulesByLevels']);
    Route::get('roles/{id_rol}/modulos-level-tree', [AccessController::class, 'modulesLevelTree']);
});

Route::prefix('management')->group(function () {
    Route::get('/users', [UserAccessController::class, 'searchUsuario']);
    Route::get('/users/{id}/accesses', [UserAccessController::class, 'index']);
    Route::post('/users/{id}/accesses', [UserAccessController::class, 'store']);
    Route::delete('/users/{id}/accesses/{aid}', [UserAccessController::class, 'destroy']);

    Route::get('/campus', [AcademicCatalogController::class, 'campus']);
    Route::get('/facultades', [AcademicCatalogController::class, 'facultades']);
    Route::get('/programas-estudio', [AcademicCatalogController::class, 'programasEstudio']);
    Route::get('/user-access/reports', [AcademicCatalogController::class, 'getAllAccessReports']);

    // VIEJO (se agrega, NO se modifica ruta)
    Route::get('users/search', [UserRoleController::class, 'search']);
    Route::post('/roles/useusersrs/{id_persona}/roles', [UserRoleController::class, 'saveForUser']);
    Route::get('users/{id_persona}/roles', [UserRoleController::class, 'assignedToUser']);
});