<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioGrupo;
use App\Models\UsuarioAutorizacion;
use App\Models\Bitacora;
use Carbon\Carbon;

class UsuarioGrupoController extends Controller
{
    public function index()
    {
        $usuariogrupo = UsuarioGrupo::join('GrupoAutorizacion', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion',
                '=',
                'GrupoAutorizacion.id_grupoautorizacion'
            );
        })
        ->selectRaw(
            "UsuarioGrupoAutorizacion.id_usuariogrupo,
             UsuarioGrupoAutorizacion.id_usuario,
             UsuarioGrupoAutorizacion.id_grupoautorizacion,
             GrupoAutorizacion.identificador,
             UsuarioGrupoAutorizacion.nivel,
             UsuarioGrupoAutorizacion.activo,
             UsuarioGrupoAutorizacion.eliminado"
        )
        ->orderBy('UsuarioGrupoAutorizacion.id_usuariogrupo')
        ->get();
        $datos = array();
        $datos['detalle'] = $usuariogrupo;
        return $datos;
    }

    public function show($id)
    {
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
            ->where('UsuarioAutorizacion.id_usuariotemporal', $id)
            ->where('UsuarioAutorizacion.fecha_inicio','<=', 
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
            ->where('UsuarioAutorizacion.fecha_final', '>=',
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $IdUsuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $IdUsuario = $item['id_usuarioaprobador'];
            }
        }else{
            $IdUsuario = $id;
        }

        $usuariogrupo = UsuarioGrupo::join('GrupoAutorizacion', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion',
                '=',
                'GrupoAutorizacion.id_grupoautorizacion'
            );
        })
        ->selectRaw(
            "UsuarioGrupoAutorizacion.id_usuariogrupo,
             UsuarioGrupoAutorizacion.id_usuario,
             UsuarioGrupoAutorizacion.id_grupoautorizacion,
             GrupoAutorizacion.identificador,
             GrupoAutorizacion.descripcion,
             UsuarioGrupoAutorizacion.nivel,
             UsuarioGrupoAutorizacion.activo,
             UsuarioGrupoAutorizacion.eliminado"
        )
        ->where('UsuarioGrupoAutorizacion.id_usuario', $IdUsuario)
        ->where('UsuarioGrupoAutorizacion.activo', 1)
        ->where('UsuarioGrupoAutorizacion.eliminado', 0)
        ->orderBy('UsuarioGrupoAutorizacion.id_usuariogrupo')
        ->get();
        $datos = array();
        $datos['detalle'] = $usuariogrupo;
        return $datos;
    }

    public function UsuariosPorGrupo($id,$grupo){

        $datosUsuario = UsuarioGrupo::where('id_usuario',$id)
        ->where('id_grupoautorizacion',$grupo)
        ->where('activo',1)->where('eliminado',0)->first();

        $usuariosGrupo = UsuarioGrupo::join('usuarios', function($join){
            $join->on('usuarios.id_usuario','=','UsuarioGrupoAutorizacion.id_usuario');
        })
        ->selectRaw(
            "
            UsuarioGrupoAutorizacion.id_usuario,
            usuarios.nombre_usuario as usuario,
            usuarios.nombre as nombre,
            usuarios.apellido as apellido
            "
        )
        ->where('UsuarioGrupoAutorizacion.id_usuario','<>',$id)
        ->where('UsuarioGrupoAutorizacion.id_grupoautorizacion',$grupo)
        ->where('UsuarioGrupoAutorizacion.nivel',$datosUsuario->nivel)
        ->where('UsuarioGrupoAutorizacion.activo',1)->where('UsuarioGrupoAutorizacion.eliminado',0)
        ->get();

        $datos = array();
        $datos['usuarios'] = $usuariosGrupo;
        return $datos;
    }

    public function store(Request $request)
    {
        $usuarios = UsuarioGrupo::join('usuarios', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_usuario', '=', 'usuarios.id_usuario');
        })
        ->select('usuarios.nombre_usuario')
        ->where('UsuarioGrupoAutorizacion.id_usuario', $request->id_usuario)
        ->where('UsuarioGrupoAutorizacion.id_grupoautorizacion', $request->id_grupoautorizacion)
        ->where('usuarios.eliminado', 0)->where('usuarios.activo', 1)
        ->where('UsuarioGrupoAutorizacion.eliminado', 0)->where('UsuarioGrupoAutorizacion.activo', 1)
        ->get();
        if ($usuarios->count() > 0) {
            return response()->json("Repetido");        
        }else{
            $usuariogrupo = new UsuarioGrupo;
            $usuariogrupo->id_usuario = $request->id_usuario;
            $usuariogrupo->id_grupoautorizacion = $request->id_grupoautorizacion;
            $usuariogrupo->nivel = $request->nivel;
            $usuariogrupo->activo = 1;
            $usuariogrupo->eliminado = 0;
            $usuariogrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'crear';
            $bitacora->objeto = 'UsuarioGrupo';
            $bitacora->parametros_nuevos = 'ID '.$usuariogrupo->id_usuariogrupo;
            $bitacora->save();
    
            return response()->json("OK");
        }
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $usuariogrupo = UsuarioGrupo::find($id);
            $usuariogrupo->eliminado = 1;
            $usuariogrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'UsuarioGrupo';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $usuariogrupo = UsuarioGrupo::find($id);
            $datosAnteriores = json_encode($usuariogrupo,true);
            $usuariogrupo->id_usuario = $request->id_usuario;
            $usuariogrupo->id_grupoautorizacion = $request->id_grupoautorizacion;
            $usuariogrupo->nivel = $request->nivel;
            $usuariogrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioGrupo';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK");
        } else if ($opcion == '3') {
            $usuariogrupo = UsuarioGrupo::find($id);
            $datosAnteriores = json_encode($usuariogrupo,true);
            $usuariogrupo->activo = $request->activo;
            $usuariogrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioGrupo';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK");
        } 
    }

    public function delete(Request $request)
    {
        $usuariogrupo->delete();

        return response()->json(null, 204);
    }
}
