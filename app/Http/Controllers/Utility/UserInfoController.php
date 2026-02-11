<?php

namespace App\Http\Controllers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Data\Utility\UserData;
use App\Models\User;
use App\Traits\ApiResponse; 
use Illuminate\Http\Request;

class UserInfoController extends Controller
{
    use ApiResponse; 

    public function index()
    {
        $response = UserData::getAllUsers();

        return $response['success']
            ? $this->ok($response['data'], 'Usuarios obtenidos exitosamente')
            : $this->error($response['message'], 500);
    }


    public function store(Request $request)
    {
        $response = UserData::createUser($request->all());

        if (!$response['success']) {
            if (isset($response['errors'])) {
                return $this->information($response['message'], 422, $response['errors']);
            }
            return $this->error($response['message'], 500);
        }

        return $this->ok($response['data'], 'Usuario creado exitosamente', 201);
    }

   
    public function show(User $user)
    {
      return $this->ok($user, 'Usuario obtenido exitosamente');
    }

    
    public function update(Request $request, User $user)
    {
        $response = UserData::updateUser($user, $request->all());

        if (!$response['success']) {
            if (isset($response['errors'])) {
                return $this->information($response['message'], 422, $response['errors']);
            }
            return $this->error($response['message'], 500);
        }

        return $this->ok($response['data'], 'Usuario actualizado exitosamente');
    }

    
    public function destroy(User $user)
    {
        $response = UserData::deleteUser($user);

        return $response['success']
            ? $this->ok(null, 'Usuario eliminado exitosamente', 204) 
            : $this->error($response['message'], 500);
    }
}