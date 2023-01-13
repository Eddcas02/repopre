<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioSinNotificacionCorreo;
use App\Models\Flujos;
use App\Models\UsuarioGrupo;
use App\Models\Bitacora;
use Carbon\Carbon;

class UsuarioSinNotificacionCorreoController extends Controller
{
    public function index()
    {
        $UsuarioSinNotificacionCorreo = UsuarioSinNotificacionCorreo::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'UsuarioSinNotificacionCorreo.id_usuario');
        })->selectRaw(
            "
            UsuarioSinNotificacionCorreo.id_usuariosinnotificacioncorreo,
            UsuarioSinNotificacionCorreo.id_usuario,
            usrCon.nombre_usuario as usuario_con,
            usrCon.nombre as nombre_con,
            usrCon.apellido as apellido_con,
            UsuarioSinNotificacionCorreo.activo,
            UsuarioSinNotificacionCorreo.eliminado
            "
        )->where('UsuarioSinNotificacionCorreo.activo',1)
        ->where('UsuarioSinNotificacionCorreo.eliminado', 0)->get();
        $datos = array();
        $datos['prioridad'] = $UsuarioSinNotificacionCorreo;
        return $datos;
    }

    public function store(Request $request)
    {
        $UsuarioSinNotificacionCorreo = new UsuarioSinNotificacionCorreo;
        $UsuarioSinNotificacionCorreo->id_usuario = $request->id_usuario;
        $UsuarioSinNotificacionCorreo->id_tiponotificacion = $request->id_tiponotificacion;
        $UsuarioSinNotificacionCorreo->activo = 1;
        $UsuarioSinNotificacionCorreo->eliminado = 0;
        $UsuarioSinNotificacionCorreo->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario_s;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'UsuarioSinNotificacionCorreo';
        $bitacora->parametros_nuevos = 'ID '.$UsuarioSinNotificacionCorreo->id_usuariosinnotificacioncorreo;
        $bitacora->save();
                
        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $UsuarioSinNotificacionCorreo = UsuarioSinNotificacionCorreo::find($id);
            $datosAnteriores = json_encode($UsuarioSinNotificacionCorreo,true);
            
            $UsuarioSinNotificacionCorreo->activo = $request->activo;
            $UsuarioSinNotificacionCorreo->eliminado = 0;
            $UsuarioSinNotificacionCorreo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioSinNotificacionCorreo';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $UsuarioSinNotificacionCorreo = UsuarioSinNotificacionCorreo::find($id);
            $UsuarioSinNotificacionCorreo->eliminado = 1;
            $UsuarioSinNotificacionCorreo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'UsuarioSinNotificacionCorreo';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $UsuarioSinNotificacionCorreo->delete();

        return response()->json(null, 204);
    }
}

