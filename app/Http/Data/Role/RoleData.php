<?php

namespace App\Http\Data\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;
use App\Models\Role\Role;

class RoleData
{
    public static function getAll(): array
    {
        try {
            $roles = DB::select("SELECT * FROM rol ORDER BY id_rol ASC");

            return [
                'success' => true,
                'data'    => $roles
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener roles: ' . $e->getMessage()
            ];
        }
    }

    public static function create(array $requestData): array
    {
        $validator = Validator::make($requestData, [
            'nombre' => 'required|string|max:64',
            'estado' => 'required|in:0,1', // varchar(1): solo '0' o '1'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Datos de validación incorrectos.',
                'errors'  => $validator->errors(),
            ];
        }

        try {
            $role = Role::create($validator->validated());
            return ['success' => true, 'data' => $role];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al crear el rol: ' . $e->getMessage(),
            ];
        }
    }

    
}
