<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RolPermiso;
use App\Models\Bitacora;
use Carbon\Carbon;

class RolPermisoController extends Controller
{
    public function index()
    {
        $rolpermiso = RolPermiso::join('permisos', function($join){
            $join->on('RolPermiso.id_permiso', '=', 'permisos.id_permiso');
        })
        ->selectRaw(
            "RolPermiso.id_rolpermiso,
             RolPermiso.id_rol,
             RolPermiso.id_permiso,
             RolPermiso.activo,
             RolPermiso.eliminado"
        )
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();
        $datos = array();
        $datos['detalle'] = $rolpermiso;
        return $datos;
    }

    public function show($id)
    {
        $rolpermiso = RolPermiso::join('permisos', function($join){
            $join->on('RolPermiso.id_permiso', '=', 'permisos.id_permiso');
        })
        ->selectRaw(
            "RolPermiso.id_rolpermiso,
             RolPermiso.id_rol,
             RolPermiso.id_permiso,
             permisos.descripcion,
             RolPermiso.activo,
             RolPermiso.eliminado"
        )
        ->where('RolPermiso.id_rol', $id)
        ->orderBy('RolPermiso.id_permiso', 'ASC')->get();
        $datos = array();
        $datos['detalle'] = $rolpermiso;
        return $datos;
    }

    public function store(Request $request, $codigo)
    {
        $id_permisos = explode("|", $codigo);
        $bandera = 1;
        foreach($id_permisos as $id_permiso){
            if (!empty($id_permiso)){
                $permisos = RolPermiso::join('permisos', function($join){
                    $join->on('RolPermiso.id_permiso', '=', 'permisos.id_permiso');
                })
                ->select('permisos.descripcion')
                ->where('RolPermiso.id_rol', $request->id_rol)
                ->where('RolPermiso.id_permiso', $id_permiso)
                ->where('RolPermiso.eliminado', 0)->get();
                if ($permisos->count() > 0) {
                    $bandera+=1;
                }
            }
        }

        if($bandera > 1){
            return response()->json("Repetidos");
        }else{
            foreach($id_permisos as $permiso){
                if (!empty($permiso)){
                    $rolpermiso = new RolPermiso;
                    $rolpermiso->id_rol = $request->id_rol;
                    $rolpermiso->id_permiso = $permiso;
                    $rolpermiso->activo = 1;
                    $rolpermiso->eliminado = 0;
                    $rolpermiso->save();
                    $bandera*=1;

                    $fechaActual = Carbon::now('America/Guatemala');
                    $bitacora = new Bitacora;
                    $bitacora->id_usuario = $request->id_usuario;
                    $bitacora->fecha_hora = $fechaActual;
                    $bitacora->accion = 'crear';
                    $bitacora->objeto = 'RolPermiso';
                    $bitacora->parametros_nuevos = 'ID '.$rolpermiso->id_rolpermiso;
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
            $rolpermiso = RolPermiso::find($id);
            $rolpermiso->eliminado = 1;
            $rolpermiso->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'RolPermiso';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $rolpermiso = RolPermiso::find($id);
            $datosAnteriores = json_encode($rolpermiso,true);
            $rolpermiso->id_permiso = $request->id_permiso;
            $rolpermiso->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'RolPermiso';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK");
        } else if ($opcion == '3') {
            $rolpermiso = RolPermiso::find($id);
            $datosAnteriores = json_encode($rolpermiso,true);
            $rolpermiso->activo = $request->activo;
            $rolpermiso->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'RolPermiso';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $rolpermiso->delete();

        return response()->json(null, 204);
    }
}
