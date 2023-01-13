<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioPerfil;
use App\Models\UsuarioAutorizacion;
use App\Models\Bitacora;
use Carbon\Carbon;

class UsuarioPerfilController extends Controller
{
    public function index()
    {
        $usuarioperfil = UsuarioPerfil::join('perfiles', function($join){
            $join->on('UsuarioPerfil.id_perfil', '=', 'perfiles.id_perfil');
        })
        ->selectRaw(
            "UsuarioPerfil.id_usuarioperfil,
             UsuarioPerfil.id_usuario,
             UsuarioPerfil.id_perfil,
             perfiles.descripcion,
             UsuarioPerfil.activo,
             UsuarioPerfil.eliminado"
        )
        ->orderBy('UsuarioPerfil.id_perfil', 'ASC')
        ->get();
        $datos = array();
        $datos['detalle'] = $usuarioperfil;
        return $datos;
    }

    public function show($id_usuario, $objeto, $opcion)
    {
        $usuarioperfil = array();
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
            ->where('UsuarioAutorizacion.id_usuariotemporal', $id_usuario)
            ->where('UsuarioAutorizacion.fecha_inicio','<=', 
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
            ->where('UsuarioAutorizacion.fecha_final', '>=',
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $IdUsuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $IdUsuario = $item['id_usuarioaprobador'];
            }
        }else{
            $IdUsuario = $id_usuario;
        }
        if($opcion == '1'){
            $usuarioperfil = UsuarioPerfil::join('perfiles', function($join){
                $join->on('UsuarioPerfil.id_perfil', '=', 'perfiles.id_perfil');
            })
            ->selectRaw(
                "UsuarioPerfil.id_usuarioperfil,
                UsuarioPerfil.id_usuario,
                UsuarioPerfil.id_perfil,
                perfiles.descripcion,
                UsuarioPerfil.activo,
                UsuarioPerfil.eliminado"
            )
            ->where('UsuarioPerfil.id_usuario', $IdUsuario)
			->where('UsuarioPerfil.eliminado', 0)
            ->orderBy('UsuarioPerfil.id_perfil', 'ASC')
            ->get();
        }else if($opcion == '2'){
            $usuarioperfil = UsuarioPerfil::join('PerfilRol', function($join){
                $join->on('UsuarioPerfil.id_perfil', '=', 'PerfilRol.id_perfil');
            })->join('perfiles', function($join){
                $join->on('UsuarioPerfil.id_perfil', '=', 'perfiles.id_perfil');
            })->join('roles', function($join){
                $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
            })
            ->selectRaw(
                "roles.objeto,
                UsuarioPerfil.activo,
                UsuarioPerfil.eliminado"
            )
            ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
            ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
            ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
            ->where('roles.activo', 1)->where('roles.eliminado', 0)
            ->where('UsuarioPerfil.id_usuario', $IdUsuario)
            ->where('roles.objeto', $objeto)
            ->orderBy('roles.id_rol', 'ASC')
            ->get();
        }else if($opcion == '3'){
            $usuarioperfil = UsuarioPerfil::join('PerfilRol', function($join){
                $join->on('UsuarioPerfil.id_perfil', '=', 'PerfilRol.id_perfil');
            })->join('perfiles', function($join){
                $join->on('UsuarioPerfil.id_perfil', '=', 'perfiles.id_perfil');
            })->join('roles', function($join){
                $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
            })
            ->selectRaw(
                "roles.objeto,
                UsuarioPerfil.activo,
                UsuarioPerfil.eliminado"
            )
            ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
            ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
            ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
            ->where('roles.activo', 1)->where('roles.eliminado', 0)
            ->where('UsuarioPerfil.id_usuario', $IdUsuario)
            ->orderBy('roles.id_rol', 'ASC')
            ->get();
        }else if($opcion == '4'){
            $usuarioperfil = UsuarioPerfil::join('PerfilRol', function($join){
                $join->on('UsuarioPerfil.id_perfil', '=', 'PerfilRol.id_perfil');
            })->join('perfiles', function($join){
                $join->on('UsuarioPerfil.id_perfil', '=', 'perfiles.id_perfil');
            })->join('roles', function($join){
                $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
            })->join('RolPermiso', function($join){
            $join->on('PerfilRol.id_rol', '=', 'RolPermiso.id_rol');
            })->join('permisos', function($join){
                $join->on('RolPermiso.id_permiso', '=', 'permisos.id_permiso');
            })
            ->selectRaw(
                "roles.objeto,
                UsuarioPerfil.activo,
                UsuarioPerfil.eliminado,
                permisos.descripcion"
            )
            ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
            ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
            ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
            ->where('roles.activo', 1)->where('roles.eliminado', 0)
            ->where('permisos.activo', 1)->where('permisos.eliminado', 0)
            ->where('UsuarioPerfil.id_usuario', $IdUsuario)
            ->where('roles.objeto', $objeto)
            ->orderBy('roles.id_rol', 'ASC')
            ->get();
        }
        $datos = array();
        $datos['detalle'] = $usuarioperfil;
        return $datos;
    }

    public function store(Request $request, $codigo)
    {
        $id_perfiles = explode("|", $codigo);
        $bandera = 1;
        foreach($id_perfiles as $id_perfil){
            if (!empty($id_perfil)){
                $perfiles = UsuarioPerfil::select('UsuarioPerfil.id_perfil')
                ->where('UsuarioPerfil.id_usuario', $request->idUsuario)
                ->where('UsuarioPerfil.id_perfil', $id_perfil)
                ->where('UsuarioPerfil.activo',1)
                ->where('UsuarioPerfil.eliminado', 0)->get();
                if ($perfiles->count() > 0) {
                    $bandera+=1;
                }
            }
        }

        if($bandera > 1){
            return response()->json("Repetidos");
        }else{
            foreach($id_perfiles as $perfil){
                if (!empty($perfil)){
                    $usuarioperfil = new UsuarioPerfil;
                    $usuarioperfil->id_usuario = $request->idUsuario;
                    $usuarioperfil->id_perfil = $perfil;
                    $usuarioperfil->activo = 1;
                    $usuarioperfil->eliminado = 0;
                    $usuarioperfil->save();
                    $bandera*=1;

                    $fechaActual = Carbon::now('America/Guatemala');
                    $bitacora = new Bitacora;
                    $bitacora->id_usuario = $request->id_usuario;
                    $bitacora->fecha_hora = $fechaActual;
                    $bitacora->accion = 'crear';
                    $bitacora->objeto = 'UsuarioPerfil';
                    $bitacora->parametros_nuevos = 'ID '.$usuarioperfil->id_usuarioperfil;
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
            $usuarioperfil = UsuarioPerfil::find($id);
            $usuarioperfil->eliminado = 1;
            $usuarioperfil->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'UsuarioPerfil';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $usuarioperfil = UsuarioPerfil::find($id);
            $datosAnteriores = json_encode($usuarioperfil,true);
            $usuarioperfil->id_perfil = $request->id_perfil;
            $usuarioperfil->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioPerfil';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK");
        } else if ($opcion == '3') {
            $usuarioperfil = UsuarioPerfil::find($id);
            $datosAnteriores = json_encode($usuarioperfil,true);
            $usuarioperfil->activo = $request->activo;
            $usuarioperfil->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioPerfil';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $usuarioperfil->delete();

        return response()->json(null, 204);
    }
}
