<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - SISTEMA INTEGRADO (Login + Setup)
|--------------------------------------------------------------------------
*/

/** ===== CONTROLADORES DE AUTENTICACIÓN Y PERSONAS (Login) ===== */
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Person\PersonController;
use App\Http\Controllers\PersonVirtual\PersonVirtualController;
use App\Http\Controllers\StudenPerson\StudentPersonController;
use App\Http\Controllers\WorkerPerson\WorkerPersonController;

/** ===== CONTROLADORES DE GESTIÓN Y CONFIGURACIÓN (Setup) ===== */
use App\Http\Controllers\Utility\UserInfoController;
use App\Http\Controllers\Modules\ModuloController;
use App\Http\Controllers\Privileges\PrivilegioController;
use App\Http\Controllers\Faculty\FacultadController;
use App\Http\Controllers\Campus\CampusController;
use App\Http\Controllers\Program\ProgramaEstudioController;

/** ===== CONTROLADORES LEGACY (Viejo) ===== */
use App\Http\Controllers\Academic\AcademicCatalogController;
use App\Http\Controllers\Config\AccessController;
use App\Http\Controllers\Role\RoleModuleController;
use App\Http\Controllers\Config\RolePrivilegeController;
use App\Http\Controllers\Config\UserRoleController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Users\UserAccessController;

