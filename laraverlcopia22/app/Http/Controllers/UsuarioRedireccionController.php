<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioRedireccion;
use App\Models\Usuarios;
use App\Models\SeccionAplicacion;
use App\Models\Bitacora;
use Carbon\Carbon;

class UsuarioRedireccionController extends Controller
{
    public function index()
    {
        $UsuarioRedireccion = UsuarioRedireccion::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'UsuarioRedireccion.id_usuario');
        })->join('SeccionAplicacion as secApp', function($join){
            $join->on('secApp.id_seccionaplicacion', '=', 'UsuarioRedireccion.id_seccionaplicacion');
        })->selectRaw(
            "
            UsuarioRedireccion.id_usuarioredireccion,
            UsuarioRedireccion.id_usuario,
            UsuarioRedireccion.id_seccionaplicacion,
            UsuarioRedireccion.activo,
            UsuarioRedireccion.eliminado,
            usrCon.nombre_usuario as usuario_con,
            usrCon.nombre as nombre_con,
            usrCon.apellido as apellido_con,
            secApp.nombre as nombre_seccion,
            secApp.direccion as direccion_seccion,
            secApp.direccion_movil as redireccion_movil
            "
        )->where('UsuarioRedireccion.activo',1)
        ->where('UsuarioRedireccion.eliminado', 0)
        ->where('usrCon.activo',1)
        ->where('usrCon.eliminado', 0)
        ->where('secApp.activo',1)
        ->where('secApp.eliminado', 0)->get();
        $datos = array();
        $datos['redireccion'] = $UsuarioRedireccion;
        return $datos;
    }

    public function show($id)
    {
        $UsuarioRedireccion = UsuarioRedireccion::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'UsuarioRedireccion.id_usuario');
        })->join('SeccionAplicacion as secApp', function($join){
            $join->on('secApp.id_seccionaplicacion', '=', 'UsuarioRedireccion.id_seccionaplicacion');
        })->selectRaw(
            "
            UsuarioRedireccion.id_usuarioredireccion,
            UsuarioRedireccion.id_usuario,
            UsuarioRedireccion.id_seccionaplicacion,
            UsuarioRedireccion.activo,
            UsuarioRedireccion.eliminado,
            usrCon.nombre_usuario as usuario_con,
            usrCon.nombre as nombre_con,
            usrCon.apellido as apellido_con,
            secApp.nombre as nombre_seccion,
            secApp.direccion as direccion_seccion,
            secApp.direccion_movil as redireccion_movil
            "
        )->where('UsuarioRedireccion.id_usuario','=',$id)
        ->where('UsuarioRedireccion.activo',1)
        ->where('UsuarioRedireccion.eliminado', 0)
        ->where('usrCon.activo',1)
        ->where('usrCon.eliminado', 0)
        ->where('secApp.activo',1)
        ->where('secApp.eliminado', 0)->first();
        $datos = array();
        $datos['redireccion'] = $UsuarioRedireccion;
        return $datos;
    }

    public function store(Request $request)
    {
        $UsuarioRedireccion = new UsuarioRedireccion;
        $UsuarioRedireccion->id_usuario = $request->id_usuario;
        $UsuarioRedireccion->id_seccionaplicacion = $request->id_seccionaplicacion;
        $UsuarioRedireccion->activo = 1;
        $UsuarioRedireccion->eliminado = 0;
        $UsuarioRedireccion->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario_s;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'UsuarioRedireccion';
        $bitacora->parametros_nuevos = 'ID '.$UsuarioRedireccion->id_usuarioredireccion;
        $bitacora->save();
                
        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $UsuarioRedireccion = UsuarioRedireccion::find($id);
            $datosAnteriores = json_encode($UsuarioRedireccion,true);
            $UsuarioRedireccion->id_usuario = $request->id_usuario;
            $UsuarioRedireccion->id_seccionaplicacion = $request->id_seccionaplicacion;
            $UsuarioRedireccion->activo = $request->activo;
            $UsuarioRedireccion->eliminado = 0;
            $UsuarioRedireccion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioRedireccion';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $UsuarioRedireccion = UsuarioRedireccion::find($id);
            $UsuarioRedireccion->eliminado = 1;
            $UsuarioRedireccion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'UsuarioRedireccion';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $UsuarioRedireccion->delete();

        return response()->json(null, 204);
    }
}

