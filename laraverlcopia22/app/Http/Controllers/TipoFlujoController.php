<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoFlujo;
use App\Models\Bitacora;
use Carbon\Carbon;

class TipoFlujoController extends Controller
{
    public function index()
    {
        $tipoFlujo = TipoFlujo::join('EstadoFlujo', function($join){
            $join->on('EstadoFlujo.id_estadoflujo', '=', 'TipoFlujo.id_estadoinicial');
        })
        ->select('TipoFlujo.id_tipoflujo',
        'TipoFlujo.descripcion',
        'TipoFlujo.id_estadoinicial',
        'EstadoFlujo.descripcion as estadoinicial',
        'TipoFlujo.activo',
        'TipoFlujo.eliminado')->get();
        $datos = array();
        $datos['tipos'] = $tipoFlujo;
        return $datos;
    }

    public function show(TipoFlujo $tipoFlujo)
    {
        return $tipoFlujo;
    }

    public function store(Request $request)
    {
        $tipoFlujo = new TipoFlujo;
        $tipoFlujo->descripcion = $request->descripcion;
        $tipoFlujo->id_estadoinicial = $request->id_estadoinicial;
        $tipoFlujo->activo = 1;
        $tipoFlujo->eliminado = 0;
        $tipoFlujo->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'TipoFlujo';
        $bitacora->parametros_nuevos = 'ID '.$tipoFlujo->id_tipoflujo;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $tipoFlujo = TipoFlujo::find($id);
            $datosAnteriores = json_encode($tipoFlujo,true);
            $tipoFlujo->descripcion = $request->descripcion;
            $tipoFlujo->id_estadoinicial = $request->id_estadoinicial;
            $tipoFlujo->activo = $request->activo;
            $tipoFlujo->eliminado = 0;
            $tipoFlujo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'TipoFlujo';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $tipoFlujo = TipoFlujo::find($id);
            $tipoFlujo->eliminado = 1;
            $tipoFlujo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'TipoFlujo';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(TipoFlujo $tipoFlujo)
    {
        $tipoFlujo->delete();

        return response()->json(null, 204);
    }
}
