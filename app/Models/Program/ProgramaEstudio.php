<?php

namespace App\Models\Program;

use Illuminate\Database\Eloquent\Model;

class ProgramaEstudio extends Model
{
    protected $table = 'global_config.programa_estudio';
    protected $primaryKey = 'id_programa_estudio';
    public $timestamps = false;

    protected $fillable = ['codigo', 'nombre', 'abreviatura'];
}