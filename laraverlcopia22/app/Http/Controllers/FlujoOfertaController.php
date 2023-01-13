<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlujoOferta;

class FlujoOfertaController extends Controller
{
    public function index()
    {
        $flujooferta = FlujoOferta::selectRaw(
            "FlujoOferta.id_flujooferta,
             FlujoOferta.id_flujo,
             FlujoOferta.doc_num,
             DATE_FORMAT(FlujoOferta.doc_date,'%d-%m-%Y')as doc_date,
             FlujoOferta.card_code,
             FlujoOferta.card_name,
             FlujoOferta.item_code,
             FlujoOferta.dscription as description,
             FlujoOferta.uom_code,
             FlujoOferta.price,
             FlujoOferta.quantity"
        )
        ->orderBy('FlujoOferta.id_flujooferta', 'ASC')
        ->get();
        $datos = array();
        $datos['oferta'] = $flujooferta;
        return $datos;
    }

    public function show($id)
    {
        $flujooferta = FlujoOferta::selectRaw(
            "FlujoOferta.id_flujooferta,
             FlujoOferta.id_flujo,
             FlujoOferta.doc_num,
             DATE_FORMAT(FlujoOferta.doc_date,'%d-%m-%Y')as doc_date,
             FlujoOferta.card_code,
             FlujoOferta.card_name,
             FlujoOferta.item_code,
             FlujoOferta.dscription as description,
             FlujoOferta.uom_code,
             FlujoOferta.price,
             FlujoOferta.quantity"
        )
        ->where('FlujoOferta.id_flujo', $id)
        ->orderBy('FlujoOferta.id_flujooferta', 'ASC')
        ->get();
        $datos = array();
        $datos['oferta'] = $flujooferta;
        return $datos;
    }

    public function store(FlujoOferta $request)
    {
        $flujooferta = FlujoOferta::create($request->all());

        return response()->json($flujooferta, 201);
    }

    public function update(Request $request, $id)
    {
        $flujooferta->update($request->all());

        return response()->json($flujooferta, 200);
    }

    public function delete(Request $request)
    {
        $flujooferta->delete();

        return response()->json(null, 204);
    }
}

