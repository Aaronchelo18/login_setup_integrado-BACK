<?php

namespace App\Models\Modules;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id_modulo
 * @property int         $id_parent
 * @property string      $nombre
 * @property int         $nivel
 * @property string|null $url
 * @property string|null $imagen
 * @property string      $estado
 */
class Modulo extends Model
{
    // Usar esquema tabla de Postgres
    protected $table = 'efeso.modulo';
    protected $primaryKey = 'id_modulo';
    public $timestamps = false;

    protected $fillable = [
        'id_parent',
        'nombre',
        'nivel',
        'url',
        'imagen',
        'estado',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'id_parent');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'id_parent');
    }
}
