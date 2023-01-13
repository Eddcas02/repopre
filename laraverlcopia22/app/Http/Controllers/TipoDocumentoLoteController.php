<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoDocumentoLote;
use App\Models\Flujos;
use App\Models\Bitacora;
use Carbon\Carbon;

class TipoDocumentoLoteController extends Controller
{
    public function index()
    {
        $TipoDocumentoLote = TipoDocumentoLote::where('eliminado',0)->get();
        $datos = array();
        $datos['tipodocumentolote'] = $TipoDocumentoLote;
        return $datos;
    }

    public function show(TipoDocumentoLote $TipoDocumentoLote)
    {
        return $TipoDocumentoLote;
    }

    public function store(Request $request)
    {
        $TipoDocumentoLote = new TipoDocumentoLote;
        $TipoDocumentoLote->Descripcion = $request->Descripcion;
        $TipoDocumentoLote->activo = 1;
        $TipoDocumentoLote->eliminado = 0;
        $TipoDocumentoLote->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'TipoDocumentoLote';
        $bitacora->parametros_nuevos = 'ID '.$TipoDocumentoLote->id_tipodocumentolote;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $TipoDocumentoLote = TipoDocumentoLote::find($id);
            $datosAnteriores = json_encode($TipoDocumentoLote,true);
            $TipoDocumentoLote->Descripcion = $request->Descripcion;
            $TipoDocumentoLote->activo = $request->activo;
            $TipoDocumentoLote->eliminado = 0;
            $TipoDocumentoLote->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'TipoDocumentoLote';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $TipoDocumentoLote = TipoDocumentoLote::find($id);
            $TipoDocumentoLote->eliminado = 1;
            $TipoDocumentoLote->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'TipoDocumentoLote';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(TipoDocumentoLote $TipoDocumentoLote)
    {
        $TipoDocumentoLote->delete();

        return response()->json(null, 204);
    }
}
