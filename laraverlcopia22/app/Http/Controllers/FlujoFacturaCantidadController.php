<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlujoFacturaCantidad;

class FlujoFacturaCantidadController extends Controller
{ 
    public function show($id)
    {
        $flujofacturacantidad = FlujoFacturaCantidad::selectRaw(
            "FlujoFacturaCantidad.id_flujofacturacantidad,
             FlujoFacturaCantidad.id_flujo,
             FlujoFacturaCantidad.doc_num, 
             FlujoFacturaCantidad.cant_facturas"          
        )
        ->where('FlujoFacturaCantidad.id_flujo', $id)
        ->orderBy('FlujoFacturaCantidad.id_flujofacturacantidad', 'ASC')
        ->get();
        $datos = array();
        $datos['facturacantidad'] = $flujofacturacantidad;
        return $datos;
    } 
}
