<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlujoFacturaDocumento;

class FlujoFacturaDocumentoController extends Controller
{ 
    public function show($id)
    {
        $flujofacturadocumento = FlujoFacturaDocumento::selectRaw(
            "FlujoFacturaDocumento.id_flujofacturadocumento,
             FlujoFacturaDocumento.id_flujo,
             FlujoFacturaDocumento.src_path, 
             FlujoFacturaDocumento.file_name,
             FlujoFacturaDocumento.file_ext"           
        )
        ->where('FlujoFacturaDocumento.id_flujo', $id)
        ->orderBy('FlujoFacturaDocumento.id_flujofacturadocumento', 'ASC')
        ->get();
        $datos = array();
        $datos['facturadocumento'] = $flujofacturadocumento;
        return $datos;
    } 
}

