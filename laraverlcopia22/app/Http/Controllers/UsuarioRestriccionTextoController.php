<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioRestriccionTexto;
use App\Models\Flujos;
use App\Models\Bitacora;
use Carbon\Carbon;

class UsuarioRestriccionTextoController extends Controller
{
    public function index()
    {
        $UsuarioRestriccionTexto = UsuarioRestriccionTexto::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'UsuarioRestriccionTexto.id_usuario');
        })->selectRaw(
            "
            UsuarioRestriccionTexto.id_usuariorestricciontexto,
            UsuarioRestriccionTexto.id_usuario,
            UsuarioRestriccionTexto.texto,
            UsuarioRestriccionTexto.activo,
            UsuarioRestriccionTexto.eliminado,
            usrCon.nombre_usuario as usuario_con,
            usrCon.nombre as nombre_con,
            usrCon.apellido as apellido_con
            "
        )->where('UsuarioRestriccionTexto.activo',1)
        ->where('UsuarioRestriccionTexto.eliminado', 0)
        ->where('usrCon.activo',1)
        ->where('usrCon.eliminado', 0)->get();
        $datos = array();
        $datos['restriccion'] = $UsuarioRestriccionTexto;
        return $datos;
    }

    public function show($id)
    {
        $UsuarioRestriccionTexto = UsuarioRestriccionTexto::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'UsuarioRestriccionTexto.id_usuario');
        })->selectRaw(
            "
            UsuarioRestriccionTexto.id_usuariorestricciontexto,
            UsuarioRestriccionTexto.id_usuario,
            UsuarioRestriccionTexto.texto,
            UsuarioRestriccionTexto.activo,
            UsuarioRestriccionTexto.eliminado,
            usrCon.nombre_usuario as usuario_con,
            usrCon.nombre as nombre_con,
            usrCon.apellido as apellido_con
            "
        )->where('UsuarioRestriccionTexto.id_usuario',$id)
        ->where('UsuarioRestriccionTexto.activo',1)
        ->where('UsuarioRestriccionTexto.eliminado', 0)
        ->where('usrCon.activo',1)
        ->where('usrCon.eliminado', 0)->get();
        $datos = array();
        $datos['restriccion'] = $UsuarioRestriccionTexto;
        return $datos;
    }

    public function store(Request $request)
    {
        $UsuarioRestriccionTexto = new UsuarioRestriccionTexto;
        $UsuarioRestriccionTexto->id_usuario = $request->id_usuario;
        $UsuarioRestriccionTexto->texto = $request->texto;
        $UsuarioRestriccionTexto->activo = 1;
        $UsuarioRestriccionTexto->eliminado = 0;
        $UsuarioRestriccionTexto->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario_s;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'UsuarioRestriccionTexto';
        $bitacora->parametros_nuevos = 'ID '.$UsuarioRestriccionTexto->id_usuariorestricciontexto;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $UsuarioRestriccionTexto = UsuarioRestriccionTexto::find($id);
            $datosAnteriores = json_encode($UsuarioRestriccionTexto,true);
            $UsuarioRestriccionTexto->id_usuario = $request->id_usuario;
            $UsuarioRestriccionTexto->texto = $request->texto;
            $UsuarioRestriccionTexto->activo = $request->activo;
            $UsuarioRestriccionTexto->eliminado = 0;
            $UsuarioRestriccionTexto->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioRestriccionTexto';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $UsuarioRestriccionTexto = UsuarioRestriccionTexto::find($id);
            $UsuarioRestriccionTexto->eliminado = 1;
            $UsuarioRestriccionTexto->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'UsuarioRestriccionTexto';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(UsuarioRestriccionTexto $UsuarioRestriccionTexto)
    {
        $UsuarioRestriccionTexto->delete();

        return response()->json(null, 204);
    }
}