/*
|--------------------------------------------------------------------------
| 1. AUTENTICACIÓN (Login & Auth)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Google Auth
    Route::get('google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);

    // Password Management
    Route::post('set-password', [AuthController::class, 'setPassword']);
    Route::post('login-password', [AuthController::class, 'loginWithPassword']);
    Route::post('forgot-password', [AuthController::class, 'reenviarLinkCrearClave']);

    // Ruta protegida para obtener el usuario actual
    Route::middleware('auth:api')->get('me', [AuthController::class, 'me']);
});

/*
|--------------------------------------------------------------------------
| 2. GESTIÓN GLOBAL DE PERSONAS
|--------------------------------------------------------------------------
*/
Route::prefix('global/config')->group(function () {
    // Personas Básicas
    Route::get('person', [PersonController::class, 'index']);
    Route::get('person/{id}', [PersonController::class, 'show']);
    Route::post('person', [PersonController::class, 'store']);
    Route::put('person/{person}', [PersonController::class, 'update']);
    Route::patch('person/{person}', [PersonController::class, 'update']);
    Route::delete('person/{person}', [PersonController::class, 'destroy']);

    // Correos Virtuales
    Route::get('persona_virtual', [PersonVirtualController::class, 'index']);
    Route::get('persona_virtual/{id}', [PersonVirtualController::class, 'show']);
    Route::post('persona_virtual', [PersonVirtualController::class, 'store']);
    Route::put('persona_virtual/{persona_virtual}', [PersonVirtualController::class, 'update']);
    Route::patch('persona_virtual/{persona_virtual}', [PersonVirtualController::class, 'update']);
    Route::delete('persona_virtual/{persona_virtual}', [PersonVirtualController::class, 'destroy']);

    // Alumnos (Student)
    Route::get('student-persons', [StudentPersonController::class, 'index']);
    Route::get('student-persons/{student}', [StudentPersonController::class, 'show']);
    Route::post('student-persons', [StudentPersonController::class, 'store']);
    Route::put('student-persons/{student}', [StudentPersonController::class, 'update']);
    Route::patch('student-persons/{student}', [StudentPersonController::class, 'update']);
    Route::delete('student-persons/{student}', [StudentPersonController::class, 'destroy']);

    // Trabajadores (Worker)
    Route::get('worker-person', [WorkerPersonController::class, 'index']);
    Route::get('worker-person/{trabajador}', [WorkerPersonController::class, 'show']);
    Route::post('worker-person', [WorkerPersonController::class, 'store']);
    Route::put('worker-person/{trabajador}', [WorkerPersonController::class, 'update']);
    Route::patch('worker-person/{trabajador}', [WorkerPersonController::class, 'update']);
    Route::delete('worker-person/{trabajador}', [WorkerPersonController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| 3. GESTIÓN DE USUARIOS (Management)
|--------------------------------------------------------------------------
*/
Route::prefix('iam/role-assignment')->group(function () {
    Route::get('users', [UserInfoController::class, 'index']);
    Route::get('users/{user}', [UserInfoController::class, 'show']);
    Route::post('users', [UserInfoController::class, 'store']);
    Route::put('users/{user}', [UserInfoController::class, 'update']);
    Route::patch('users/{user}', [UserInfoController::class, 'update']);
    Route::delete('users/{user}', [UserInfoController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| 4. CONFIGURACIÓN DEL SISTEMA (Setup / Application Management)
|--------------------------------------------------------------------------
*/
Route::get('application-management/modules/admin-list', [ModuloController::class, 'listAllAdmin']);

Route::prefix('application-management')->group(function () {
    // Módulos: Consultas y Árboles
    Route::get('modules/arbol', [ModuloController::class, 'getTree']);
    Route::get('modules/padres', [ModuloController::class, 'getParentModules']);
    Route::get('modules/opciones', [ModuloController::class, 'getOptions']);
    Route::get('modules/jerarquia/tree', [ModuloController::class, 'getHierarchyTree']);
    Route::get('modules', [ModuloController::class, 'index']);
    Route::get('modules/{modulo}', [ModuloController::class, 'show']);
    
    // Módulos: Acciones Admin/Hierarchy
    Route::post('modules', [ModuloController::class, 'storeAdmin']);
    Route::post('modules/jerarquia', [ModuloController::class, 'createInHierarchy']);
    Route::put('modules/jerarquia/{id}', [ModuloController::class, 'updateHierarchyNode']);
    Route::patch('modules/jerarquia/{id}', [ModuloController::class, 'patchHierarchyNode']);
    Route::delete('modules/jerarquia/{id}', [ModuloController::class, 'deleteHierarchyNode']);

    // Módulos: CRUD General
    Route::post('modules/store-basic', [ModuloController::class, 'store']); 
    Route::put('modules/{modulo}', [ModuloController::class, 'updateAdmin']);
    Route::patch('modules/{modulo}', [ModuloController::class, 'updateAdmin']);
    Route::delete('modules/{modulo}', [ModuloController::class, 'destroyAdmin']);
    Route::get('modules/{id}/access', [ModuloController::class, 'getAccessByModule']);

    // Privilegios
    Route::get('modules/{id_modulo}/privilegios', [PrivilegioController::class, 'index']);
    Route::get('modules/{id_modulo}/privilegios/matrix', [PrivilegioController::class, 'matrix']);
    Route::get('modules/{id_parent}/privilegios/tree-matrix', [PrivilegioController::class, 'treeMatrix']);
    Route::get('modules/{id_modulo}/privilegios/catalog', [PrivilegioController::class, 'catalog']);
    
    Route::post('modules/{id_modulo}/privilegios', [PrivilegioController::class, 'store']);
    Route::post('modules/{id_modulo}/privilegios/bulk', [PrivilegioController::class, 'bulkUpsert']);
    Route::post('modules/{id_modulo}/privilegios/matrix', [PrivilegioController::class, 'assignMatrix']);
    Route::post('modules/{id_parent}/privilegios/tree-matrix', [PrivilegioController::class, 'assignTreeMatrix']);
    Route::post('modules/{id_modulo}/privilegios/catalog-create', [PrivilegioController::class, 'createCatalog']);
    Route::post('modules/{id_modulo}/privilegios/catalog-assign', [PrivilegioController::class, 'createForModule']);
    
    Route::put('modules/privilegios/{id_privilegio}', [PrivilegioController::class, 'update']);
    Route::patch('modules/privilegios/{id_privilegio}', [PrivilegioController::class, 'patch']);
    Route::delete('modules/privilegios/{id_privilegio}', [PrivilegioController::class, 'destroy']);
    Route::delete('modules/privilegios/row/{id_privilegio}', [PrivilegioController::class, 'destroyRow']);

    // Catálogos Académicos
    Route::get('facultades', [FacultadController::class, 'index']);
    Route::get('facultades/{id}', [FacultadController::class, 'show']);
    Route::get('campus', [CampusController::class, 'index']);
    Route::get('campus/{id}', [CampusController::class, 'show']);
    Route::get('programas', [ProgramaEstudioController::class, 'index']);
    Route::get('programas/{id}', [ProgramaEstudioController::class, 'show']);

    // Roles y Accesos (Control de Accesos)
    Route::get('access-control/roles', [RoleController::class, 'index']);
    Route::get('access-control/roles/{id_rol}/modulos-flat', [AccessController::class, 'modulesFlat']);
    Route::get('access-control/roles/{id_rol}/modulos-tree', [AccessController::class, 'modulesTree']);
    Route::get('access-control/roles/{id_rol}/modulos/{id_root}/tree', [AccessController::class, 'modulesTreeByRoot']);
    Route::get('access-control/roles/{id_rol}/modulos-levels', [AccessController::class, 'modulesByLevels']);
    Route::get('access-control/roles/{id_rol}/modulos-level-tree', [AccessController::class, 'modulesLevelTree']);
    Route::get('access-control/roles/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'assigned']);
    
    Route::post('access-control/roles', [RoleController::class, 'store']);
    Route::post('access-control/roles/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'store']);
    Route::match(['put','post'], 'access-control/roles/{id_rol}/modulos', [RoleModuleController::class,'sync']);
    
    Route::put('access-control/roles/{id_rol}/status', [RoleController::class, 'updateStatus']);
    Route::put('access-control/roles/{id_rol}', [RoleController::class, 'updateName']);
    Route::put('access-control/roles/{id_rol}/modulos/{id_root}', [AccessController::class, 'syncRoleModulesByRoot']);
    Route::delete('access-control/roles/{id_rol}', [RoleController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| 6. RUTAS IAM (Identidad y Accesos)
|--------------------------------------------------------------------------
*/
Route::prefix('iam')->group(function () {
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('roles/{id_rol}/modulos-flat', [AccessController::class, 'modulesFlat']);
    Route::get('roles/{id_rol}/modulos-tree', [AccessController::class, 'modulesTree']);
    Route::get('roles/{id_rol}/modulos/{id_root}/tree', [AccessController::class, 'modulesTreeByRoot']);
    Route::get('modules/{id_modulo}/privilegios', [RolePrivilegeController::class, 'catalogByModule']);
    Route::get('roles/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'assigned']);
    
    Route::post('roles', [RoleController::class, 'store']);
    Route::post('roles/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'store']);
    Route::match(['put','post'],'roles/{id_rol}/modulos', [RoleModuleController::class,'sync']);
    
    Route::put('roles/{id_rol}/status', [RoleController::class, 'updateStatus']);
    Route::put('roles/{id_rol}', [RoleController::class, 'updateName']);
    Route::put('roles/{id_rol}/modulos/{id_root}', [AccessController::class, 'syncRoleModulesByRoot']);
    Route::delete('roles/{id_rol}', [RoleController::class, 'destroy']);

    // Gestión de Usuarios y Accesos Directos
    Route::get('user-access/search', [UserAccessController::class, 'searchUsuario']);
    Route::get('user-access/{id}/list', [UserAccessController::class, 'index']);
    Route::get('user-access/reports', [AcademicCatalogController::class, 'getAllAccessReports']);
    Route::get('role-assignment/search', [UserRoleController::class, 'search']);
    Route::get('role-assignment/{id_persona}/roles', [UserRoleController::class, 'assignedToUser']);
    
    Route::post('user-access/{id}/save', [UserAccessController::class, 'store']);
    Route::post('role-assignment/{id_persona}/roles', [UserRoleController::class, 'saveForUser']);
    Route::delete('user-access/{id}/delete/{aid}', [UserAccessController::class, 'destroy']);
});