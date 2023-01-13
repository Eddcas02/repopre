<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OcultarColumnaUsuario;
use App\Models\Bitacora;
use Carbon\Carbon;

class OcultarColumnaUsuarioController extends Controller
{
    public function index()
    {
        $OcultarColumnaUsuario = OcultarColumnaUsuario::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'OcultarColumnaUsuario.id_usuario');
        })->selectRaw(
            "
            OcultarColumnaUsuario.id_ocultarcolumnausuario,
            OcultarColumnaUsuario.id_usuario,
            usrCon.nombre_usuario as usuario,
            usrCon.nombre as nombre,
            usrCon.apellido as apellido,
            OcultarColumnaUsuario.NombreColumna,
            OcultarColumnaUsuario.activo,
            OcultarColumnaUsuario.eliminado
            "
        )->where('OcultarColumnaUsuario.activo',1)
        ->where('OcultarColumnaUsuario.eliminado', 0)->get();
        $datos = array();
        $datos['ocultar'] = $OcultarColumnaUsuario;
        return $datos;
    }

    public function show($id)
    {
        $OcultarColumnaUsuario = OcultarColumnaUsuario::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'OcultarColumnaUsuario.id_usuario');
        })->selectRaw(
            "
            OcultarColumnaUsuario.id_ocultarcolumnausuario,
            OcultarColumnaUsuario.id_usuario,
            usrCon.nombre_usuario as usuario,
            usrCon.nombre as nombre,
            usrCon.apellido as apellido,
            OcultarColumnaUsuario.NombreColumna,
            OcultarColumnaUsuario.activo,
            OcultarColumnaUsuario.eliminado
            "
        )->where('OcultarColumnaUsuario.id_usuario',$id)
        ->where('OcultarColumnaUsuario.activo',1)
        ->where('OcultarColumnaUsuario.eliminado', 0)->get();
        $datos = array();
        $datos['ocultar'] = $OcultarColumnaUsuario;
        return $datos;
    }

    public function store(Request $request)
    {
        $OcultarColumnaUsuario = new OcultarColumnaUsuario;
        $OcultarColumnaUsuario->id_usuario = $request->id_usuario;
        $OcultarColumnaUsuario->NombreColumna = $request->NombreColumna;
        $OcultarColumnaUsuario->activo = 1;
        $OcultarColumnaUsuario->eliminado = 0;
        $OcultarColumnaUsuario->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario_s;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'OcultarColumnaUsuario';
        $bitacora->parametros_nuevos = 'ID '.$OcultarColumnaUsuario->id_ocultarcolumnausuario;
        $bitacora->save();
                
        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $OcultarColumnaUsuario = OcultarColumnaUsuario::find($id);
            $datosAnteriores = json_encode($OcultarColumnaUsuario,true);
            $OcultarColumnaUsuario->activo = $request->activo;
            $OcultarColumnaUsuario->eliminado = 0;
            $OcultarColumnaUsuario->save();
            

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'OcultarColumnaUsuario';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $OcultarColumnaUsuario = OcultarColumnaUsuario::find($id);
            $OcultarColumnaUsuario->eliminado = 1;
            $OcultarColumnaUsuario->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'OcultarColumnaUsuario';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $OcultarColumnaUsuario->delete();

        return response()->json(null, 204);
    }
}

