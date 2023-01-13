<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CuentaGrupoAutorizacion;
use App\Models\Flujos;
use App\Models\Bitacora;
use Carbon\Carbon;

class CuentaGrupoAutorizacionController extends Controller
{
    public function index()
    {
        $CuentaGrupoAutorizacion = CuentaGrupoAutorizacion::join('GrupoAutorizacion', function($join){
            $join->on('CuentaGrupoAutorizacion.id_grupoautorizacion',
                '=',
                'GrupoAutorizacion.id_grupoautorizacion'
            );
        })
        ->selectRaw(
            "CuentaGrupoAutorizacion.id_cuentagrupo as id_cuentagrupo,
            CuentaGrupoAutorizacion.id_grupoautorizacion as id_grupoautorizacion,
             GrupoAutorizacion.identificador,
             CuentaGrupoAutorizacion.CodigoCuenta as CodigoCuenta,
             CuentaGrupoAutorizacion.activo,
             CuentaGrupoAutorizacion.eliminado"
        )->where('CuentaGrupoAutorizacion.eliminado',0)
        ->where('CuentaGrupoAutorizacion.activo',1)
        ->where('GrupoAutorizacion.eliminado',0)
        ->where('GrupoAutorizacion.activo',1)
        ->get();
        $datos = array();
        $datos['cuenta_grupo_autorizacion'] = $CuentaGrupoAutorizacion;
        return $datos;
    }

    public function show(CuentaGrupoAutorizacion $CuentaGrupoAutorizacion)
    {
        return $CuentaGrupoAutorizacion;
    }

    public function store(Request $request)
    {
        $CuentaGrupoAutorizacion = new CuentaGrupoAutorizacion;
        $CuentaGrupoAutorizacion->id_grupoautorizacion = $request->id_grupoautorizacion;
        $CuentaGrupoAutorizacion->CodigoCuenta = $request->CodigoCuenta;
        $CuentaGrupoAutorizacion->activo = 1;
        $CuentaGrupoAutorizacion->eliminado = 0;
        $CuentaGrupoAutorizacion->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'CuentaGrupoAutorizacion';
        $bitacora->parametros_nuevos = 'ID '.$CuentaGrupoAutorizacion->id_cuentagrupo;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $CuentaGrupoAutorizacion = CuentaGrupoAutorizacion::find($id);
            $datosAnteriores = json_encode($CuentaGrupoAutorizacion,true);
            $CuentaGrupoAutorizacion->id_grupoautorizacion = $request->id_grupoautorizacion;
            $CuentaGrupoAutorizacion->CodigoCuenta = $request->CodigoCuenta;
            $CuentaGrupoAutorizacion->activo = $request->activo;
            $CuentaGrupoAutorizacion->eliminado = 0;
            $CuentaGrupoAutorizacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'CuentaGrupoAutorizacion';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $CuentaGrupoAutorizacion = CuentaGrupoAutorizacion::find($id);
            $CuentaGrupoAutorizacion->eliminado = 1;
            $CuentaGrupoAutorizacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'CuentaGrupoAutorizacion';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(CuentaGrupoAutorizacion $CuentaGrupoAutorizacion)
    {
        $CuentaGrupoAutorizacion->delete();

        return response()->json(null, 204);
    }
}
