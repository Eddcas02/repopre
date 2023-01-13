<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CondicionAutorizacion;
use App\Models\Bitacora;
use Carbon\Carbon;

class CondicionAutorizacionController extends Controller
{
    public function index()
    {
        $condicionAutorizacion = CondicionAutorizacion::all();
        $datos = array();
        $datos['condiciones'] = $condicionAutorizacion;
        return $datos;
    }

    public function show(Request $request)
    {
        return $condicionAutorizacion;
    }

    public function store(Request $request)
    {
        $condicionAutorizacion = new CondicionAutorizacion;
        $condicionAutorizacion->descripcion = $request->descripcion;
        $condicionAutorizacion->parametro = $request->parametro;
        $condicionAutorizacion->activo = 1;
        $condicionAutorizacion->eliminado = 0;
        $condicionAutorizacion->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'CondicionAutorizacion';
        $bitacora->parametros_nuevos = 'ID '.$condicionAutorizacion->id_condicionautorizacion;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $condicionAutorizacion = CondicionAutorizacion::find($id);
            $datosAnteriores = json_encode($condicionAutorizacion,true);
            $condicionAutorizacion->descripcion = $request->descripcion;
            $condicionAutorizacion->parametro = $request->parametro;
            $condicionAutorizacion->activo = $request->activo;
            $condicionAutorizacion->eliminado = 0;
            $condicionAutorizacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'CondicionAutorizacion';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();
            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $condicionAutorizacion = CondicionAutorizacion::find($id);
            $condicionAutorizacion->eliminado = 1;
            $condicionAutorizacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'CondicionAutorizacion';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();
            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $condicionAutorizacion->delete();

        return response()->json(null, 204);
    }
}
