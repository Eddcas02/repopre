<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlujoOrden;

class FlujoOrdenController extends Controller
{
    public function index()
    {
        $flujoorden = FlujoOrden::selectRaw(
            "FlujoOrden.id_flujoorden,
             FlujoOrden.id_flujo,
             FlujoOrden.docu_num,
             DATE_FORMAT(FlujoOrden.tax_date,'%d-%m-%Y')as tax_date,
             DATE_FORMAT(FlujoOrden.doc_date,'%d-%m-%Y')as doc_date,
             FlujoOrden.card_code,
             FlujoOrden.card_name,
             FlujoOrden.fac_nit,
             FlujoOrden.phone1,
             FlujoOrden.termino_pago,
             FlujoOrden.address,
             FlujoOrden.user,
             FlujoOrden.item_code,
             FlujoOrden.price,
             FlujoOrden.quantity,
             FlujoOrden.line_total,
             FlujoOrden.doc_total,
             FlujoOrden.comment,
             FlujoOrden.crea_usuario,
             DATE_FORMAT(FlujoOrden.crea_fecha,'%d-%m-%Y') as crea_fecha,
             FlujoOrden.autoriza_usuario,
             DATE_FORMAT(FlujoOrden.autoriza_fecha,'%d-%m-%Y') as autoriza_fecha"
        )
        ->orderBy('FlujoOrden.id_flujoorden', 'ASC')
        ->get();
        $datos = array();
        $datos['orden'] = $flujoorden;
        return $datos;
    }

    public function show($id)
    {
        $flujoorden = FlujoOrden::selectRaw(
            "FlujoOrden.id_flujoorden,
             FlujoOrden.id_flujo,
             FlujoOrden.docu_num,
             DATE_FORMAT(FlujoOrden.tax_date,'%d-%m-%Y')as tax_date,
             DATE_FORMAT(FlujoOrden.doc_date,'%d-%m-%Y')as doc_date,
             FlujoOrden.card_code,
             FlujoOrden.card_name,
             FlujoOrden.fac_nit,
             FlujoOrden.phone1,
             FlujoOrden.termino_pago,
             FlujoOrden.address,
             FlujoOrden.user,
             FlujoOrden.item_code,
             FlujoOrden.price,
             FlujoOrden.quantity,
             FlujoOrden.line_total,
             FlujoOrden.doc_total,
             FlujoOrden.comment,
             FlujoOrden.crea_usuario,
             DATE_FORMAT(FlujoOrden.crea_fecha,'%d-%m-%Y') as crea_fecha,
             FlujoOrden.autoriza_usuario,
             DATE_FORMAT(FlujoOrden.autoriza_fecha,'%d-%m-%Y') as autoriza_fecha"             
        )
        ->where('FlujoOrden.id_flujo', $id)
        ->orderBy('FlujoOrden.id_flujoorden', 'ASC')
        ->get();
        $datos = array();
        $datos['orden'] = $flujoorden;
        return $datos;
    }

    public function store(Request $request)
    {
        $flujoorden = FlujoOrden::create($request->all());

        return response()->json($flujoorden, 201);
    }

    public function update(Request $request, $id)
    {
        $flujoorden->update($request->all());

        return response()->json($flujoorden, 200);
    }

    public function delete(Request $request)
    {
        $flujoorden->delete();

        return response()->json(null, 204);
    }
}
