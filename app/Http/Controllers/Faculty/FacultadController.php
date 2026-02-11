<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Http\Data\Faculty\FacultadData;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class FacultadController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        // Capturamos el id_usuario de la URL, por ejemplo: ?id_usuario=1458
        $idUsuario = $request->query('id_usuario');

        $r = FacultadData::getAll($idUsuario);

        return $r['success'] 
            ? $this->ok($r['data'], 'Facultades obtenidas correctamente')
            : $this->error($r['message'], 500);
    }
}