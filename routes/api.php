<?php

use Illuminate\Support\Facades\Route;

/** ===== CONTROLADORES ===== */
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Person\PersonController;
use App\Http\Controllers\PersonVirtual\PersonVirtualController;
use App\Http\Controllers\StudenPerson\StudentPersonController;
use App\Http\Controllers\WorkerPerson\WorkerPersonController;
use App\Http\Controllers\Utility\UserInfoController;
use App\Http\Controllers\Modules\ModuloController;
use App\Http\Controllers\Privileges\PrivilegioController;
use App\Http\Controllers\Faculty\FacultadController;
use App\Http\Controllers\Campus\CampusController;
use App\Http\Controllers\Program\ProgramaEstudioController;
use App\Http\Controllers\Academic\AcademicCatalogController;
use App\Http\Controllers\Config\AccessController;
use App\Http\Controllers\Role\RoleModuleController;
use App\Http\Controllers\Config\RolePrivilegeController;
use App\Http\Controllers\Config\UserRoleController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Users\UserAccessController;

/*
|--------------------------------------------------------------------------
| 1. AUTENTICACIÓN
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::get('google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);
    Route::post('set-password', [AuthController::class, 'setPassword']);
    Route::post('login-password', [AuthController::class, 'loginWithPassword']);
    Route::post('forgot-password', [AuthController::class, 'reenviarLinkCrearClave']);
    Route::middleware('auth:api')->get('me', [AuthController::class, 'me']);
});

/*
|--------------------------------------------------------------------------
| 2. GESTIÓN GLOBAL DE PERSONAS (Config)
|--------------------------------------------------------------------------
*/
Route::prefix('global/config')->group(function () {
    // Personas
    Route::get('person', [PersonController::class, 'index']);
    Route::get('person/{id}', [PersonController::class, 'show']);
    Route::post('person', [PersonController::class, 'store']);
    Route::put('person/{person}', [PersonController::class, 'update']);
    Route::patch('person/{person}', [PersonController::class, 'update']);
    Route::delete('person/{person}', [PersonController::class, 'destroy']);

    // Persona Virtual
    Route::get('persona_virtual', [PersonVirtualController::class, 'index']);
    Route::get('persona_virtual/{id}', [PersonVirtualController::class, 'show']);
    Route::post('persona_virtual', [PersonVirtualController::class, 'store']);
    Route::put('persona_virtual/{persona_virtual}', [PersonVirtualController::class, 'update']);
    Route::patch('persona_virtual/{persona_virtual}', [PersonVirtualController::class, 'update']);
    Route::delete('persona_virtual/{persona_virtual}', [PersonVirtualController::class, 'destroy']);

    // Student
    Route::get('student-persons', [StudentPersonController::class, 'index']);
    Route::get('student-persons/{student}', [StudentPersonController::class, 'show']);
    Route::post('student-persons', [StudentPersonController::class, 'store']);
    Route::put('student-persons/{student}', [StudentPersonController::class, 'update']);
    Route::patch('student-persons/{student}', [StudentPersonController::class, 'update']);
    Route::delete('student-persons/{student}', [StudentPersonController::class, 'destroy']);

    // Worker
    Route::get('worker-person', [WorkerPersonController::class, 'index']);
    Route::get('worker-person/{trabajador}', [WorkerPersonController::class, 'show']);
    Route::post('worker-person', [WorkerPersonController::class, 'store']);
    Route::put('worker-person/{trabajador}', [WorkerPersonController::class, 'update']);
    Route::patch('worker-person/{trabajador}', [WorkerPersonController::class, 'update']);
    Route::delete('worker-person/{trabajador}', [WorkerPersonController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| 3. APPLICATION MANAGEMENT (Modules & Access Control)
|--------------------------------------------------------------------------
*/
Route::prefix('application-management')->group(function () {

    // --- MÓDULOS ---
    Route::get('modules/admin-list', [ModuloController::class, 'listAllAdmin']);
    Route::get('modules/arbol', [ModuloController::class, 'getTree']);
    Route::get('modules/padres', [ModuloController::class, 'getParentModules']);
    Route::get('modules/opciones', [ModuloController::class, 'getOptions']);
    Route::get('modules/jerarquia/tree', [ModuloController::class, 'getHierarchyTree']);
    Route::get('modules', [ModuloController::class, 'index']);
    Route::get('modules/{modulo}', [ModuloController::class, 'show']);
    
    Route::post('modules', [ModuloController::class, 'storeAdmin']);
    Route::post('modules/jerarquia', [ModuloController::class, 'createInHierarchy']);
    Route::post('modules/store-basic', [ModuloController::class, 'store']); 

    Route::put('modules/{modulo}', [ModuloController::class, 'updateAdmin']);
    Route::put('modules/jerarquia/{id}', [ModuloController::class, 'updateHierarchyNode']);
    
    Route::patch('modules/{modulo}', [ModuloController::class, 'updateAdmin']);
    Route::patch('modules/jerarquia/{id}', [ModuloController::class, 'patchHierarchyNode']);
    
    Route::delete('modules/{modulo}', [ModuloController::class, 'destroyAdmin']);
    Route::delete('modules/jerarquia/{id}', [ModuloController::class, 'deleteHierarchyNode']);

    // --- PRIVILEGIOS (Catálogos y Asignación) ---
    Route::get('modules/{id_modulo}/privilegios', [PrivilegioController::class, 'index']);
    Route::get('modules/{id_modulo}/privilegios/catalog', [PrivilegioController::class, 'catalog']);
    Route::get('modules/{id_modulo}/privilegios/matrix', [PrivilegioController::class, 'matrix']);
    
    Route::post('modules/{id_modulo}/privilegios', [PrivilegioController::class, 'store']);
    Route::post('modules/{id_modulo}/privilegios/bulk', [PrivilegioController::class, 'bulkUpsert']);
    Route::post('modules/{id_modulo}/privilegios/catalog-create', [PrivilegioController::class, 'createCatalog']);

    Route::put('modules/privilegios/{id_privilegio}', [PrivilegioController::class, 'update']);
    Route::patch('modules/privilegios/{id_privilegio}', [PrivilegioController::class, 'patch']);
    Route::delete('modules/privilegios/{id_privilegio}', [PrivilegioController::class, 'destroy']);

    // --- ACCESS CONTROL (Roles) ---
    // Sincronizado con RoleService.ts
    Route::get('access-control', [RoleController::class, 'index']);
    Route::get('access-control/role/{id_rol}/modulos-tree', [AccessController::class, 'modulesTree']);
    Route::get('access-control/role/{id_rol}/root/{id_root}', [AccessController::class, 'modulesTreeByRoot']);
    Route::get('access-control/role/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'assigned']);
    
    Route::post('access-control', [RoleController::class, 'store']);
    Route::post('access-control/role/{id_rol}/modulos/{id_modulo}/privilegios', [RolePrivilegeController::class, 'store']);
    
    Route::put('access-control/role/{id_rol}', [RoleController::class, 'updateName']);
    Route::put('access-control/role/{id_rol}/status', [RoleController::class, 'updateStatus']);
    Route::put('access-control/role/{id_rol}/root/{id_root}/sync', [AccessController::class, 'syncRoleModulesByRoot']);
    
    Route::delete('access-control/role/{id_rol}', [RoleController::class, 'destroy']);

    // Catálogos Académicos
    Route::get('facultades', [FacultadController::class, 'index']);
    Route::get('campus', [CampusController::class, 'index']);
    Route::get('programas', [ProgramaEstudioController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| 4. IAM (Identity & Access Management)
|--------------------------------------------------------------------------
*/
Route::prefix('iam')->group(function () {
    // Reportes para Dashboard
    Route::get('user-access/reports', [AcademicCatalogController::class, 'getAllAccessReports']);
    Route::get('roles', [RoleController::class, 'index']); 

    // GESTIÓN DE USUARIOS (Usando tu UserRoleController con efeso.usuario)
    // Cambiamos UserInfoController por UserRoleController que es el que tiene la data real
    Route::get('role-assignment/users', [UserRoleController::class, 'index']); 
    
    // Si el front usa el buscador, apuntamos al método search de tu controlador
    Route::get('role-assignment/search', [UserRoleController::class, 'search']); 

    Route::get('role-assignment/{id_persona}/roles', [UserRoleController::class, 'assignedToUser']);
    Route::post('role-assignment/{id_persona}/roles', [UserRoleController::class, 'saveForUser']);

    // Accesos a Usuarios (Otras rutas)
    Route::get('user-access/search', [UserAccessController::class, 'searchUsuario']);
    Route::get('user-access/{id}/list', [UserAccessController::class, 'index']);
    Route::post('user-access/{id}/save', [UserAccessController::class, 'store']);
    Route::delete('user-access/{id}/delete/{aid}', [UserAccessController::class, 'destroy']);
});