<?php

namespace App\Models\Faculty;

use Illuminate\Database\Eloquent\Model;

class Facultad extends Model
{
    protected $table = 'global_config.facultad';
    protected $primaryKey = 'id_facultad';
    public $timestamps = false;

    protected $fillable = ['nombre'];
}