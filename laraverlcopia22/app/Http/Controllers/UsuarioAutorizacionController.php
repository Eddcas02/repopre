<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioAutorizacion;
use App\Models\Bitacora;
use Carbon\Carbon;

class UsuarioAutorizacionController extends Controller
{
    public function index()
    { 
        $usuarioautorizacion = UsuarioAutorizacion::join('usuarios', function($join){
            $join->on('usuarios.id_usuario',
                '=',
                'UsuarioAutorizacion.id_usuariotemporal'
            );
        })
        ->selectRaw(
            "UsuarioAutorizacion.id_usuarioautorizacion,
             UsuarioAutorizacion.id_usuarioaprobador,
             UsuarioAutorizacion.id_usuariotemporal,
             concat(usuarios.nombre, ' ', usuarios.apellido) as usuariotemporal,
             DATE_FORMAT(UsuarioAutorizacion.fecha_inicio, '%d-%m-%Y') as fecha_inicio,
             DATE_FORMAT(UsuarioAutorizacion.fecha_final, '%d-%m-%Y') as fecha_final,
             UsuarioAutorizacion.activo,
             UsuarioAutorizacion.eliminado"
        )
        ->orderBy('UsuarioAutorizacion.id_usuarioautorizacion')
        ->get();
        $datos = array();
        $datos['autorizacion'] = $usuarioautorizacion;
        return $datos;
    }

    public function show($id)
    { 
        $usuarioautorizacion = UsuarioAutorizacion::join('usuarios', function($join){
            $join->on('usuarios.id_usuario',
                '=',
                'UsuarioAutorizacion.id_usuariotemporal'
            );
        })
        ->selectRaw(
            "UsuarioAutorizacion.id_usuarioautorizacion,
             UsuarioAutorizacion.id_usuarioaprobador,
             UsuarioAutorizacion.id_usuariotemporal,
             concat(usuarios.nombre, ' ', usuarios.apellido) as usuariotemporal,
             DATE_FORMAT(UsuarioAutorizacion.fecha_inicio, '%d-%m-%Y') as fecha_inicio,
             DATE_FORMAT(UsuarioAutorizacion.fecha_final, '%d-%m-%Y') as fecha_final,
             UsuarioAutorizacion.activo,
             UsuarioAutorizacion.eliminado"
        )
        ->where('UsuarioAutorizacion.id_usuarioaprobador', $id)
        ->where('UsuarioAutorizacion.fecha_final', '>=', now())
        ->orderBy('UsuarioAutorizacion.id_usuarioautorizacion')
        ->get();
        $datos = array();
        $datos['autorizacion'] = $usuarioautorizacion;
        return $datos;
    }

    public function store(Request $request)
    {
        $encurso_aprobador = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioautorizacion')
        ->where('UsuarioAutorizacion.fecha_inicio','<=', 
          date("Y-m-d", strtotime('-6 hour', strtotime($request->fecha_inicio))))
        ->where('UsuarioAutorizacion.fecha_final', '>=',
          date("Y-m-d", strtotime('-6 hour', strtotime($request->fecha_inicio))))
        ->orWhere('UsuarioAutorizacion.fecha_inicio','<=', 
          date("Y-m-d", strtotime('-6 hour', strtotime($request->fecha_final))))
        ->where('UsuarioAutorizacion.fecha_final', '>=',
          date("Y-m-d", strtotime('-6 hour', strtotime($request->fecha_final))))
        ->where('UsuarioAutorizacion.id_usuarioaprobador', $request->id_usuarioaprobador)->get();

        $encurso_temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioautorizacion')
        ->where('UsuarioAutorizacion.fecha_inicio','<=', 
          date("Y-m-d", strtotime('-6 hour', strtotime($request->fecha_inicio))))
        ->where('UsuarioAutorizacion.fecha_final', '>=',
          date("Y-m-d", strtotime('-6 hour', strtotime($request->fecha_inicio))))
        ->orWhere('UsuarioAutorizacion.fecha_inicio','<=', 
          date("Y-m-d", strtotime('-6 hour', strtotime($request->fecha_final))))
        ->where('UsuarioAutorizacion.fecha_final', '>=',
          date("Y-m-d", strtotime('-6 hour', strtotime($request->fecha_final))))
        ->where('UsuarioAutorizacion.id_usuariotemporal', $request->id_usuariotemporal)->get();

        if($encurso_aprobador->count() > 0 || $encurso_temporal->count() > 0){
            return response()->json("Existe");
        }else{
            $usuarioautorizacion = new UsuarioAutorizacion;
            $usuarioautorizacion->id_usuarioaprobador = $request->id_usuarioaprobador;
            $usuarioautorizacion->id_usuariotemporal = $request->id_usuariotemporal;
            $usuarioautorizacion->fecha_inicio = 
            date("Y-m-d H:i",strtotime('-6 hour',strtotime($request->fecha_inicio)));
            $usuarioautorizacion->fecha_final = 
            date("Y-m-d H:i",strtotime('-6 hour',strtotime($request->fecha_final)));
            $usuarioautorizacion->activo = 1;
            $usuarioautorizacion->eliminado = 0;
            $usuarioautorizacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'crear';
            $bitacora->objeto = 'UsuarioAutorizacion';
            $bitacora->parametros_nuevos = 'ID '.$usuarioautorizacion->id_usuarioautorizacion;
            $bitacora->save();
    
            return response()->json("OK");
        }
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $usuarioautorizacion = UsuarioAutorizacion::find($id);
            $datosAnteriores = json_encode($usuarioautorizacion,true);
            $usuarioautorizacion->activo = $request->activo;
            $usuarioautorizacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioAutorizacion';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $usuarioautorizacion->delete();

        return response()->json(null, 204);
    }
}
