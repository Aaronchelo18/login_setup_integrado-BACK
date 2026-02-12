<?php 

namespace App\Http\Requests\Efeso;

use Illuminate\Foundation\Http\FormRequest;

class SyncUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
           
            'roles'   => ['required','array'],
            'roles.*' => ['integer','distinct']
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'Debes enviar el arreglo "roles".',
            'roles.array'    => '"roles" debe ser un arreglo.',
            'roles.*.integer'=> 'Cada rol debe ser un entero.',
            'roles.*.distinct'=> 'Hay roles repetidos.'
        ];
    }
}
