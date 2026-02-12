<?php

namespace App\Http\Requests\Modulo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModuloRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $idParent = $this->input('id_parent');

        return [
            'id_parent' => ['nullable','integer','exists:modulo,id_modulo'],
            'nombre'    => [
                'required','string','max:128',
                Rule::unique('modulo', 'nombre')
                    ->where(fn ($q) => $q->where('id_parent', $idParent)),
            ],
            'url'       => ['nullable','string','max:264'],
            'imagen'    => ['nullable','string','max:128'],
            'estado'    => ['required', Rule::in(['0','1',0,1])],
        ];
    }

    public function messages(): array
    {
        return [
            'id_parent.exists' => 'El módulo padre no existe.',
            'nombre.required'  => 'El nombre es obligatorio.',
            'nombre.unique'    => 'Ya existe un módulo con ese nombre en el mismo nivel.',
            'estado.in'        => 'El estado debe ser 0 o 1.',
        ];
    }
}
