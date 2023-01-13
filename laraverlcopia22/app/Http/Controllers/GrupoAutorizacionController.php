<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GrupoAutorizacion;
use App\Models\Bitacora;
use Carbon\Carbon;

class GrupoAutorizacionController extends Controller
{
    public function index()
    {
        $grupoautorizacion = GrupoAutorizacion::select(
            'GrupoAutorizacion.id_grupoautorizacion as id_grupo',
            'GrupoAutorizacion.identificador',
            'GrupoAutorizacion.descripcion',
            'GrupoAutorizacion.numero_niveles',
            'GrupoAutorizacion.activo',
            'GrupoAutorizacion.eliminado'
        )
        ->where('eliminado',0)->orderBy('GrupoAutorizacion.id_grupoautorizacion')->get();
        $datos = array();
        $datos['grupos'] = $grupoautorizacion;
        return $datos;
    }

    public function show($id)
    {
        return $grupoautorizacion;
    }

    public function store(Request $request)
    {
        $grupoautorizacion = new GrupoAutorizacion;
        $grupoautorizacion->identificador = $request->identificador;
        $grupoautorizacion->descripcion = $request->descripcion;
        $grupoautorizacion->numero_niveles = $request->numero_niveles;
        $grupoautorizacion->activo = 1;
        $grupoautorizacion->eliminado = 0;
        $grupoautorizacion->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'GrupoAutorizacion';
        $bitacora->parametros_nuevos = 'ID '.$grupoautorizacion->id_grupoautorizacion;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $grupoautorizacion = GrupoAutorizacion::find($id);
            $datosAnteriores = json_encode($grupoautorizacion,true);
            $grupoautorizacion->identificador = $request->identificador;
            $grupoautorizacion->descripcion = $request->descripcion;
            $grupoautorizacion->numero_niveles = $request->numero_niveles;
            $grupoautorizacion->activo = $request->activo;
            $grupoautorizacion->eliminado = 0;
            $grupoautorizacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'GrupoAutorizacion';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $grupoautorizacion = GrupoAutorizacion::find($id);
            $grupoautorizacion->eliminado = 1;
            $grupoautorizacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'GrupoAutorizacion';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $grupoautorizacion->delete();

        return response()->json(null, 204);
    }
}
