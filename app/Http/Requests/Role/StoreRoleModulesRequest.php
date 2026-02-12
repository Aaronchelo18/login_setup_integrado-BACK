<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'modulos'   => ['required','array'],
            'modulos.*' => ['integer','distinct'], 
        ];
    }

    public function messages(): array
    {
        return [
            'modulos.required'   => 'Debes enviar al menos un módulo.',
            'modulos.array'      => 'El campo modulos debe ser un arreglo.',
            'modulos.*.integer'  => 'Cada módulo debe ser un ID numérico.',
            'modulos.*.distinct' => 'Hay módulos duplicados.',
        ];
    }
}
