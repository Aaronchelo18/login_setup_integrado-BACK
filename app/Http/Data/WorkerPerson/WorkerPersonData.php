<?php

namespace App\Http\Data\WorkerPerson;

use App\Models\WorkerPerson\WorkerPerson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class WorkerPersonData
{
    //
    /**
     * Obtener todos los trabajadores
     */
    public static function getAll(): array
    {
        try {
            $data = DB::select("
                SELECT id_trabajador, cargo, estado
                FROM global_config.persona_trabajador
                ORDER BY id_trabajador DESC
            ");

            $data = array_map(function ($item) {
                $item->id_trabajador = (int)$item->id_trabajador;
                $item->estado = (int)$item->estado;
                return $item;
            }, $data);

            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al obtener trabajadores: ' . $e->getMessage()];
        }
    }

    /**
     * Obtener trabajador por ID
     */
    public static function getById(int $id): array
    {
        try {
            $trabajador = DB::selectOne("
                SELECT * 
                FROM global_config.persona_trabajador 
                WHERE id_trabajador = ?
            ", [$id]);

            if (!$trabajador) {
                return ['success' => false, 'message' => 'Trabajador no encontrado'];
            }

            $trabajador->id_trabajador = (int)$trabajador->id_trabajador;
            $trabajador->estado = (int)$trabajador->estado;

            return ['success' => true, 'data' => $trabajador];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al buscar trabajador: ' . $e->getMessage()];
        }
    }

    /**
     * Crear trabajador
     */
    public static function create(array $requestData): array
    {
        // Validar datos de entrada
        // Validar datos de entrada
        $validator = Validator::make($requestData, [
            'id_trabajador' => [
                'required',
                'integer',
                function ($attr, $val, $fail) {
                    $exists = DB::selectOne("SELECT 1 FROM global_config.persona WHERE id_persona = ?", [$val]);
                    if (!$exists) {
                        $fail("La persona con id $val no existe.");
                    }
                },
                function ($attr, $val, $fail) {
                    $exists = DB::selectOne("SELECT 1 FROM global_config.persona_trabajador WHERE id_trabajador = ?", [$val]);
                    if ($exists) {
                        $fail("El trabajador con id $val ya existe.");
                    }
                }
            ],
            'cargo' => 'required|string|max:255',
            // 🔹 Estado ahora es opcional
            'estado' => 'nullable|integer|in:0,1'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors()
            ];
        }

        try {
            $validated = $validator->validated();

            // 🔹 Si no se envía estado, se pone en 1 por defecto
            $estado = isset($validated['estado']) ? (int)$validated['estado'] : 1;

            DB::table('global_config.persona_trabajador')->insert([
                'id_trabajador' => (int)$validated['id_trabajador'],
                'cargo' => $validated['cargo'],
                'estado' => $estado
            ]);

            return [
                'success' => true,
                'data' => [
                    'id_trabajador' => (int)$validated['id_trabajador'],
                    'cargo' => $validated['cargo'],
                    'estado' => $estado
                ]
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al crear trabajador: ' . $e->getMessage()];
        }
    }

    /**
     * Actualizar trabajador
     */
    public static function update(WorkerPerson $trabajador, array $data): array
    {
        $validator = Validator::make($data, [
            'cargo' => 'sometimes|string|max:255',
            'estado' => 'sometimes|integer|in:0,1'
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors()
            ];
        }

        try {
            $validated = $validator->validated();

            if (isset($validated['estado'])) {
                $validated['estado'] = (int)$validated['estado'];
            }

            $trabajador->fill($validated)->save();

            $freshData = $trabajador->fresh();
            $freshData->id_trabajador = (int)$freshData->id_trabajador;
            $freshData->estado = (int)$freshData->estado;

            return ['success' => true, 'data' => $freshData];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al actualizar trabajador: ' . $e->getMessage()];
        }
    }

    /**
     * Eliminar trabajador
     */
    public static function delete(WorkerPerson $trabajador): array
    {
        try {
            $id = $trabajador->id_trabajador;
            $trabajador->delete();

            return [
                'success' => true,
                'message' => 'Trabajador eliminado correctamente',
                'deleted_id' => (int)$id
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar trabajador: ' . $e->getMessage()
            ];
        }
    }
}
