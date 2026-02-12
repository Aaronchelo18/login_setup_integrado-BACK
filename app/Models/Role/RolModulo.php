<?php

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Model;

class RolModulo extends Model
{
    protected $table = 'rol_modulo';
    public $timestamps = false;
    protected $primaryKey = ['id_rol', 'id_modulo']; // Combinación de claves primarias
    public $incrementing = false;

    protected $fillable = ['id_rol', 'id_modulo'];
}