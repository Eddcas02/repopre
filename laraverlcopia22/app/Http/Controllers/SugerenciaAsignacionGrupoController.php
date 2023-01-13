<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SugerenciaAsignacionGrupo;
use App\Models\Flujos;
use App\Models\Bitacora;
use Carbon\Carbon;

class SugerenciaAsignacionGrupoController extends Controller
{
    public function index()
    {
        $SugerenciaAsignacionGrupo = SugerenciaAsignacionGrupo::where('eliminado',0)->get();
        $datos = array();
        $datos['sugerencias'] = $SugerenciaAsignacionGrupo;
        return $datos;
    }

    public function SugerenciasPorFlujo($id)
    {
        $SugerenciaAsignacionGrupo = SugerenciaAsignacionGrupo::join('GrupoAutorizacion', function($join){
            $join->on('SugerenciaAsignacionGrupo.id_grupoautorizacion', '=', 'GrupoAutorizacion.id_grupoautorizacion');
        })->select('SugerenciaAsignacionGrupo.id_grupoautorizacion', 'GrupoAutorizacion.identificador', 'GrupoAutorizacion.descripcion')
        ->where('SugerenciaAsignacionGrupo.id_flujo',$id)
        ->where('SugerenciaAsignacionGrupo.activo',1)->where('SugerenciaAsignacionGrupo.eliminado',0)
        ->where('GrupoAutorizacion.activo',1)->where('GrupoAutorizacion.eliminado',0)->get();
        $datos = array();
        $datos['sugerencias'] = $SugerenciaAsignacionGrupo;
        return $datos;
    }

    public function show(SugerenciaAsignacionGrupo $SugerenciaAsignacionGrupo)
    {
        return $SugerenciaAsignacionGrupo;
    }

    public function store(Request $request)
    {
        $SugerenciaAsignacionGrupo = new SugerenciaAsignacionGrupo;
        $SugerenciaAsignacionGrupo->id_flujo = $request->id_flujo;
        $SugerenciaAsignacionGrupo->id_grupoautorizacion = $request->id_grupoautorizacion;
        $SugerenciaAsignacionGrupo->activo = 1;
        $SugerenciaAsignacionGrupo->eliminado = 0;
        $SugerenciaAsignacionGrupo->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'SugerenciaAsignacionGrupo';
        $bitacora->parametros_nuevos = 'ID '.$SugerenciaAsignacionGrupo->id_sugerenciagrupo;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $SugerenciaAsignacionGrupo = SugerenciaAsignacionGrupo::find($id);
            $datosAnteriores = json_encode($SugerenciaAsignacionGrupo,true);
            $SugerenciaAsignacionGrupo->id_flujo = $request->id_flujo;
            $SugerenciaAsignacionGrupo->id_grupoautorizacion = $request->id_grupoautorizacion;
            $SugerenciaAsignacionGrupo->activo = $request->activo;
            $SugerenciaAsignacionGrupo->eliminado = 0;
            $SugerenciaAsignacionGrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'SugerenciaAsignacionGrupo';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $SugerenciaAsignacionGrupo = SugerenciaAsignacionGrupo::find($id);
            $SugerenciaAsignacionGrupo->eliminado = 1;
            $SugerenciaAsignacionGrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'SugerenciaAsignacionGrupo';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(SugerenciaAsignacionGrupo $SugerenciaAsignacionGrupo)
    {
        $SugerenciaAsignacionGrupo->delete();

        return response()->json(null, 204);
    }
}
