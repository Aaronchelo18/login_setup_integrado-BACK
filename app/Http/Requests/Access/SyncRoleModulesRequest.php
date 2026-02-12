<?php

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;

class SyncRoleModulesRequest extends FormRequest {
  public function rules(): array {
  return [
    'modulos'   => ['required','array'],
    'modulos.*' => ['integer','exists:modulo,id_modulo'],   // OJO: modulo (singular) + id_modulo
  ];
}
}
