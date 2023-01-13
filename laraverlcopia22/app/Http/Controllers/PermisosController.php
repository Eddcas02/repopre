<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permisos;
use App\Models\Bitacora;
use Carbon\Carbon;

class PermisosController extends Controller
{
    public function index()
    {
        $permisos = Permisos::where('eliminado',0)->get();
        $datos = array();
        $datos['permisos'] = $permisos;
        return $datos;
    }

    public function show(Permisos $permisos)
    {
        return $permisos;
    }

    public function store(Request $request)
    {
        $permisos = new Permisos;
        $permisos->descripcion = $request->descripcion;
        $permisos->activo = 1;
        $permisos->eliminado = 0;
        $permisos->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'Permisos';
        $bitacora->parametros_nuevos = 'ID '.$permisos->id_permiso;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $permisos = Permisos::find($id);
            $datosAnteriores = json_encode($permisos,true);
            $permisos->descripcion = $request->descripcion;
            $permisos->activo = $request->activo;
            $permisos->eliminado = 0;
            $permisos->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'Permisos';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $permisos = Permisos::find($id);
            $permisos->eliminado = 1;
            $permisos->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'Permisos';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Permisos $permisos)
    {
        $permisos->delete();

        return response()->json(null, 204);
    }
}
