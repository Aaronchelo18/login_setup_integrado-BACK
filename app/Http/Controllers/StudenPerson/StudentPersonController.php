<?php

namespace App\Http\Controllers\StudenPerson;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Data\StudentPerson\StudentPersonData;
use App\Models\StudenPerson\StudentPerson;

use App\Traits\ApiResponse;

class StudentPersonController extends Controller
{
    //Api Responser Trait
    use ApiResponse;

     public function index()
    {
        $response = StudentPersonData::getAll();
        return $response['success']
            ? $this->ok($response['data'], 'Alumnos listados correctamente')
            : $this->error($response['message'], 500);
    }

    public function show($id)
    {
        $response = StudentPersonData::getById($id);
        return $response['success']
            ? $this->ok($response['data'], 'Alumno encontrado')
            : $this->error($response['message'], 404);
    }

    public function store(Request $request)
    {
        $response = StudentPersonData::create($request->all());

        return !$response['success']
            ? (isset($response['errors'])
                ? $this->information($response['message'], 422, $response['errors'])
                : $this->error($response['message'], 500))
            : $this->ok($response['data'], 'Alumno creado correctamente', 201);
    }

    public function update(Request $request, StudentPerson  $student)
    {
        $response = StudentPersonData::update($student, $request->all());

        return !$response['success']
            ? (isset($response['errors'])
                ? $this->information($response['message'], 422, $response['errors'])
                : $this->error($response['message'], 500))
            : $this->ok($response['data'], 'Alumno actualizado');
    }

    public function destroy(StudentPerson $student)
    {
        $response = StudentPersonData::delete($student);

        return $response['success']
            ? $this->ok(['deleted_id' => $student->id_alumno], 'Alumno eliminado correctamente', 200)
            : $this->error($response['message'], 500);
    }
}
