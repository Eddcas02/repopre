<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlujoSolicitud;

class FlujoSolicitudController extends Controller
{
    public function index()
    {
        $flujosolicitud = FlujoSolicitud::selectRaw(
            "FlujoSolicitud.id_flujosolicitud,
             FlujoSolicitud.id_flujo,
             FlujoSolicitud.doc_num,
             FlujoSolicitud.req_name,
             FlujoSolicitud.item_code,
             FlujoSolicitud.dscription as description,
             FlujoSolicitud.uom_code,
             FlujoSolicitud.price,
             FlujoSolicitud.quantity,
             FlujoSolicitud.unidades_totales,
             FlujoSolicitud.unidades_por_caja,
             FlujoSolicitud.comments,
             FlujoSolicitud.autorizador1,
             FlujoSolicitud.autorizador2,
             FlujoSolicitud.autorizador3,
             DATE_FORMAT(FlujoSolicitud.fecha_aut1,'%d-%m-%Y') as fecha_aut1,
             DATE_FORMAT(FlujoSolicitud.fecha_aut2,'%d-%m-%Y') as fecha_aut2,
             DATE_FORMAT(FlujoSolicitud.fecha_aut3,'%d-%m-%Y') as fecha_aut3"
        )
        ->orderBy('FlujoSolicitud.id_flujosolicitud', 'ASC')
        ->get();
        $datos = array();
        $datos['solicitud'] = $flujosolicitud;
        return $datos;
    }

    public function show($id)
    {
        $flujosolicitud = FlujoSolicitud::selectRaw(
            "FlujoSolicitud.id_flujosolicitud,
             FlujoSolicitud.id_flujo,
             FlujoSolicitud.doc_num,
             FlujoSolicitud.req_name,
             FlujoSolicitud.item_code,
             FlujoSolicitud.dscription as description,
             FlujoSolicitud.uom_code,
             FlujoSolicitud.price,
             FlujoSolicitud.quantity,
             FlujoSolicitud.unidades_totales,
             FlujoSolicitud.unidades_por_caja,
             FlujoSolicitud.comments,
             FlujoSolicitud.autorizador1,
             FlujoSolicitud.autorizador2,
             FlujoSolicitud.autorizador3,
             DATE_FORMAT(FlujoSolicitud.fecha_aut1,'%d-%m-%Y') as fecha_aut1,
             DATE_FORMAT(FlujoSolicitud.fecha_aut2,'%d-%m-%Y') as fecha_aut2,
             DATE_FORMAT(FlujoSolicitud.fecha_aut3,'%d-%m-%Y') as fecha_aut3"
        )
        ->where('FlujoSolicitud.id_flujo', $id)
        ->orderBy('FlujoSolicitud.id_flujosolicitud', 'ASC')
        ->get();
        $datos = array();
        $datos['solicitud'] = $flujosolicitud;
        return $datos;
    }

    public function store(Request $request)
    {
        $flujosolicitud = FlujoSolicitud::create($request->all());

        return response()->json($flujosolicitud, 201);
    }

    public function update(Request $request, $id)
    {
        $flujosolicitud->update($request->all());

        return response()->json($flujosolicitud, 200);
    }

    public function delete(Request $request)
    {
        $flujosolicitud->delete();

        return response()->json(null, 204);
    }
}
