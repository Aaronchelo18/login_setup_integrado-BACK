<?php

namespace App\Http\Requests\Access;
use Illuminate\Foundation\Http\FormRequest;

class SavePrivilegesRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return ['privilegios'=>'array','privilegios.*'=>'integer|min:1'];
  }
}
