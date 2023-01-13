<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlujoCompensarSeleccionado;
use App\Models\Flujos;
use App\Models\Bitacora;
use Carbon\Carbon;

class FlujoCompensarSeleccionadoController extends Controller
{
    public function index($id)
    {
        $FlujoCompensarSeleccionado = FlujoCompensarSeleccionado::where('id_usuario',$id)
        ->where('activo','=',1)
        ->get();
        $datos = array();
        $datos['pagos'] = $FlujoCompensarSeleccionado;
        return $datos;
    }

    public function show($id_usuario, $id_flujo)
    {
        $FlujoCompensarSeleccionado = FlujoCompensarSeleccionado::where('id_flujo',$id_flujo)
        ->where('activo','=',1)
        ->first();
        if($FlujoCompensarSeleccionado){
            if($FlujoCompensarSeleccionado->id_usuario == $id_usuario){
                return response()->json("SI");
            }else{
                return response()->json("No");
            }
        }else{
            $fechaActual = Carbon::now('America/Guatemala');
            $NuevaSeleccion = new FlujoCompensarSeleccionado;
            $NuevaSeleccion->id_flujo = $id_flujo;
            $NuevaSeleccion->id_usuario = $id_usuario;
            $NuevaSeleccion->activo = 1;
            $NuevaSeleccion->fecha = $fechaActual;
            $NuevaSeleccion->save();
            return response()->json("SI");
        }
    }

    public function store(Request $request)
    {
        return response()->json("OK");
    }

    public function update(Request $request, $id)
    {
        $FlujoCompensarSeleccionado = FlujoCompensarSeleccionado::where('id_flujo',$request->id_flujo)
        ->where('id_usuario',$request->id_usuario)
        ->where('activo','=',1)
        ->first();
        if($FlujoCompensarSeleccionado){
            $FlujoCompensarSeleccionado->activo = 0;
            $FlujoCompensarSeleccionado->save();
        }
        return response()->json("OK");
    }

    public function delete(FlujoCompensarSeleccionado $FlujoCompensarSeleccionado)
    {
        return response()->json("OK");
    }
}
