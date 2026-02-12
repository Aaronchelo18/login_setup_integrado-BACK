<?php

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;

class SyncRoleModulePrivilegesRequest extends FormRequest {
  public function rules(): array {
  return [
    'privilegios'   => ['required','array'],
    'privilegios.*' => ['integer','exists:privilegios,id_privilegio'], // OJO: privilegios + id_privilegio
  ];
}
}
