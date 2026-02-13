<?php

namespace App\Http\Controllers\Person;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Data\Person\PersonData;
use App\Models\Person\Person;
use App\Traits\ApiResponse;

class PersonController extends Controller
{
    //
    use ApiResponse;

// listar personas
    public function index()
    {
        $response = PersonData::getAllPerson();
        return $response['success']
            ? $this->ok($response['data'], 'Personas listadas correctamente')
            : $this->error($response['message'], 500);
    }

    // listar persona por ID
    public function show($id)
    {
        $response = PersonData::getById($id);
        return $response['success']
            ? $this->ok($response['data'], 'Persona encontrada')
            : $this->error($response['message'], 404);
    }

    // crear persona
    public function store(Request $request)
    {
        $response = PersonData::create($request->all());
        return !$response['success']
            ? (isset($response['errors']) ? $this->information($response['message'], 422, $response['errors']) : $this->error($response['message'], 500))
            : $this->ok($response['data'], 'Persona creada correctamente', 201);
    }

    // actualizar persona
    public function update(Request $request, Person $person)
    {
        $response = PersonData::update($person, $request->all());
        return !$response['success']
            ? (isset($response['errors']) ? $this->information($response['message'], 422, $response['errors']) : $this->error($response['message'], 500))
            : $this->ok($response['data'], 'Persona actualizada');
    }

    // eliminar persona
   public function destroy(Person $person)
    {
        $response = PersonData::delete($person);

        return $response['success']
            ? $this->ok(['deleted_id' => $person->id_persona], 'Persona eliminada correctamente', 200)
            : $this->error($response['message'], 500);
    }
}
