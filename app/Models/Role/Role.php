<?php

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table      = 'rol';
    protected $primaryKey = 'id_rol';
    
    protected $fillable = ['nombre', 'estado'];
    public $timestamps = false;
}
