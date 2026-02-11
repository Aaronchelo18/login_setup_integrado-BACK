<?php

namespace App\Http\Controllers\Program;

use App\Http\Controllers\Controller;
use App\Http\Data\Program\ProgramaEstudioData;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProgramaEstudioController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        // Captura opcional de la URL: ?id_usuario=1475
        $idUsuario = $request->query('id_usuario');

        $r = ProgramaEstudioData::getAll($idUsuario);

        return $r['success'] 
            ? $this->ok($r['data'], 'Programas de estudio obtenidos correctamente')
            : $this->error($r['message'], 500);
    }
}