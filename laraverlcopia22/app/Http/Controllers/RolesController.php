<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Roles;
use App\Models\Bitacora;
use Carbon\Carbon;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Roles::where('eliminado',0)->get();
        $datos = array();
        $datos['roles'] = $roles;
        return $datos;
    }

    public function show(Roles $roles)
    {
        return $roles;
    }

    public function store(Request $request)
    {
        $roles = new Roles;
        $roles->descripcion = $request->descripcion;
        $roles->objeto = $request->objeto;
        $roles->activo = 1;
        $roles->eliminado = 0;
        $roles->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'Roles';
        $bitacora->parametros_nuevos = 'ID '.$roles->id_rol;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $roles = Roles::find($id);
            $datosAnteriores = json_encode($roles,true);
            $roles->descripcion = $request->descripcion;
            $roles->objeto = $request->objeto;
            $roles->activo = $request->activo;
            $roles->eliminado = 0;
            $roles->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'Roles';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $roles = Roles::find($id);
            $roles->eliminado = 1;
            $roles->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'Roles';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Roles $roles)
    {
        $roles->delete();

        return response()->json(null, 204);
    }
}
