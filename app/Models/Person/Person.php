<?php

namespace App\Models\Person;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\StudenPerson\StudentPerson;
use App\Models\PersonVirtual\PersonVirtual;

class Person extends Model
{
    //
    use HasFactory;
     // Referencia a la tabla con esquema
    protected $table = 'global_config.persona';
    protected $primaryKey = 'id_persona';
    public $timestamps = false;

    protected $fillable = ['nombre', 'paterno', 'materno'];

    /**
     * Relación uno a muchos con persona_virtual
     */
    public function personaVirtuales(): HasMany
    {
        return $this->hasMany(PersonVirtual::class, 'id_persona', 'id_persona');
    }

        // Relación con alumno
    public function alumno(): HasOne
    {
        return $this->hasOne(StudentPerson::class, 'id_alumno', 'id_persona');
    }

}
