<?php

namespace App\Models\WorkerPerson;

use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerPerson extends Model
{
    //
    use HasFactory;
    // Referencia a la tabla con esquema
    protected $table = 'global_config.persona_trabajador';
    protected $primaryKey = 'id_trabajador';
    public $timestamps = false;

    protected $fillable = [
        'id_trabajador',   // 🔹 CORREGIDO
        'cargo',
        'estado'
    ];

       /**
     * Relación uno a uno con persona 
     * El trabajador pertenece a una persona
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'id_trabajador', 'id_persona');
    }
}
