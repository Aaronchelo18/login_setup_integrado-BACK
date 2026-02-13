<?php
namespace App\Http\Data\Person;

use App\Models\Person\Person;
use App\Models\PersonVirtual\PersonVirtual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Throwable;

class PersonData
{
     /**
     * Obtener todas las personas
     */
    public static function getAllPerson(): array
    {
        try {
            $data = DB::select("SELECT id_persona, nombre, paterno, materno FROM global_config.persona ORDER BY id_persona DESC");
            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al obtener personas: ' . $e->getMessage()];
        }
    }

    /**
     * Obtener una persona por ID
     */
    public static function getById(int $id): array
    {
        try {
            $data = DB::selectOne("SELECT * FROM global_config.persona WHERE id_persona = ?", [$id]);

            if (!$data) return ['success' => false, 'message' => 'Persona no encontrada'];

            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al buscar persona: ' . $e->getMessage()];
        }
    }

    /**
     * Crear una nueva persona (y opcionalmente correo principal y alumno)
     */
    public static function create(array $requestData): array
    {
        // Validaciones
        $validator = Validator::make($requestData, [
            'nombre'   => 'required|string|max:64',
            'paterno'  => 'required|string|max:64',
            'materno'  => 'required|string|max:64',
            'correo'   => 'nullable|email|unique:persona_virtual,correo',
            'codigo'   => 'nullable|string|max:50|unique:persona_alumno,codigo',
            'estado'   => 'nullable|in:0,1'
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => 'Datos inválidos.', 'errors' => $validator->errors()];
        }

        try {
            DB::beginTransaction();
            $validated = $validator->validated();

            // Crear persona
            $person = Person::create([
                'nombre'  => $validated['nombre'],
                'paterno' => $validated['paterno'],
                'materno' => $validated['materno']
            ]);

            // Si se envía un correo, registrar en persona_virtual y usuario
            if (!empty($validated['correo'])) {
                // Desmarcar correo principal anterior si existiera
                
            PersonVirtual::where('id_persona', $person->id_persona)
                    ->where('es_principal', '1')
                    ->update(['es_principal' => '0']);

                // Crear nuevo correo principal
                PersonVirtual::create([
                    'id_persona'     => $person->id_persona,
                    'correo'         => $validated['correo'],
                    'estado'         => '0',
                    'verificado'     => '0',
                    'es_principal'   => '1',
                    'creado_en'      => now(),
                    'ultima_sesion'  => now(),
                ]);

                // Registrar usuario
                DB::table('efeso.usuario')->insert([
                    'id_persona' => $person->id_persona,
                    'correo'     => $validated['correo']
                ]);
            }

            // Si se incluye código y estado, crear alumno
            if (!empty($validated['codigo'])) {
    DB::table('global_config.persona_alumno')->insert([
        'id_alumno' => $person->id_persona,
        'codigo'    => $validated['codigo'],
        'estado'    => $validated['estado'] ?? '1'  // Valor por defecto
    ]);
}


            DB::commit();
            return ['success' => true, 'data' => $person];
        } catch (Throwable $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Error al crear persona: ' . $e->getMessage()];
        }
    }

    /**
     * Actualizar una persona
     */
   public static function update(Person $person, array $requestData): array
{
    // Validar los campos de la tabla persona
    $validator = Validator::make($requestData, [
        'nombre'  => 'sometimes|required|string|max:64',
        'paterno' => 'sometimes|required|string|max:64',
        'materno' => 'sometimes|required|string|max:64',
        'codigo'  => 'sometimes|string|max:50|unique:persona_alumno,codigo,' . $person->id_persona . ',id_alumno'
    ]);

    if ($validator->fails()) {
        return [
            'success' => false,
            'message' => 'Datos inválidos.',
            'errors'  => $validator->errors()
        ];
    }

    try {
        $validated = $validator->validated();

        if (empty($validated)) {
            return ['success' => false, 'message' => 'No se proporcionaron campos válidos para actualizar.', 'data' => []];
        }

        DB::beginTransaction();

        // Actualizar tabla persona solo si hay campos de persona
        $personaFields = array_intersect_key($validated, array_flip(['nombre', 'paterno', 'materno']));
        if (!empty($personaFields)) {
            DB::table('global_config.persona')
                ->where('id_persona', $person->id_persona)
                ->update($personaFields);
        }

        // Si se envía código, actualizar en persona_alumno (mismo id)
        if (!empty($validated['codigo'])) {
            DB::table('global_config.persona_alumno')
                ->updateOrInsert(
                    ['id_alumno' => $person->id_persona],
                    ['codigo' => $validated['codigo']]
                );
        }

        DB::commit();

        // Consultar persona actualizada
        $updated = DB::selectOne("SELECT id_persona, nombre, paterno, materno FROM global_config.persona WHERE id_persona = ?", [$person->id_persona]);

        return ['success' => true, 'data' => $updated];

    } catch (Throwable $e) {
        DB::rollBack();
        return ['success' => false, 'message' => 'Error al actualizar persona: ' . $e->getMessage()];
    }
}

    /**
     * Eliminar persona + relaciones
     */
    public static function delete(Person $person): array
    {
        try {
            DB::beginTransaction();

            DB::table('efeso.usuario')->where('id_persona', $person->id_persona)->delete();
            DB::table('global_config.persona_virtual')->where('id_persona', $person->id_persona)->delete();
            DB::table('global_config.persona_alumno')->where('id_alumno', $person->id_persona)->delete();
            DB::table('global_config.persona')->where('id_persona', $person->id_persona)->delete();

            DB::commit();
            return ['success' => true];
        } catch (Throwable $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Error al eliminar persona: ' . $e->getMessage()];
        }
    }
}
