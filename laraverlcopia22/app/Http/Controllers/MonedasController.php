<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Monedas;
use App\Models\Bitacora;
use Carbon\Carbon;

class MonedasController extends Controller
{
    public function index()
    {
        $monedas = Monedas::where('eliminado',0)->get();
        $datos = array();
        $datos['monedas'] = $monedas;
        return $datos;
    }

    public function show($id)
    {
        $monedas = Monedas::where('id_moneda', $id)->get();
        $datos = array();
        $datos['monedas'] = $monedas;
        return $datos;        
    }

    public function store(Request $request)
    {
        $monedas = new Monedas;
        $monedas->nombre = $request->nombre;
        $monedas->simbolo = $request->simbolo;
        $monedas->activo = 1;
        $monedas->eliminado = 0;
        $monedas->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'Monedas';
        $bitacora->parametros_nuevos = 'ID '.$monedas->id_moneda;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $monedas = Monedas::find($id);
            $datosAnteriores = json_encode($monedas,true);
            $monedas->nombre = $request->nombre;
            $monedas->simbolo = $request->simbolo;
            $monedas->activo = $request->activo;
            $monedas->eliminado = 0;
            $monedas->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'Monedas';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $monedas = Monedas::find($id);
            $monedas->eliminado = 1;
            $monedas->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'Monedas';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $monedas->delete();

        return response()->json(null, 204);
    }
}

