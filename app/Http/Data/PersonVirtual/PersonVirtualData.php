<?php
namespace App\Http\Data\PersonVirtual;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\PersonVirtual\PersonVirtual;
use Throwable;

class PersonVirtualData
{
    /**
     * Listar todas las personas virtuales
     */
    public static function getAll(): array
    {
        try {
            $data = DB::select("
                SELECT id_persona_virtual, id_persona, correo, estado, es_principal, creado_en, ultima_sesion
                FROM global_config.persona_virtual
                ORDER BY id_persona_virtual DESC
            ");
            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al obtener persona_virtual: ' . $e->getMessage()];
        }
    }
    
    /**
     * Listar una persona virtual por ID
     */
    public static function getById(int $id): array
    {
        try {
            $pv = PersonVirtual::find($id);
            return $pv
                ? ['success' => true, 'data' => $pv]
                : ['success' => false, 'message' => 'Correo virtual no encontrado.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al obtener persona_virtual: ' . $e->getMessage()];
        }
    }

    
    /**
     * Crear una nueva persona virtual
     */
   /**
 * Crear una nueva persona virtual
 */
public static function create(array $data): array
{
    $validator = Validator::make($data, [
        'id_persona' => 'required|exists:persona,id_persona',
        'correo' => 'required|email|unique:persona_virtual,correo',
        'es_principal' => 'required|in:0,1',
    ]);

    if ($validator->fails()) {
        return ['success' => false, 'message' => 'Datos inválidos.', 'errors' => $validator->errors()];
    }

    try {
        DB::beginTransaction();

        if ($data['es_principal'] === '1') {
            PersonVirtual::where('id_persona', $data['id_persona'])
                ->where('es_principal', '1')
                ->update(['es_principal' => '0']);
        }

        $registro = PersonVirtual::create([
            'id_persona' => $data['id_persona'],
            'correo' => $data['correo'],
            'estado' => '1', // CAMBIADO: Estado automáticamente en 1 (activo)
            'verificado' => '0', // Mantenemos verificado en 0
            'es_principal' => $data['es_principal'],
            'creado_en' => now(),
            'ultima_sesion' => now()
        ]);

        DB::commit();
        return ['success' => true, 'data' => $registro];
    } catch (Throwable $e) {
        DB::rollBack();
        return ['success' => false, 'message' => 'Error al crear persona_virtual: ' . $e->getMessage()];
    }
}
    
    /**
     * Actualizar una persona virtual
     */

    public static function update(PersonVirtual $pv, array $data): array
    {
        $rules = [
            'estado' => 'sometimes|in:0,1',
            'verificado' => 'sometimes|in:0,1',
            'es_principal' => 'sometimes|in:0,1'
        ];

        if (isset($data['correo']) && $data['correo'] !== $pv->correo) {
            $rules['correo'] = 'email|unique:persona_virtual,correo,' . $pv->id_persona_virtual . ',id_persona_virtual';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return ['success' => false, 'message' => 'Datos inválidos.', 'errors' => $validator->errors()];
        }

        try {
            DB::beginTransaction();

            // Si este correo era principal y lo están quitando
            if ($pv->es_principal == '1' && isset($data['es_principal']) && $data['es_principal'] === '0') {
                // Eliminarlo de efeso.usuario
                DB::table('efeso.usuario')
                    ->where('correo', $pv->correo)
                    ->where('id_persona', $pv->id_persona)
                    ->delete();
            }

            // Si se está estableciendo uno nuevo como principal
            if (isset($data['es_principal']) && $data['es_principal'] === '1') {
                // Poner los demás como no principales
                PersonVirtual::where('id_persona', $pv->id_persona)
                    ->where('id_persona_virtual', '!=', $pv->id_persona_virtual)
                    ->update(['es_principal' => '0']);

                // Insertar en efeso.usuario si no existe
                $existe = DB::table('efeso.usuario')
                    ->where('correo', $pv->correo)
                    ->where('id_persona', $pv->id_persona)
                    ->exists();

                if (!$existe) {
                    DB::table('efeso.usuario')->insert([
                        'id_persona' => $pv->id_persona,
                        'correo' => $pv->correo
                    ]);
                }
            }

            $pv->fill($data)->save();

            DB::commit();
            return ['success' => true, 'data' => $pv->fresh()];
        } catch (Throwable $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Error al actualizar persona_virtual: ' . $e->getMessage()];
        }
    }


    /**
     * Eliminar una persona virtual
     */
    public static function delete(PersonVirtual $pv): array
    {
        try {
            $pv->delete();
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al eliminar persona_virtual: ' . $e->getMessage()];
        }
    }
}
