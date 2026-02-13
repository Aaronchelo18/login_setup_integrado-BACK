<?php

namespace App\Http\Controllers\WorkerPerson;

use App\Http\Controllers\Controller;
use App\Http\Data\WorkerPerson\WorkerPersonData;
use App\Models\WorkerPerson\WorkerPerson;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;    

class WorkerPersonController extends Controller
{
    //
    use ApiResponse;

      // Listar todos los trabajadores
    public function index()
    {
        $response = WorkerPersonData::getAll();
        return $response['success']
            ? $this->ok($response['data'], 'Trabajadores listados correctamente')
            : $this->error($response['message'], 500);
    }

    // Listar trabajador por ID
    public function show($id)
    {
        $response = WorkerPersonData::getById($id);
        return $response['success']
            ? $this->ok($response['data'], 'Trabajador encontrado')
            : $this->error($response['message'], 404);
    }

    // Crear trabajador
    public function store(Request $request)
    {
        $response = WorkerPersonData::create($request->all());
        return !$response['success']
            ? (isset($response['errors'])
                ? $this->information($response['message'], 422, $response['errors'])
                : $this->error($response['message'], 500))
            : $this->ok($response['data'], 'Trabajador creado correctamente', 201);
    }

    // Actualizar trabajador
    public function update(Request $request, WorkerPerson $trabajador)
    {
        $response = WorkerPersonData::update($trabajador, $request->all());
        return !$response['success']
            ? (isset($response['errors'])
                ? $this->information($response['message'], 422, $response['errors'])
                : $this->error($response['message'], 500))
            : $this->ok($response['data'], 'Trabajador actualizado');
    }

    // Eliminar trabajador
    public function destroy(WorkerPerson $trabajador)
    {
        $response = WorkerPersonData::delete($trabajador);

        return $response['success']
            ? $this->ok(['deleted_id' => $trabajador->id_trabajador], 'Trabajador eliminado correctamente', 200)
            : $this->error($response['message'], 500);
    }
}
