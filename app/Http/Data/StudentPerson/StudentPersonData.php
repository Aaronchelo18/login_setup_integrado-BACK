<?php

namespace App\Http\Data\StudentPerson;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\StudenPerson\StudentPerson;
use Throwable;

class StudentPersonData
{
    //
    public static function getAll(): array
    {
        try {
            $data = DB::select("SELECT id_alumno, codigo, estado FROM global_config.persona_alumno ORDER BY id_alumno DESC");
            
            // Convertir a números
            $data = array_map(function($item) {
                $item->id_alumno = (int)$item->id_alumno;
                $item->codigo = (int)$item->codigo; // ← Código como número
                $item->estado = (int)$item->estado;
                return $item;
            }, $data);
            
            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al obtener alumnos: ' . $e->getMessage()];
        }
    }

    public static function getById(int $id): array
    {
        try {
            $alumno = DB::selectOne("SELECT * FROM global_config.persona_alumno WHERE id_alumno = ?", [$id]);
            if (!$alumno) return ['success' => false, 'message' => 'Alumno no encontrado'];
            
            // Convertir a números
            $alumno->id_alumno = (int)$alumno->id_alumno;
            $alumno->codigo = (int)$alumno->codigo; // ← Código como número
            $alumno->estado = (int)$alumno->estado;
            
            return ['success' => true, 'data' => $alumno];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al buscar alumno: ' . $e->getMessage()];
        }
    }

    public static function create(array $requestData): array
    {
        $validator = Validator::make($requestData, [
            'id_persona' => 'required|integer|exists:persona,id_persona|unique:persona_alumno,id_alumno',
            'codigo' => 'required|integer|unique:persona_alumno,codigo', // ← Validar como integer
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

            DB::table('global_config.persona_alumno')->insert([
                'id_alumno' => (int)$validated['id_persona'],
                'codigo' => (int)$validated['codigo'], // ← Insertar como número
                'estado' => 1
            ]);

            return ['success' => true, 'data' => [
                'id_alumno' => (int)$validated['id_persona'],
                'codigo' => (int)$validated['codigo'], // ← Retornar como número
                'estado' => 1
            ]];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al crear alumno: ' . $e->getMessage()];
        }
    }

    public static function update(StudentPerson $student, array $data): array
    {
        $validator = Validator::make($data, [
            'codigo' => 'sometimes|integer|unique:persona_alumno,codigo,' . $student->id_alumno . ',id_alumno', // ← Validar como integer
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
            
            // Convertir a números
            if (isset($validated['codigo'])) {
                $validated['codigo'] = (int)$validated['codigo'];
            }
            if (isset($validated['estado'])) {
                $validated['estado'] = (int)$validated['estado'];
            }
            
            $student->fill($validated)->save();
            
            // Obtener datos frescos y convertir a números
            $freshData = $student->fresh();
            $freshData->id_alumno = (int)$freshData->id_alumno;
            $freshData->codigo = (int)$freshData->codigo; // ← Código como número
            $freshData->estado = (int)$freshData->estado;
            
            return ['success' => true, 'data' => $freshData];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al actualizar alumno: ' . $e->getMessage()];
        }
    }

    public static function delete(StudentPerson $student): array
    {
        try {
            $id = $student->id_alumno;
            $student->delete();
            
            return [
                'success' => true,
                'message' => 'Alumno eliminado correctamente',
                'deleted_id' => (int)$id // ← ID como número
            ];
        } catch (Throwable $e) {
            return [
                'success' => false, 
                'message' => 'Error al eliminar alumno: ' . $e->getMessage()
            ];
        }
    }
}
