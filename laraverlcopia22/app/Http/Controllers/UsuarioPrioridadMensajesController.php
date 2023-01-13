<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioPrioridadMensajes;
use App\Models\Flujos;
use App\Models\UsuarioGrupo;
use App\Models\Bitacora;
use Carbon\Carbon;

class UsuarioPrioridadMensajesController extends Controller
{
    public function index($id)
    {
        $usuarioPrioridadMensajes = UsuarioPrioridadMensajes::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'UsuarioPrioridadMensajes.id_usuario');
        })->join('usuarios as usrPri', function($join){
            $join->on('usrPri.id_usuario', '=', 'UsuarioPrioridadMensajes.id_usuario_prioridad');
        })->selectRaw(
            "
            UsuarioPrioridadMensajes.id_usuarioprioridadmensajes,
            UsuarioPrioridadMensajes.id_usuario,
            usrCon.nombre_usuario as usuario_con,
            usrCon.nombre as nombre_con,
            usrCon.apellido as apellido_con,
            UsuarioPrioridadMensajes.id_usuario_prioridad,
            usrPri.nombre_usuario as usuario_pri,
            usrPri.nombre as nombre_pri,
            usrPri.apellido as apellido_pri,
            UsuarioPrioridadMensajes.nivel,
            UsuarioPrioridadMensajes.activo,
            UsuarioPrioridadMensajes.eliminado
            "
        )->where('UsuarioPrioridadMensajes.id_usuario','=',$id)
        ->where('UsuarioPrioridadMensajes.activo',1)
        ->where('UsuarioPrioridadMensajes.eliminado', 0)
        ->orderBy('UsuarioPrioridadMensajes.nivel')->get();
        $datos = array();
        $datos['prioridad'] = $usuarioPrioridadMensajes;
        return $datos;
    }

    public function store(Request $request)
    {
        $ultimo = UsuarioPrioridadMensajes::where('id_usuario','=',$request->id_usuario)
        ->where('activo',1)->where('eliminado',0)->max('nivel');
        if($ultimo == null){
            $ultimo = 0;
        }
        $ultimo = $ultimo + 1;
        $usuarioPrioridadMensajes = new UsuarioPrioridadMensajes;
        $usuarioPrioridadMensajes->id_usuario = $request->id_usuario;
        $usuarioPrioridadMensajes->id_usuario_prioridad = $request->id_usuario_prioridad;
        $usuarioPrioridadMensajes->nivel = $ultimo;
        $usuarioPrioridadMensajes->activo = 1;
        $usuarioPrioridadMensajes->eliminado = 0;
        $usuarioPrioridadMensajes->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario_s;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'UsuarioPrioridadMensajes';
        $bitacora->parametros_nuevos = 'ID '.$usuarioPrioridadMensajes->id_usuarioprioridadmensajes;
        $bitacora->save();
                
        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $usuarioPrioridadMensajes = UsuarioPrioridadMensajes::find($id);
            $datosAnteriores = json_encode($usuarioPrioridadMensajes,true);
            if($usuarioPrioridadMensajes->nivel == $request->nivel){
                $usuarioPrioridadMensajes->activo = $request->activo;
                $usuarioPrioridadMensajes->eliminado = 0;
                $usuarioPrioridadMensajes->save();
            }else{
                $PuestoAnterior = UsuarioPrioridadMensajes::where('activo',1)->where('eliminado',0)
                ->where('id_usuario','=', $usuarioPrioridadMensajes->id_usuario)
                ->where('nivel','=',$request->nivel)->first();
                if($PuestoAnterior != null){
                    $PuestoAnterior->nivel = $usuarioPrioridadMensajes->nivel;
                    $PuestoAnterior->save();

                    $usuarioPrioridadMensajes->nivel=$request->nivel;
                    $usuarioPrioridadMensajes->save();
                }
            }

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioPrioridadMensajes';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $usuarioPrioridadMensajes = UsuarioPrioridadMensajes::find($id);
            $usuarioPrioridadMensajes->eliminado = 1;
            $usuarioPrioridadMensajes->save();

            $listaUsuarios = UsuarioPrioridadMensajes::where('activo',1)
            ->where('eliminado',0)->where('id_usuario','=',$usuarioPrioridadMensajes->id_usuario)
            ->orderBy('nivel')->get();
            $contador = 0;
            foreach($listaUsuarios as $item){
                $contador++;
                $item->nivel = $contador;
                $item->save();
            }

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'UsuarioPrioridadMensajes';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $usuarioPrioridadMensajes->delete();

        return response()->json(null, 204);
    }
}

