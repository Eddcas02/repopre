<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PerfilRol;
use App\Models\Bitacora;
use Carbon\Carbon;

class PerfilRolController extends Controller
{
    public function index()
    {
        $perfilrol = PerfilRol::join('roles', function($join){
            $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
        })
        ->selectRaw(
            "PerfilRol.id_perfilrol,
             PerfilRol.id_perfil,
             PerfilRol.id_rol,
             roles.descripcion,
             PerfilRol.activo,
             PerfilRol.eliminado"
        )
        ->orderBy('PerfilRol.id_rol', 'ASC')
        ->get();
        $datos = array();
        $datos['detalle'] = $perfilrol;
        return $datos;
    }

    public function show($id)
    {
        $perfilrol = PerfilRol::join('roles', function($join){
            $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
        })
        ->selectRaw(
            "PerfilRol.id_perfilrol,
             PerfilRol.id_perfil,
             PerfilRol.id_rol,
             roles.descripcion,
             PerfilRol.activo,
             PerfilRol.eliminado"
        )
        ->where('PerfilRol.id_perfil', $id)
        ->orderBy('PerfilRol.id_rol', 'ASC')->get();
        $datos = array();
        $datos['detalle'] = $perfilrol;
        return $datos;
    }

    public function store(Request $request, $codigo)
    {
        $id_roles = explode("|", $codigo);
        $bandera = 1;
        foreach($id_roles as $id_rol){
            if (!empty($id_rol)){
                $roles = PerfilRol::join('roles', function($join){
                    $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
                })
                ->select('roles.descripcion')
                ->where('PerfilRol.id_perfil', $request->id_perfil)
                ->where('PerfilRol.id_rol', $id_rol)
                ->where('PerfilRol.eliminado', 0)->get();
                if ($roles->count() > 0) {
                    $bandera+=1;
                }
            }
        }

        if($bandera > 1){
            return response()->json("Repetidos");
        }else{
            foreach($id_roles as $rol){
                if (!empty($rol)){
                    $perfilrol = new PerfilRol;
                    $perfilrol->id_perfil = $request->id_perfil;
                    $perfilrol->id_rol = $rol;
                    $perfilrol->activo = 1;
                    $perfilrol->eliminado = 0;
                    $perfilrol->save();
                    $bandera*=1;

                    $fechaActual = Carbon::now('America/Guatemala');
                    $bitacora = new Bitacora;
                    $bitacora->id_usuario = $request->id_usuario;
                    $bitacora->fecha_hora = $fechaActual;
                    $bitacora->accion = 'crear';
                    $bitacora->objeto = 'PerfilRol';
                    $bitacora->parametros_nuevos = 'ID '.$perfilrol->id_perfilrol;
                    $bitacora->save();
            
                }
            }
        }
        if($bandera<=1){
            return response()->json("OK");
        }
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $perfilrol = PerfilRol::find($id);
            $perfilrol->eliminado = 1;
            $perfilrol->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'PerfilRol';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $perfilrol = PerfilRol::find($id);
            $datosAnteriores = json_encode($perfilrol,true);
            $perfilrol->id_rol = $request->id_rol;
            $perfilrol->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'PerfilRol';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK");
        } else if ($opcion == '3') {
            $perfilrol = PerfilRol::find($id);
            $datosAnteriores = json_encode($perfilrol,true);
            $perfilrol->activo = $request->activo;
            $perfilrol->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'PerfilRol';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $perfilrol->delete();

        return response()->json(null, 204);
    }
}
