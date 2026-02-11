<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Utility\UserInfoController;
use App\Http\Controllers\Modules\ModuloController;
use App\Http\Controllers\Privileges\PrivilegioController;
use App\Http\Controllers\Faculty\FacultadController;
use App\Http\Controllers\Campus\CampusController;
use App\Http\Controllers\Program\ProgramaEstudioController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('managemt')->group(function () {
    Route::get('users', [UserInfoController::class, 'index']);
    Route::post('users', [UserInfoController::class, 'store']);
    Route::get('users/{user}', [UserInfoController::class, 'show']);
    Route::put('users/{user}', [UserInfoController::class, 'update']);
    Route::patch('users/{user}', [UserInfoController::class, 'update']);
    Route::delete('users/{user}', [UserInfoController::class, 'destroy']);
});


Route::prefix('config/setup')->group(function () {
    Route::get('modulos/arbol', [ModuloController::class, 'getTree']);
    Route::get('modulos/padres', [ModuloController::class, 'getParentModules']);
    Route::get('modulos/opciones', [ModuloController::class, 'getOptions']);
    //Route::get('modulos/jerarquia', [ModuloController::class, 'getHierarchy']); 
    // Árbol jerárquico (anidado con children) — NUEVO


    Route::get('modulos/jerarquia/tree', [ModuloController::class, 'getHierarchyTree']);
    Route::post('modulos/jerarquia', [ModuloController::class, 'createInHierarchy']);
    Route::put('modulos/jerarquia/{id}', [ModuloController::class, 'updateHierarchyNode']);
    Route::patch('modulos/jerarquia/{id}', [ModuloController::class, 'patchHierarchyNode']);
    Route::delete('modulos/jerarquia/{id}', [ModuloController::class, 'deleteHierarchyNode']);


    Route::get('modulos', [ModuloController::class, 'index']);
    Route::post('modulos', [ModuloController::class, 'store']);
    Route::get('modulos/{modulo}', [ModuloController::class, 'show']);
    Route::put('modulos/{modulo}', [ModuloController::class, 'update']);
    Route::patch('modulos/{modulo}', [ModuloController::class, 'update']);
    Route::delete('modulos/{modulo}', [ModuloController::class, 'destroy']);
    Route::get('modules/{id}/access', [ModuloController::class, 'getAccessByModule']);

    // 🔐 Privilegios por módulo (CRUD + bulk matriz)

    Route::get('modulos/{id_modulo}/privilegios', [PrivilegioController::class, 'index']);
    Route::post('modulos/{id_modulo}/privilegios', [PrivilegioController::class, 'store']);
    Route::put('modulos/privilegios/{id_privilegio}', [PrivilegioController::class, 'update']);
    Route::patch('modulos/privilegios/{id_privilegio}', [PrivilegioController::class, 'patch']);
    Route::delete('modulos/privilegios/{id_privilegio}', [PrivilegioController::class, 'destroy']);
    Route::post('modulos/{id_modulo}/privilegios/bulk', [PrivilegioController::class, 'bulkUpsert']);

    // 1) Por módulo (matriz)
    Route::get('modulos/{id_modulo}/privilegios/matrix', [PrivilegioController::class, 'matrix']);
    Route::post('modulos/{id_modulo}/privilegios/matrix', [PrivilegioController::class, 'assignMatrix']);

    // 2) Por árbol (descendientes)
    Route::get('modulos/{id_parent}/privilegios/tree-matrix', [PrivilegioController::class, 'treeMatrix']);
    Route::post('modulos/{id_parent}/privilegios/tree-matrix', [PrivilegioController::class, 'assignTreeMatrix']);

    // Catálogo “raw” por módulo (crear filas base sin duplicar)
    // Catálogo “raw” por módulo
    Route::get(
        'modulos/{id_modulo}/privilegios/catalog',
        [PrivilegioController::class, 'catalog']
    );

    // POST para crear catálogo (sin duplicar)
    Route::post(
        'modulos/{id_modulo}/privilegios/catalog-create',
        [PrivilegioController::class, 'createCatalog']
    );

    // POST para crear privilegios individuales (si quieres mantenerlo)
    Route::post(
        'modulos/{id_modulo}/privilegios/catalog',
        [PrivilegioController::class, 'createForModule']
    );

    // Eliminar fila
    Route::delete(
        'modulos/privilegios/row/{id_privilegio}',
        [PrivilegioController::class, 'destroyRow']
    );


    // Facultades 
    Route::get('facultades', [FacultadController::class, 'index']);
    Route::get('facultades/{id}', [FacultadController::class, 'show']);

    // Campus
    Route::get('campus', [CampusController::class, 'index']);
    Route::get('campus/{id}', [CampusController::class, 'show']);

    // Programas
    Route::get('programas', [ProgramaEstudioController::class, 'index']);
    Route::get('programas/{id}', [ProgramaEstudioController::class, 'show']);
});     