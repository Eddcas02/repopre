<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LotePago;
use Illuminate\Support\Facades\URL;

class LotePagoController extends Controller
{
    public function ListaLotes($tipo){
        $lotesTmp = LotePago::join('usuarios', function($join){
            $join->on('LotePago.id_usuario', '=', 'usuarios.id_usuario');
        })->selectRaw(
            "LotePago.id_lotepago,
            LotePago.tipo,
            LotePago.fecha_hora,
            LotePago.PathDocumentoPDF,
            LotePago.PathDocumentoExcel,
            usuarios.id_usuario,
            usuarios.nombre_usuario,
            usuarios.nombre,
            usuarios.apellido
            ")
        ->where('LotePago.tipo', $tipo)
        ->where('LotePago.Activo',1)->where('LotePago.Eliminado',0)->get();
        $datos = array();
        $lotes = array();
        foreach($lotesTmp as $lote){
            $lotes[] = [ 
            "id_lotepago" => $lote->id_lotepago,
            "tipo" => $lote->tipo,
            "fecha_hora" => $lote->fecha_hora,
            "PathDocumentoPDF" => URL($lote->PathDocumentoPDF),
            "PathDocumentoExcel" => URL($lote->PathDocumentoExcel),
            "id_usuario" => $lote->id_usuario,
            "nombre_usuario" => $lote->nombre_usuario,
            "nombre" => $lote->nombre,
            "apellido" => $lote->apellido
            ];
        }
        $datos['lotes'] = $lotes;
        return $datos;

    }
}