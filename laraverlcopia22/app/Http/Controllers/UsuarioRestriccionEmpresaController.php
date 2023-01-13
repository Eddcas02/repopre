<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioRestriccionEmpresa;
use App\Models\Flujos;
use App\Models\Bitacora;
use Carbon\Carbon;

class UsuarioRestriccionEmpresaController extends Controller
{
    public function index()
    {
        $UsuarioRestriccionEmpresa = UsuarioRestriccionEmpresa::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'UsuarioRestriccionEmpresa.id_usuario');
        })->selectRaw(
            "
            UsuarioRestriccionEmpresa.id_usuariorestriccionempresa,
            UsuarioRestriccionEmpresa.id_usuario,
            UsuarioRestriccionEmpresa.empresa_codigo,
            UsuarioRestriccionEmpresa.empresa_nombre,
            UsuarioRestriccionEmpresa.activo,
            UsuarioRestriccionEmpresa.eliminado,
            usrCon.nombre_usuario as usuario_con,
            usrCon.nombre as nombre_con,
            usrCon.apellido as apellido_con
            "
        )->where('UsuarioRestriccionEmpresa.activo',1)
        ->where('UsuarioRestriccionEmpresa.eliminado', 0)
        ->where('usrCon.activo',1)
        ->where('usrCon.eliminado', 0)->get();
        $datos = array();
        $datos['restriccion'] = $UsuarioRestriccionEmpresa;
        return $datos;
    }

    public function show($id)
    {
        $UsuarioRestriccionEmpresa = UsuarioRestriccionEmpresa::join('usuarios as usrCon', function($join){
            $join->on('usrCon.id_usuario', '=', 'UsuarioRestriccionEmpresa.id_usuario');
        })->selectRaw(
            "
            UsuarioRestriccionEmpresa.id_usuariorestriccionempresa,
            UsuarioRestriccionEmpresa.id_usuario,
            UsuarioRestriccionEmpresa.empresa_codigo,
            UsuarioRestriccionEmpresa.empresa_nombre,
            UsuarioRestriccionEmpresa.activo,
            UsuarioRestriccionEmpresa.eliminado,
            usrCon.nombre_usuario as usuario_con,
            usrCon.nombre as nombre_con,
            usrCon.apellido as apellido_con
            "
        )->where('UsuarioRestriccionEmpresa.id_usuario',$id)
        ->where('UsuarioRestriccionEmpresa.activo',1)
        ->where('UsuarioRestriccionEmpresa.eliminado', 0)
        ->where('usrCon.activo',1)
        ->where('usrCon.eliminado', 0)->get();
        $datos = array();
        $datos['restriccion'] = $UsuarioRestriccionEmpresa;
        return $datos;
    }

    public function store(Request $request)
    {
        $UsuarioRestriccionEmpresa = new UsuarioRestriccionEmpresa;
        $UsuarioRestriccionEmpresa->id_usuario = $request->id_usuario;
        $UsuarioRestriccionEmpresa->empresa_codigo = $request->empresa_codigo;
        $UsuarioRestriccionEmpresa->empresa_nombre = $request->empresa_nombre;
        $UsuarioRestriccionEmpresa->activo = 1;
        $UsuarioRestriccionEmpresa->eliminado = 0;
        $UsuarioRestriccionEmpresa->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario_s;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'UsuarioRestriccionEmpresa';
        $bitacora->parametros_nuevos = 'ID '.$UsuarioRestriccionEmpresa->id_usuariorestriccionempresa;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $UsuarioRestriccionEmpresa = UsuarioRestriccionEmpresa::find($id);
            $datosAnteriores = json_encode($UsuarioRestriccionEmpresa,true);
            $UsuarioRestriccionEmpresa->id_usuario = $request->id_usuario;
            $UsuarioRestriccionEmpresa->empresa_codigo = $request->empresa_codigo;
            $UsuarioRestriccionEmpresa->empresa_nombre = $request->empresa_nombre;
            $UsuarioRestriccionEmpresa->activo = $request->activo;
            $UsuarioRestriccionEmpresa->eliminado = 0;
            $UsuarioRestriccionEmpresa->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioRestriccionEmpresa';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $UsuarioRestriccionEmpresa = UsuarioRestriccionEmpresa::find($id);
            $UsuarioRestriccionEmpresa->eliminado = 1;
            $UsuarioRestriccionEmpresa->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'UsuarioRestriccionEmpresa';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(UsuarioRestriccionEmpresa $UsuarioRestriccionEmpresa)
    {
        $UsuarioRestriccionEmpresa->delete();

        return response()->json(null, 204);
    }
}
