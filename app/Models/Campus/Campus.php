<?php

namespace App\Models\Campus;

use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    protected $table = 'global_config.campus';
    protected $primaryKey = 'id_campus';
    public $timestamps = false;

    protected $fillable = ['campus', 'codigo', 'estado', 'orden'];
}