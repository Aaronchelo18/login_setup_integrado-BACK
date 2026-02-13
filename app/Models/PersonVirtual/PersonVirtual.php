<?php

namespace App\Models\PersonVirtual;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Person\Person;

class PersonVirtual extends Model
{
    //
    //
      use Notifiable, HasFactory;

   
    // Especificamos la tabla incluyendo el esquema
    protected $table = 'global_config.persona_virtual';
    protected $primaryKey = 'id_persona_virtual';
    public $timestamps = false;

    // Campos asignables en masa
    protected $fillable = [
        'id_persona',
        'correo',
        'verificado',
        'estado',
        'es_principal',
        'creado_en',
        'ultima_sesion',
    ];

    // Casts para convertir automáticamente campos al tipo correcto
    protected $casts = [
        'verificado' => 'string',
        'estado' => 'string',
        'es_principal' => 'string',
        'creado_en' => 'datetime',
        'ultima_sesion' => 'datetime',
    ];

    // Relación con la tabla persona
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'id_persona', 'id_persona');
    }
}
