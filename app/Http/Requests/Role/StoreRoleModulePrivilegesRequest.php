<?php
// app/Http/Requests/Role/StoreRoleModulePrivilegesRequest.php
namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleModulePrivilegesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Aquí puedes poner gates/policies si corresponde
        return true;
    }

    public function rules(): array
    {
        return [
            'privilegios'   => ['required','array'],
            'privilegios.*' => ['integer','distinct'],  // IDs de privilegio
            // Si vas a aceptar por "clave" en vez de IDs, cambia aquí a 'string'
        ];
    }

    public function messages(): array
    {
        return [
            'privilegios.required'   => 'Debes enviar al menos un privilegio.',
            'privilegios.array'      => 'El campo privilegios debe ser un arreglo.',
            'privilegios.*.integer'  => 'Cada privilegio debe ser un ID numérico.',
            'privilegios.*.distinct' => 'Hay privilegios duplicados.',
        ];
    }
}