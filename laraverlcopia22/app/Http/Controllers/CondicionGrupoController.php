<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CondicionGrupo;
use App\Models\Bitacora;
use Carbon\Carbon;

class CondicionGrupoController extends Controller
{
    public function index()
    {
        $condiciongrupo = CondicionGrupo::join('GrupoAutorizacion', function($join){
            $join->on('CondicionGrupoAutorizacion.id_grupoautorizacion',
                '=',
                'GrupoAutorizacion.id_grupoautorizacion'
            );
        })
        ->selectRaw(
            "CondicionGrupoAutorizacion.id_condiciongrupoautorizacion as id_condiciongrupo,
             CondicionGrupoAutorizacion.id_condicionautorizacion as id_condicion,
             CondicionGrupoAutorizacion.id_grupoautorizacion as id_grupo,
             GrupoAutorizacion.identificador,
             CondicionGrupoAutorizacion.activo,
             CondicionGrupoAutorizacion.eliminado"
        )
        ->orderBy('CondicionGrupoAutorizacion.id_condiciongrupoautorizacion')
        ->get();
        $datos = array();
        $datos['detalle'] = $condiciongrupo;
        return $datos;
    }

    public function show($id)
    {
        $condiciongrupo = CondicionGrupo::join('GrupoAutorizacion', function($join){
            $join->on('CondicionGrupoAutorizacion.id_grupoautorizacion',
                '=',
                'GrupoAutorizacion.id_grupoautorizacion'
            );
        })
        ->selectRaw(
            "CondicionGrupoAutorizacion.id_condiciongrupoautorizacion as id_condiciongrupo,
             CondicionGrupoAutorizacion.id_condicionautorizacion as id_condicion,
             CondicionGrupoAutorizacion.id_grupoautorizacion as id_grupo,
             GrupoAutorizacion.identificador,
             CondicionGrupoAutorizacion.activo,
             CondicionGrupoAutorizacion.eliminado"
        )
        ->where('CondicionGrupoAutorizacion.id_condicionautorizacion', $id)
        ->orderBy('CondicionGrupoAutorizacion.id_condiciongrupoautorizacion')
        ->get();
        $datos = array();
        $datos['detalle'] = $condiciongrupo;
        return $datos;
    }

    public function store(Request $request, $codigo)
    {
        $id_grupos = explode("|", $codigo);
        $bandera = 1;
        foreach($id_grupos as $id_grupo){
            if (!empty($id_grupo)){
                $grupos = CondicionGrupo::join('GrupoAutorizacion', function($join){
                    $join->on('CondicionGrupoAutorizacion.id_grupoautorizacion',
                        '=',
                        'GrupoAutorizacion.id_grupoautorizacion');
                })
                ->select('GrupoAutorizacion.identificador')
                ->where('CondicionGrupoAutorizacion.id_condicionautorizacion',
                         $request->id_condicionautorizacion)
                ->where('CondicionGrupoAutorizacion.id_grupoautorizacion', $id_grupo)
                ->where('CondicionGrupoAutorizacion.eliminado', 0)->get();
                if ($grupos->count() > 0) {
                    $bandera+=1;
                }
            }
        }

        if($bandera > 1){
            return response()->json("Repetidos");
        }else{
            foreach($id_grupos as $grupo){
                if (!empty($grupo)){
                    $condiciongrupo = new CondicionGrupo;
                    $condiciongrupo->id_condicionautorizacion = $request->id_condicionautorizacion;
                    $condiciongrupo->id_grupoautorizacion = $grupo;
                    $condiciongrupo->activo = 1;
                    $condiciongrupo->eliminado = 0;
                    $condiciongrupo->save();
                    $bandera*=1;

                    $fechaActual = Carbon::now('America/Guatemala');
                    $bitacora = new Bitacora;
                    $bitacora->id_usuario = $request->id_usuario;
                    $bitacora->fecha_hora = $fechaActual;
                    $bitacora->accion = 'crear';
                    $bitacora->objeto = 'CondicionGrupo';
                    $bitacora->parametros_nuevos = 'ID '.$condiciongrupo->id_condiciongrupoautorizacion;
                    $bitacora->save();
                }
            }
        }
        if($bandera<=1){
            return response()->json("OK");
        }
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $condiciongrupo = CondicionGrupo::find($id);
            $condiciongrupo->eliminado = 1;
            $condiciongrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'CondicionGrupo';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();
            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $condiciongrupo = CondicionGrupo::find($id);
            $datosAnteriores = json_encode($condiciongrupo,true);
            $condiciongrupo->id_grupoautorizacion = $request->id_grupoautorizacion;
            $condiciongrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'CondicionGrupo';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();
            return response()->json("OK");
        } else if ($opcion == '3') {
            $condiciongrupo = CondicionGrupo::find($id);
            $datosAnteriores = json_encode($condiciongrupo,true);
            $condiciongrupo->activo = $request->activo;
            $condiciongrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'CondicionGrupo';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();
            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $condiciongrupo->delete();

        return response()->json(null, 204);
    }
}
