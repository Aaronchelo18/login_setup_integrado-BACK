<?php

namespace App\Models\StudenPerson;

use Illuminate\Database\Eloquent\Model;
use App\Models\Person\Person;

class StudentPerson extends Model
{
    //
    protected $table = 'global_config.persona_alumno';
    protected $primaryKey = 'id_alumno';
    public $timestamps = false;

    protected $fillable = [
        'id_alumno',
        'codigo',
        'estado',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'id_alumno', 'id_persona');
    }
}
