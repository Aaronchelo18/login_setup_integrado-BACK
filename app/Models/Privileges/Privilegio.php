<?php
namespace App\Models\Privileges;

use Illuminate\Database\Eloquent\Model;

class Privilegio extends Model
{
    protected $table = 'efeso.privilegios';
    protected $primaryKey = 'id_privilegio';
    public $timestamps = false;

    protected $fillable = [
        'id_modulo',
        'nombre',
        'clave',
        'valor',
        'comentario',
        'estado'
    ];
}
