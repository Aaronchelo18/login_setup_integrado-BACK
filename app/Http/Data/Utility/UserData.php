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
            // Recibimos el parámetro 'q' de la URL para filtrar
            $q = request()->query('q');

            // Buscamos en la tabla de personas para que aparezca JOZEF (ID 1465, etc.)
            // Ajusta 'persona' al nombre real de tu tabla
            $query = DB::table('persona')
                ->select(
                    'id_persona', 
                    'nombre', 
                    'paterno', 
                    'materno', 
                    DB::raw("CONCAT(nombre, ' ', paterno, ' ', materno) as display_name")
                );

            if (!empty($q)) {
                $query->where(function($sql) use ($q) {
                    $sql->where('nombre', 'like', "%$q%")
                        ->orWhere('paterno', 'like', "%$q%")
                        ->orWhere('materno', 'like', "%$q%");
                });
            }

            $users = $query->orderBy('paterno', 'asc')->paginate(request()->query('per_page', 10));

            return [
                'success' => true, 
                'data' => $users->items(),
                'meta' => [
                    'total' => $users->total(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                ]
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
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