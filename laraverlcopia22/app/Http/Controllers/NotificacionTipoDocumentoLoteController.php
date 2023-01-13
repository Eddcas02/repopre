<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotificacionTipoDocumentoLote;
use App\Models\Flujos;
use App\Models\Bitacora;
use Carbon\Carbon;

class NotificacionTipoDocumentoLoteController extends Controller
{
    public function index()
    {
        $NotificacionTipoDocumentoLote = NotificacionTipoDocumentoLote::where('eliminado',0)->get();
        $datos = array();
        $datos['notificaciontipodocumentolote'] = $NotificacionTipoDocumentoLote;
        return $datos;
    }

    public function show(NotificacionTipoDocumentoLote $NotificacionTipoDocumentoLote)
    {
        return $NotificacionTipoDocumentoLote;
    }

    public function store(Request $request)
    {
        $NotificacionTipoDocumentoLote = new NotificacionTipoDocumentoLote;
        $NotificacionTipoDocumentoLote->id_usuarionotificaciontransaccion = $request->id_usuarionotificaciontransaccion;
        $NotificacionTipoDocumentoLote->id_tipodocumentolote = $request->id_tipodocumentolote;
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

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $NotificacionTipoDocumentoLote = NotificacionTipoDocumentoLote::find($id);
            $datosAnteriores = json_encode($NotificacionTipoDocumentoLote,true);
            $NotificacionTipoDocumentoLote->id_usuarionotificaciontransaccion = $request->id_usuarionotificaciontransaccion;
            $NotificacionTipoDocumentoLote->id_tipodocumentolote = $request->id_tipodocumentolote;
            $NotificacionTipoDocumentoLote->activo = $request->activo;
            $NotificacionTipoDocumentoLote->eliminado = 0;
            $NotificacionTipoDocumentoLote->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->idUsuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'NotificacionTipoDocumentoLote';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $NotificacionTipoDocumentoLote = NotificacionTipoDocumentoLote::find($id);
            $NotificacionTipoDocumentoLote->eliminado = 1;
            $NotificacionTipoDocumentoLote->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->idUsuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'NotificacionTipoDocumentoLote';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(NotificacionTipoDocumentoLote $NotificacionTipoDocumentoLote)
    {
        $NotificacionTipoDocumentoLote->delete();

        return response()->json(null, 204);
    }
}
