<?php

namespace App\Http\Requests\Access;
use Illuminate\Foundation\Http\FormRequest;

class RoleAppTreeRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return ['id_rol'=>['required','integer','min:1'], 'id_app'=>['required','integer','min:1']];
  }
  protected function prepareForValidation(): void {
    if ($this->route('id_rol')) $this->merge(['id_rol'=>(int)$this->route('id_rol')]);
    if ($this->route('id_app')) $this->merge(['id_app'=>(int)$this->route('id_app')]);
  }
}
