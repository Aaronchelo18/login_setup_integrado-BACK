<?php

namespace App\Http\Controllers\PersonVirtual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Data\PersonVirtual\PersonVirtualData;
use App\Models\PersonVirtual\PersonVirtual;
use App\Traits\ApiResponse;

class PersonVirtualController extends Controller
{
    //
    use ApiResponse;
     /**
     * Lista todos los correos virtuales
     */
     public function index()
    {
        $response = PersonVirtualData::getAll();

        return $response['success']
            ? $this->ok($response['data'], 'Lista de correos virtuales obtenida')
            : $this->error($response['message'], 500);
    }
    /**
     * Muestra un correo virtual por ID
     */

    public function show($id)
    {
        $response = PersonVirtualData::getById($id);

        return $response['success']
            ? $this->ok($response['data'], 'Correo virtual encontrado')
            : $this->error($response['message'], 404);
    }
    /**
     * Crea un nuevo correo virtual
     */

    public function store(Request $request)
    {
        $response = PersonVirtualData::create($request->all());

        return !$response['success']
            ? (isset($response['errors']) 
                ? $this->information($response['message'], 422, $response['errors']) 
                : $this->error($response['message'], 500))
            : $this->ok($response['data'], 'Correo virtual creado exitosamente', 201);
    }
    /**
     * Actualiza un correo virtual
     */

    public function update(Request $request, PersonVirtual $persona_virtual)
    {
        $response = PersonVirtualData::update($persona_virtual, $request->all());

        return !$response['success']
            ? (isset($response['errors']) 
                ? $this->information($response['message'], 422, $response['errors']) 
                : $this->error($response['message'], 500))
            : $this->ok($response['data'], 'Correo virtual actualizado');
    }
    /**
     * Elimina un correo virtual
     */

    public function destroy(PersonVirtual $persona_virtual)
    {
        $response = PersonVirtualData::delete($persona_virtual);

        return $response['success']
            ? $this->ok(null, 'Correo virtual eliminado', 204)
            : $this->error($response['message'], 500);
    }
}
