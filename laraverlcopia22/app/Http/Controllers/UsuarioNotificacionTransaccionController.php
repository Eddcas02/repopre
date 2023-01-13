<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioNotificacionTransaccion;
use App\Models\Flujos;
use App\Models\Bitacora;
use Carbon\Carbon;
use App\Models\NotificacionTipoDocumentoLote;

class UsuarioNotificacionTransaccionController extends Controller
{
    public function index()
    {
        $UsuarioNotificacionTransaccion = UsuarioNotificacionTransaccion::join('usuarios', function($join){
            $join->on('UsuarioNotificacionTransaccion.id_usuario', '=', 'usuarios.id_usuario');
        })->join('NotificacionTipoDocumentoLote', function($join){
            $join->on('UsuarioNotificacionTransaccion.id_usuarionotificaciontransaccion', '=', 'NotificacionTipoDocumentoLote.id_usuarionotificaciontransaccion');
        })->join('TipoDocumentoLote', function($join){
            $join->on('NotificacionTipoDocumentoLote.id_tipodocumentolote', '=', 'TipoDocumentoLote.id_tipodocumentolote');
        })
        ->selectRaw(
            "
            UsuarioNotificacionTransaccion.id_usuarionotificaciontransaccion,
            UsuarioNotificacionTransaccion.TipoTransaccion,
            usuarios.id_usuario,
            usuarios.correo,
            usuarios.nombre,
            usuarios.apellido,
            NotificacionTipoDocumentoLote.id_notificaciontipodocumentolote,
            TipoDocumentoLote.id_tipodocumentolote,
            TipoDocumentoLote.Descripcion as tipoDocumento
            "
        )
        ->where('UsuarioNotificacionTransaccion.Activo',1)->where('UsuarioNotificacionTransaccion.Eliminado',0)
        ->where('usuarios.activo',1)->where('usuarios.eliminado',0)
        ->where('NotificacionTipoDocumentoLote.Activo',1)->where('NotificacionTipoDocumentoLote.Eliminado',0)
        ->where('TipoDocumentoLote.Activo',1)->where('TipoDocumentoLote.Eliminado',0)
        ->orderBy('UsuarioNotificacionTransaccion.id_usuario', 'ASC')
        ->get();
        $datos = array();
        $datos['UsuarioNotificacionTransaccion'] = $UsuarioNotificacionTransaccion;
        return $datos;
    }

    public function show($id)
    {
        $UsuarioNotificacionTransaccion = UsuarioNotificacionTransaccion::join('usuarios', function($join){
            $join->on('UsuarioNotificacionTransaccion.id_usuario', '=', 'usuarios.id_usuario');
        })->join('NotificacionTipoDocumentoLote', function($join){
            $join->on('UsuarioNotificacionTransaccion.id_usuarionotificaciontransaccion', '=', 'NotificacionTipoDocumentoLote.id_usuarionotificaciontransaccion');
        })->join('TipoDocumentoLote', function($join){
            $join->on('NotificacionTipoDocumentoLote.id_tipodocumentolote', '=', 'TipoDocumentoLote.id_tipodocumentolote');
        })
        ->selectRaw(
            "
            UsuarioNotificacionTransaccion.id_usuarionotificaciontransaccion,
            UsuarioNotificacionTransaccion.TipoTransaccion,
            usuarios.id_usuario,
            usuarios.correo,
            usuarios.nombre,
            usuarios.apellido,
            NotificacionTipoDocumentoLote.id_notificaciontipodocumentolote,
            TipoDocumentoLote.id_tipodocumentolote,
            TipoDocumentoLote.Descripcion as tipoDocumento
            "
        )
        ->where('UsuarioNotificacionTransaccion.Activo',1)->where('UsuarioNotificacionTransaccion.Eliminado',0)
        ->where('usuarios.activo',1)->where('usuarios.eliminado',0)
        ->where('NotificacionTipoDocumentoLote.Activo',1)->where('NotificacionTipoDocumentoLote.Eliminado',0)
        ->where('TipoDocumentoLote.Activo',1)->where('TipoDocumentoLote.Eliminado',0)
        ->where('UsuarioNotificacionTransaccion.id_usuario', '=', $id)
        ->orderBy('UsuarioNotificacionTransaccion.id_usuario', 'ASC')
        ->get();
        $datos = array();
        $datos['UsuarioNotificacionTransaccion'] = $UsuarioNotificacionTransaccion;
        return $datos;
    }

