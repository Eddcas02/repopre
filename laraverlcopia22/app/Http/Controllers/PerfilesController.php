<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perfiles;
use App\Models\UsuarioPerfil;
use App\Models\Bitacora;
use Carbon\Carbon;

class PerfilesController extends Controller
{
    public function index()
    {
        $perfiles = Perfiles::all();
        $datos = array();
        $datos['perfiles'] = $perfiles;
        return $datos;
    }

    public function show(Perfiles $perfiles)
    {
        return $perfiles;
    }

    public function store(Request $request)
    {
        $perfiles = new Perfiles;
        $perfiles->descripcion = $request->descripcion;
        $perfiles->activo = 1;
        $perfiles->eliminado = 0;
        $perfiles->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'Perfiles';
        $bitacora->parametros_nuevos = 'ID '.$perfiles->id_perfil;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $perfiles = Perfiles::find($id);
            $datosAnteriores = json_encode($perfiles,true);
            $perfiles->descripcion = $request->descripcion;
            $perfiles->activo = $request->activo;
            $perfiles->eliminado = 0;
            $perfiles->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'Perfiles';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $perfiles = Perfiles::find($id);
            $perfiles->eliminado = 1;
            $perfiles->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'Perfiles';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Perfiles $perfiles)
    {
        $perfiles->delete();

        return response()->json(null, 204);
    }

    public function paraasignar($id){
        $usuarioperfil = UsuarioPerfil::select('id_perfil')->where('id_usuario','=', $id)
        ->where('activo','=',1)
        ->where('eliminado','=',0)->get()->toArray();

        $perfiles = Perfiles::whereNotIn('id_perfil', $usuarioperfil)
        ->where('activo','=',1)
        ->where('eliminado','=',0)->get();
        $datos = array();
        $datos['perfiles'] = $perfiles;
        return $datos;
    }
}
