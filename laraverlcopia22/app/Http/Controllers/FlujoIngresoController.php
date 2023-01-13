<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlujoIngreso;

class FlujoIngresoController extends Controller
{
    public function index()
    {
        $flujoingreso = FlujoIngreso::selectRaw(
            "FlujoIngreso.id_flujoingreso,
             FlujoIngreso.id_flujo,
             FlujoIngreso.doc_num,
             DATE_FORMAT(FlujoIngreso.tax_date,'%d-%m-%Y')as tax_date,
             DATE_FORMAT(FlujoIngreso.doc_date,'%d-%m-%Y')as doc_date,
             FlujoIngreso.whs_name,
             FlujoIngreso.user,
             FlujoIngreso.item_code,
             FlujoIngreso.dscription,
             FlujoIngreso.uom_code,
             FlujoIngreso.quantity,
             FlujoIngreso.comments"
        )
        ->orderBy('FlujoIngreso.id_flujoingreso', 'ASC')
        ->get();
        $datos = array();
        $datos['ingreso'] = $flujoingreso;
        return $datos;
    }

    public function show($id)
    {
        $flujoingreso = FlujoIngreso::selectRaw(
            "FlujoIngreso.id_flujoingreso,
             FlujoIngreso.id_flujo,
             FlujoIngreso.doc_num,
             DATE_FORMAT(FlujoIngreso.tax_date,'%d-%m-%Y')as tax_date,
             DATE_FORMAT(FlujoIngreso.doc_date,'%d-%m-%Y')as doc_date,
             FlujoIngreso.whs_name,
             FlujoIngreso.user,
             FlujoIngreso.item_code,
             FlujoIngreso.dscription,
             FlujoIngreso.uom_code,
             FlujoIngreso.quantity,
             FlujoIngreso.comments"           
        )
        ->where('FlujoIngreso.id_flujo', $id)
        ->orderBy('FlujoIngreso.id_flujoingreso', 'ASC')
        ->get();
        $datos = array();
        $datos['ingreso'] = $flujoingreso;
        return $datos;
    }

    public function store(Request $request)
    {
        $flujoingreso = FlujoIngreso::create($request->all());

        return response()->json($flujoingreso, 201);
    }

    public function update(Request $request, $id)
    {
        $flujoingreso->update($request->all());

        return response()->json($flujoingreso, 200);
    }

    public function delete(Request $request)
    {
        $flujoingreso->delete();

        return response()->json(null, 204);
    }
}