    public function store(Request $request)
    {
        $partesDocumento = explode("|", $request->ConfiguracionDocumentos);

        foreach($partesDocumento as $items){
            $partesItems = explode(",",$items);
            $existeRegistro = UsuarioNotificacionTransaccion::where('id_usuario','=',$request->id_usuario)
            ->where('TipoTransaccion','=',$partesItems[0])
            ->where('activo',1)->where('eliminado',0)
            ->first();
            $id_usuarionotificaciontransaccion = 0;

            if(!$existeRegistro)
            {
                $UsuarioNotificacionTransaccion = new UsuarioNotificacionTransaccion;
                $UsuarioNotificacionTransaccion->id_usuario = $request->id_usuario;
                $UsuarioNotificacionTransaccion->TipoTransaccion = $partesItems[0];
                $UsuarioNotificacionTransaccion->activo = 1;
                $UsuarioNotificacionTransaccion->eliminado = 0;
                $UsuarioNotificacionTransaccion->save();
                $id_usuarionotificaciontransaccion = $UsuarioNotificacionTransaccion->id_usuarionotificaciontransaccion;
    
                $fechaActual = Carbon::now('America/Guatemala');
                $bitacora = new Bitacora;
                $bitacora->id_usuario = $request->idUsuario;
                $bitacora->fecha_hora = $fechaActual;
                $bitacora->accion = 'crear';
                $bitacora->objeto = 'UsuarioNotificacionTransaccion';
                $bitacora->parametros_nuevos = 'ID '.$UsuarioNotificacionTransaccion->id_usuarionotificaciontransaccion;
                $bitacora->save();
            }else{
                $id_usuarionotificaciontransaccion = $existeRegistro->id_usuarionotificaciontransaccion;
            }

            $contadorPartes = 0;
            foreach($partesItems as $documentos){
                if($contadorPartes > 0){
                    $NotificacionTipoDocumentoLote = new NotificacionTipoDocumentoLote;
                    $NotificacionTipoDocumentoLote->id_usuarionotificaciontransaccion = $id_usuarionotificaciontransaccion;
                    $NotificacionTipoDocumentoLote->id_tipodocumentolote = $documentos;
                    $NotificacionTipoDocumentoLote->activo = 1;
                    $NotificacionTipoDocumentoLote->eliminado = 0;
                    $NotificacionTipoDocumentoLote->save();

                    $fechaActual = Carbon::now('America/Guatemala');
                    $bitacora = new Bitacora;
                    $bitacora->id_usuario = $request->idUsuario;
                    $bitacora->fecha_hora = $fechaActual;
                    $bitacora->accion = 'crear';
                    $bitacora->objeto = 'NotificacionTipoDocumentoLote';
                    $bitacora->parametros_nuevos = 'ID '.$NotificacionTipoDocumentoLote->id_notificaciontipodocumentolote;
                    $bitacora->save();
                }
                $contadorPartes++;
            }
        }

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $UsuarioNotificacionTransaccion = UsuarioNotificacionTransaccion::find($id);
            $datosAnteriores = json_encode($UsuarioNotificacionTransaccion,true);
            $UsuarioNotificacionTransaccion->id_usuario = $request->id_usuario;
            $UsuarioNotificacionTransaccion->TipoTransaccion = $request->TipoTransaccion;
            $UsuarioNotificacionTransaccion->activo = $request->activo;
            $UsuarioNotificacionTransaccion->eliminado = 0;
            $UsuarioNotificacionTransaccion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->idUsuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioNotificacionTransaccion';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $UsuarioNotificacionTransaccion = UsuarioNotificacionTransaccion::find($id);
            $UsuarioNotificacionTransaccion->eliminado = 1;
            $UsuarioNotificacionTransaccion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->idUsuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'UsuarioNotificacionTransaccion';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(UsuarioNotificacionTransaccion $UsuarioNotificacionTransaccion)
    {
        $UsuarioNotificacionTransaccion->delete();

        return response()->json(null, 204);
    }
}
