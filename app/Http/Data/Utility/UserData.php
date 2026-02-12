<?php

namespace App\Http\Data\Utility;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UserData
{
  
    public static function getAllUsers(): array
    {
        try {
            $query = "SELECT id, name, email, email_verified_at, created_at, updated_at FROM users ORDER BY created_at DESC";
            $users = DB::select($query);
            return ['success' => true, 'data' => $users];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al obtener los usuarios: ' . $e->getMessage()];
        }
    }

  
    public static function createUser(array $requestData): array
    {
        $validator = Validator::make($requestData, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => 'Datos de validación incorrectos.', 'errors' => $validator->errors()];
        }

        try {
            $user = User::create($validator->validated());
            return ['success' => true, 'data' => $user];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al crear el usuario: ' . $e->getMessage()];
        }
    }

   
    public static function updateUser(User $user, array $requestData): array
    {
        $validator = Validator::make($requestData, [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => 'Datos de validación incorrectos.', 'errors' => $validator->errors()];
        }

        try {
            $user->fill($validator->validated());
            $user->save();
            
            return ['success' => true, 'data' => $user->fresh()];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al actualizar el usuario: ' . $e->getMessage()];
        }
    }

   
    public static function deleteUser(User $user): array
    {
        try {
            $user->delete();
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al eliminar el usuario: ' . $e->getMessage()];
        }
    }
}