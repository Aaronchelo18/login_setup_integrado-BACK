<?php

namespace App\Http\Requests\Access;
use Illuminate\Foundation\Http\FormRequest;

class SaveRoleModulesRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return ['modulos'=>'required|array','modulos.*'=>'integer|min:1'];
  }
}
