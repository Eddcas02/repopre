<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EstadoFlujo;
use App\Models\Bitacora;
use Carbon\Carbon;

class EstadoFlujoController extends Controller
{
    public function index()
    {
        $estadoFlujo = EstadoFlujo::leftJoin('EstadoFlujo as EstadoFlujoPadre', function($join){
            $join->on('EstadoFlujoPadre.id_estadoflujo', '=', 'EstadoFlujo.id_estadoflujopadre');
        })->select(
            'EstadoFlujo.id_estadoflujo',
            'EstadoFlujo.id_estadoflujopadre',
            'EstadoFlujoPadre.descripcion as estadopadre',
            'EstadoFlujo.descripcion',
            'EstadoFlujo.activo',
            'EstadoFlujo.eliminado'
        )->orderBy('EstadoFlujo.id_estadoflujo')->get();
        $datos = array();
        $datos['estados'] = $estadoFlujo;
        return $datos;
    }

    public function show($id)
    {
        return $estadoFlujo;
    }

    public function store(Request $request)
    {
        $estadoFlujo = new EstadoFlujo;
        $estadoFlujo->id_estadoflujopadre = $request->id_estadoflujopadre;
        $estadoFlujo->descripcion = $request->descripcion;
        $estadoFlujo->activo = 1;
        $estadoFlujo->eliminado = 0;
        $estadoFlujo->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'EstadoFlujo';
        $bitacora->parametros_nuevos = 'ID '.$estadoFlujo->id_estadoflujo;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $estadoFlujo = EstadoFlujo::find($id);
            $datosAnteriores = json_encode($estadoFlujo,true);
            $estadoFlujo->id_estadoflujopadre = $request->id_estadoflujopadre;
            $estadoFlujo->descripcion = $request->descripcion;
            $estadoFlujo->activo = $request->activo;
            $estadoFlujo->eliminado = 0;
            $estadoFlujo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'EstadoFlujo';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            EstadoFlujo::where('id_estadoflujopadre', $id)->update(['id_estadoflujopadre' => 0]);
            $estadoFlujo = EstadoFlujo::find($id);
            $estadoFlujo->eliminado = 1;
            $estadoFlujo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'EstadoFlujo';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $estadoFlujo->delete();

        return response()->json(null, 204);
    }
}
