<?php

namespace App\Http\Controllers\Campus;

use App\Http\Controllers\Controller;
use App\Http\Data\Campus\CampusData;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        // Captura opcional: ?id_usuario=1475
        $idUsuario = $request->query('id_usuario');

        $r = CampusData::getAll($idUsuario);

        return $r['success'] 
            ? $this->ok($r['data'], 'Lista de campus cargada')
            : $this->error($r['message'], 500);
    }
}