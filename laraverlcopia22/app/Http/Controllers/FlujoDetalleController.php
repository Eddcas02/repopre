<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlujoDetalle;
use App\Models\UsuarioAutorizacion;
use App\Models\EstadoFlujo;
use App\Models\Flujos;
use App\Models\UsuarioGrupo;
use App\Models\Usuarios;
use App\Models\UsuarioPerfil;
use App\Models\RestriccionEmpresa;
use App\Models\UsuarioRestriccionEmpresa;
use App\Models\UsuarioRestriccionTexto;

class FlujoDetalleController extends Controller
{
    public function index()
    {
        $flujoDetalle = FlujoDetalle::leftJoin('EstadoFlujo as EstadoFlujo', function($join){
            $join->on('EstadoFlujo.id_estadoflujo', '=', 'FlujoDetalle.IdEstadoFlujo');
        })->leftJoin('usuarios', function($join2){
            $join2->on('usuarios.id_usuario','=','FlujoDetalle.IdUsuario');
        })->select(
            'FlujoDetalle.IdFlujoDetalle',
            'EstadoFlujo.descripcion',
            'usuarios.nombre',
            'usuarios.apellido',
            'FlujoDetalle.Fecha',
            'FlujoDetalle.Comentario'
        )->orderBy('FlujoDetalle.Fecha')->get();
        $datos = array();
        $datos['bitacora'] = $flujoDetalle;
        return $datos;
    } 
    
    public function flujoproceso($IdFlujo){
        $datos = array();

        $datosFlujo = Flujos::where('id_flujo','=',$IdFlujo)->first();

        $flujoDetalle = FlujoDetalle::leftJoin('EstadoFlujo as EstadoFlujo', function($join){
            $join->on('EstadoFlujo.id_estadoflujo', '=', 'FlujoDetalle.IdEstadoFlujo');
        })->leftJoin('usuarios', function($join2){
                $join2->on('usuarios.id_usuario','=','FlujoDetalle.IdUsuario');
        })->join('Flujo', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })->selectRaw(
            "FlujoDetalle.IdFlujoDetalle,
            FlujoDetalle.IdFlujo,
            FlujoDetalle.IdEstadoFlujo,
            EstadoFlujo.descripcion,
            usuarios.id_usuario,
            usuarios.nombre_usuario,
            usuarios.nombre,
            usuarios.apellido,
            DATE_FORMAT(FlujoDetalle.Fecha,'%d-%m-%Y %H:%i')as Fecha,
            FlujoDetalle.Comentario,
            FlujoDetalle.NivelAutorizo"
        )
        ->where('FlujoDetalle.IdFlujo','=',$IdFlujo)
        ->where('FlujoDetalle.IdEstadoFlujo','=',$datosFlujo->estado)
        ->orderBy('FlujoDetalle.Fecha','DESC')
        ->orderBy('FlujoDetalle.IdEstadoFlujo')
        ->orderBy('FlujoDetalle.NivelAutorizo')
        ->first();

        if(isset($flujoDetalle)){
            $estadoFlujo = EstadoFlujo::where('id_estadoflujopadre','=',$flujoDetalle->IdEstadoFlujo)->first();

            if($datosFlujo->estado == 4){
                $flujoDelProceso[] =[
                    "descripcion" => "Actual",
                    "id_estadoflujo" => $flujoDetalle->IdEstadoFlujo,
                    "nivel" => $flujoDetalle->NivelAutorizo,
                    "nombre_usuario" => $flujoDetalle->nombre_usuario,
                    "nombre" => $flujoDetalle->nombre,
                    "apellido" => $flujoDetalle->apellido,
                    "accion" => $flujoDetalle->descripcion
                ];
                $ultimonivel = UsuarioGrupo::join('Flujo', function($join){
                    $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=', 
                    'Flujo.id_grupoautorizacion');
                })->join('GrupoAutorizacion', function($join){
                    $join->on('Flujo.id_grupoautorizacion', '=', 
                    'GrupoAutorizacion.id_grupoautorizacion');
                })
                ->select('Flujo.id_flujo', 'Flujo.nivel')
                ->where('Flujo.id_flujo', $IdFlujo)
                ->where('GrupoAutorizacion.numero_niveles', $datosFlujo->nivel)
                ->groupBy('Flujo.id_flujo')->get();
    
                if($ultimonivel->count() > 0){
                    $usuariogrupo = Usuarios::join('UsuarioGrupoAutorizacion', function($join){
                        $join->on('usuarios.id_usuario','=','UsuarioGrupoAutorizacion.id_usuario');
                    })
                    ->select('usuarios.nombre_usuario','usuarios.nombre','usuarios.apellido')
                    ->where('UsuarioGrupoAutorizacion.id_grupoautorizacion','=',$datosFlujo->id_grupoautorizacion)
                    ->where('UsuarioGrupoAutorizacion.nivel','=',$datosFlujo->nivel)
                    ->where('UsuarioGrupoAutorizacion.activo', 1)->where('UsuarioGrupoAutorizacion.eliminado', 0)
                    ->where('usuarios.activo','=',1)->where('usuarios.eliminado','=',0)
                    ->groupBy('usuarios.nombre_usuario')
                    ->groupBy('usuarios.nombre')
                    ->groupBy('usuarios.apellido')
                    ->get();
                    
                    foreach($usuariogrupo as $item){
                        $flujoDelProceso[]=[
                            "descripcion" => "Siguiente",
                            "id_estadoflujo" => 5,
                            "nivel" => "",
                            "nombre_usuario" => $item->nombre_usuario,
                            "nombre" => $item->nombre,
                            "apellido" => $item->apellido,
                            "accion" => "Finalizar autorización"
                        ];
                    }
                }else{
                    $usuariogrupo = Usuarios::join('UsuarioGrupoAutorizacion', function($join){
                        $join->on('usuarios.id_usuario','=','UsuarioGrupoAutorizacion.id_usuario');
                    })
                    ->select('usuarios.nombre_usuario','usuarios.nombre','usuarios.apellido')
                    ->where('UsuarioGrupoAutorizacion.id_grupoautorizacion','=',$datosFlujo->id_grupoautorizacion)
                    ->where('UsuarioGrupoAutorizacion.nivel','=',$datosFlujo->nivel)
                    ->where('UsuarioGrupoAutorizacion.activo', 1)->where('UsuarioGrupoAutorizacion.eliminado', 0)
                    ->where('usuarios.activo','=',1)->where('usuarios.eliminado','=',0)
                    ->groupBy('usuarios.nombre_usuario')
                    ->groupBy('usuarios.nombre')
                    ->groupBy('usuarios.apellido')
                    ->get();
                    foreach($usuariogrupo as $item){
                        $flujoDelProceso[]=[
                            "descripcion" => "Siguiente",
                            "id_estadoflujo" => $datosFlujo->estado,
                            "nivel" => $datosFlujo->nivel,
                            "nombre_usuario" => $item->nombre_usuario,
                            "nombre" => $item->nombre,
                            "apellido" => $item->apellido,
                            "accion" => "Autorizar nivel"
                        ];
                    }
                }
            }else{
                $flujoDelProceso[] =[
                    "descripcion" => "Actual",
                    "id_estadoflujo" => $flujoDetalle->IdEstadoFlujo,
                    "nivel" => "",
                    "nombre_usuario" => $flujoDetalle->nombre_usuario,
                    "nombre" => $flujoDetalle->nombre,
                    "apellido" => $flujoDetalle->apellido,
                    "accion" => $flujoDetalle->descripcion
                ];
                if(isset($estadoFlujo)){
                    switch($estadoFlujo->id_estadoflujo){
                        case 2:
                            $usuarioperfil = Usuarios::join('UsuarioPerfil',function($join){
                                $join->on('usuarios.id_usuario','=','UsuarioPerfil.id_usuario');
                            })->join('PerfilRol', function($join){
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
                            ->select('usuarios.nombre_usuario','usuarios.nombre','usuarios.apellido')
                            ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
                            ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
                            ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
                            ->where('roles.activo', 1)->where('roles.eliminado', 0)
                            ->where('RolPermiso.activo', 1)->where('RolPermiso.eliminado', 0)
                            ->where('permisos.activo', 1)->where('permisos.eliminado', 0)
                            ->where('roles.objeto', "Modulo Autorizacion Pagos")
                            ->where('permisos.id_permiso', 7)
                            ->where('usuarios.activo','=',1)->where('usuarios.eliminado','=',0)
                            ->groupBy('usuarios.nombre_usuario')
                            ->groupBy('usuarios.nombre')
                            ->groupBy('usuarios.apellido')
                            ->get();
                            foreach($usuarioperfil as $item){
                                $flujoDelProceso[]=[
                                    "descripcion" => "Siguiente",
                                    "id_estadoflujo" => $estadoFlujo->id_estadoflujo,
                                    "nivel" => "",
                                    "nombre_usuario" => $item->nombre_usuario,
                                    "nombre" => $item->nombre,
                                    "apellido" => $item->apellido,
                                    "accion" => $estadoFlujo->accion
                                ];
                            }
                            break;
                        case 3:
                            $usuarioperfil = Usuarios::join('UsuarioPerfil',function($join){
                                $join->on('usuarios.id_usuario','=','UsuarioPerfil.id_usuario');
                            })->join('PerfilRol', function($join){
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
                            ->select('usuarios.nombre_usuario','usuarios.nombre','usuarios.apellido')
                            ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
                            ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
                            ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
                            ->where('roles.activo', 1)->where('roles.eliminado', 0)
                            ->where('RolPermiso.activo', 1)->where('RolPermiso.eliminado', 0)
                            ->where('permisos.activo', 1)->where('permisos.eliminado', 0)
                            ->where('roles.objeto', "Modulo Autorizacion Pagos")
                            ->where('permisos.id_permiso', 6)
                            ->where('usuarios.activo','=',1)->where('usuarios.eliminado','=',0)
                            ->groupBy('usuarios.nombre_usuario')
                            ->groupBy('usuarios.nombre')
                            ->groupBy('usuarios.apellido')
                            ->get();
                            foreach($usuarioperfil as $item){
                                $flujoDelProceso[]=[
                                    "descripcion" => "Siguiente",
                                    "id_estadoflujo" => $estadoFlujo->id_estadoflujo,
                                    "nivel" => "",
                                    "nombre_usuario" => $item->nombre_usuario,
                                    "nombre" => $item->nombre,
                                    "apellido" => $item->apellido,
                                    "accion" => $estadoFlujo->accion
                                ];
                            }
                            break;
                        case 4:
                            $usuariogrupo = Usuarios::join('UsuarioGrupoAutorizacion', function($join){
                                $join->on('usuarios.id_usuario','=','UsuarioGrupoAutorizacion.id_usuario');
                            })
                            ->select('usuarios.nombre_usuario','usuarios.nombre','usuarios.apellido')
                            ->where('UsuarioGrupoAutorizacion.id_grupoautorizacion','=',$datosFlujo->id_grupoautorizacion)
                            ->where('UsuarioGrupoAutorizacion.nivel','=',1)
                            ->where('UsuarioGrupoAutorizacion.activo', 1)->where('UsuarioGrupoAutorizacion.eliminado', 0)
                            ->where('usuarios.activo','=',1)->where('usuarios.eliminado','=',0)
                            ->groupBy('usuarios.nombre_usuario')
                            ->groupBy('usuarios.nombre')
                            ->groupBy('usuarios.apellido')
                            ->get();
                            foreach($usuariogrupo as $item){
                                $flujoDelProceso[]=[
                                    "descripcion" => "Siguiente",
                                    "id_estadoflujo" => $estadoFlujo->id_estadoflujo,
                                    "nivel" => 1,
                                    "nombre_usuario" => $item->nombre_usuario,
                                    "nombre" => $item->nombre,
                                    "apellido" => $item->apellido,
                                    "accion" => $estadoFlujo->accion
                                ];
                            }
                            break;
                    }
                }
            }
    
            $datos['flujo'] = $flujoDelProceso;
        }else{
            $datos['flujo'] = [];
        }

        
        return $datos;
    }

    public function bitacora($IdFlujo)
    {    
        $flujoDetalle = FlujoDetalle::leftJoin('EstadoFlujo as EstadoFlujo', function($join){
                $join->on('EstadoFlujo.id_estadoflujo', '=', 'FlujoDetalle.IdEstadoFlujo');
        })->leftJoin('usuarios', function($join2){
                $join2->on('usuarios.id_usuario','=','FlujoDetalle.IdUsuario');
        })->join('Flujo', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })->selectRaw(
            "FlujoDetalle.IdFlujoDetalle,
            FlujoDetalle.IdFlujo,
            FlujoDetalle.IdEstadoFlujo,
            FlujoDetalle.FlujoActivo,
            EstadoFlujo.descripcion,
            usuarios.id_usuario,
            usuarios.nombre_usuario,
            usuarios.nombre,
            usuarios.apellido,
            DATE_FORMAT(FlujoDetalle.Fecha,'%d-%m-%Y %H:%i')as Fecha,
            FlujoDetalle.Comentario,
            FlujoDetalle.NivelAutorizo"
        )
        ->orderBy('FlujoDetalle.Fecha')
        ->orderBy('FlujoDetalle.IdEstadoFlujo')
        ->orderBy('FlujoDetalle.NivelAutorizo')
        ->where('FlujoDetalle.IdFlujo','=',$IdFlujo)
        ->get();

        $datos = array();
        $datos['bitacora'] = $flujoDetalle;
        return $datos;
    }

    public function autorizados($IdUsuario, $Tipo)
    {
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
            ->where('UsuarioAutorizacion.id_usuariotemporal', $IdUsuario)
            ->where('UsuarioAutorizacion.fecha_inicio','<=', 
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
            ->where('UsuarioAutorizacion.fecha_final', '>=',
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $id_usuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $id_usuario = $item['id_usuarioaprobador'];
            }
        }else{
            $id_usuario = $IdUsuario;
        }

        $estados = [4, 5, 7, 9, 10, 12, 13, 15, 16, 17, 18, 19]; 
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id_usuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$id_usuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $flujoDetalle = FlujoDetalle::join('Flujo', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })->selectRaw(
            "FlujoDetalle.IdFlujo, 
            Flujo.comments,
            Flujo.activo,
            Flujo.doc_num,
            Flujo.id_grupoautorizacion,
            Flujo.nivel,
            Flujo.estado,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d') as doc_date,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
               WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
               WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
               WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque
            "
        )
        ->whereIn('Flujo.estado', $estados)   
        //->whereIn('FlujoDetalle.IdEstadoFlujo', $estados)     
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)     
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })    
        ->where('FlujoDetalle.FlujoActivo', 1)                  
        ->where('FlujoDetalle.IdUsuario', $id_usuario)
        ->where('Flujo.tipo', $Tipo)
        ->where('Flujo.activo','=',1)->where('Flujo.eliminado','=',0)
        ->groupBy('FlujoDetalle.IdFlujo') 
        ->groupBy('Flujo.comments')
        ->groupBy('Flujo.activo')
        ->groupBy('Flujo.doc_num')
        ->groupBy('Flujo.id_grupoautorizacion')
        ->groupBy('Flujo.nivel')
        ->groupBy('Flujo.estado')
        ->groupBy('Flujo.doc_date')
        ->orderBy('FlujoDetalle.IdFlujo')
        ->groupBy('Flujo.card_name')
        ->groupBy('Flujo.doc_total')
        ->groupBy('Flujo.doc_curr')
        ->orderBy('Flujo.empresa_nombre')->get();
        $datos = array();
        $datos['bitacora'] = $flujoDetalle;
        return $datos;
    }

    public function rechazados($IdUsuario, $Tipo)
    {
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
            ->where('UsuarioAutorizacion.id_usuariotemporal', $IdUsuario)
            ->where('UsuarioAutorizacion.fecha_inicio','<=', 
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
            ->where('UsuarioAutorizacion.fecha_final', '>=',
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $id_usuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $id_usuario = $item['id_usuarioaprobador'];
            }
        }else{
            $id_usuario = $IdUsuario;
        }
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id_usuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$id_usuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }
        
        $flujoDetalle = FlujoDetalle::join('Flujo', function($join){
            $join->on('Flujo.id_flujo', '=', 'FlujoDetalle.IdFlujo');
        })->join('UsuarioGrupoAutorizacion', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion','=','Flujo.id_grupoautorizacion');
        })
        ->selectRaw(
            "FlujoDetalle.IdFlujoDetalle,
            FlujoDetalle.IdFlujo,
            FlujoDetalle.Comentario,
            Flujo.comments,
            Flujo.activo,
            Flujo.doc_num,
            Flujo.id_grupoautorizacion,
            Flujo.nivel,
            Flujo.estado,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d') as doc_date,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
               WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
               WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
               WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque"
        )
        ->where('Flujo.estado', 6)
        ->where('FlujoDetalle.IdEstadoFlujo', 6)         
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->where('UsuarioGrupoAutorizacion.id_usuario', $id_usuario)
        ->where('UsuarioGrupoAutorizacion.activo', 1)
        ->where('UsuarioGrupoAutorizacion.eliminado', 0)
        ->where('Flujo.tipo', $Tipo)
        ->where('Flujo.activo','=',1)->where('Flujo.eliminado','=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('FlujoDetalle.IdFlujo')->get();
        $datos = array();
        $datos['bitacora'] = $flujoDetalle;
        return $datos;
    }

    public function compensados($IdUsuario, $Tipo)
    {
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
        ->select('roles.objeto', 'RolPermiso.id_permiso', 'permisos.descripcion')
        ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
        ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
        ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
        ->where('roles.activo', 1)->where('roles.eliminado', 0)
        ->where('RolPermiso.activo', 1)->where('RolPermiso.eliminado', 0)
        ->where('permisos.activo', 1)->where('permisos.eliminado', 0)
        ->where('roles.objeto', "Modulo Compensacion Pagos")
        ->where('UsuarioPerfil.id_usuario', $IdUsuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();
        
        $permisos = array();
        $i = 0;
        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $mostrarDatos = 0;
        foreach($permisos as $item){
            if($item['id_permiso'] == 9){
                $mostrarDatos = 1;
            }
        }

        $datos = array();
        if($mostrarDatos == 1){
            $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
            ->where('activo',1)->get()->toArray();

            $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
            ->where('id_usuario',$IdUsuario)
            ->where('eliminado',0)
            ->where('activo',1)
            ->get()->toArray();

            if(!empty($EmpresasDeUsuario)){
                $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
                ->whereIn('empresa_codigo', $EmpresasDeUsuario)
                ->groupBy('empresa_codigo')
                ->groupBy('empresa_nombre')
                ->get()->toArray();
            }else{
                $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
                ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
                ->groupBy('empresa_codigo')
                ->groupBy('empresa_nombre')
                ->get()->toArray();
            }

            $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
            ->where('id_usuario',$IdUsuario)
            ->where('eliminado',0)
            ->where('activo',1)
            ->get()->toArray();
    
            $listaWhereTextos = array();
    
            if(count($listaWhereTextosTmp) > 0){
                foreach($listaWhereTextosTmp as $item){
                    $listaWhereTextos[] = $item['texto'];
                }
            }

            $flujoDetalle = FlujoDetalle::join('Flujo', function($join){
                $join->on('Flujo.id_flujo', '=', 'FlujoDetalle.IdFlujo');
            })
            ->selectRaw(
                "FlujoDetalle.IdFlujoDetalle,
                FlujoDetalle.IdFlujo,
                FlujoDetalle.Comentario,
                Flujo.comments,
                Flujo.activo,
                Flujo.doc_num,
                Flujo.id_grupoautorizacion,
                Flujo.nivel,
                Flujo.estado,
                DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d') as doc_date,
                Flujo.card_name,
                Flujo.en_favor_de,
                Flujo.doc_total,
                Flujo.doc_curr,
                Flujo.empresa_nombre,
                (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
                and fd.IdFlujo = Flujo.id_flujo) as aut_date,
                (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
                and fd.IdFlujo = Flujo.id_flujo) as creation_date,
                CASE
                   WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
                   WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
                   WHEN Flujo.tipo = 'INTERNA' THEN 1
                END as TieneCheque"
            )
            ->where('FlujoDetalle.IdEstadoFlujo', 7)         
            ->where('FlujoDetalle.FlujoActivo', 1)
            ->where('Flujo.estado', 7)
            ->where('Flujo.tipo', $Tipo)
            ->where('Flujo.activo','=',1)->where('Flujo.eliminado','=',0)
            ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
            ->where(function ($q) use ($listaWhereTextos) {
                foreach ($listaWhereTextos as $value) {
                     $q->orWhere('Flujo.comments', 'like', "%{$value}%");
                }
            })
            ->orderBy('FlujoDetalle.IdFlujo')->get()->toArray();

            $datos['bitacora'] = $flujoDetalle;
        }else{
            $datos['bitacora'] = array();
        }
        return $datos;
    }

    public function enviadosBanco($IdUsuario, $Tipo)
    {
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
        ->select('roles.objeto', 'RolPermiso.id_permiso', 'permisos.descripcion')
        ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
        ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
        ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
        ->where('roles.activo', 1)->where('roles.eliminado', 0)
        ->where('RolPermiso.activo', 1)->where('RolPermiso.eliminado', 0)
        ->where('permisos.activo', 1)->where('permisos.eliminado', 0)
        ->where('roles.objeto', "Modulo Compensacion Pagos")
        ->where('UsuarioPerfil.id_usuario', $IdUsuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();
        
        $permisos = array();
        $i = 0;
        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $mostrarDatos = 0;
        foreach($permisos as $item){
            if($item['id_permiso'] == 9){
                $mostrarDatos = 1;
            }
        }

        $datos = array();
        if($mostrarDatos == 1){
            $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
            ->where('activo',1)->get()->toArray();

            $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
            ->where('id_usuario',$IdUsuario)
            ->where('eliminado',0)
            ->where('activo',1)
            ->get()->toArray();

            if(!empty($EmpresasDeUsuario)){
                $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
                ->whereIn('empresa_codigo', $EmpresasDeUsuario)
                ->groupBy('empresa_codigo')
                ->groupBy('empresa_nombre')
                ->get()->toArray();
            }else{
                $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
                ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
                ->groupBy('empresa_codigo')
                ->groupBy('empresa_nombre')
                ->get()->toArray();
            }

            $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
            ->where('id_usuario',$IdUsuario)
            ->where('eliminado',0)
            ->where('activo',1)
            ->get()->toArray();
    
            $listaWhereTextos = array();
    
            if(count($listaWhereTextosTmp) > 0){
                foreach($listaWhereTextosTmp as $item){
                    $listaWhereTextos[] = $item['texto'];
                }
            }

            $flujoDetalle = FlujoDetalle::join('Flujo', function($join){
                $join->on('Flujo.id_flujo', '=', 'FlujoDetalle.IdFlujo');
            })
            ->selectRaw(
                "FlujoDetalle.IdFlujoDetalle,
                FlujoDetalle.IdFlujo,
                FlujoDetalle.Comentario,
                Flujo.comments,
                Flujo.activo,
                Flujo.doc_num,
                Flujo.id_grupoautorizacion,
                Flujo.nivel,
                Flujo.estado,
                DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d') as doc_date,
                Flujo.card_name,
                Flujo.en_favor_de,
                Flujo.doc_total,
                Flujo.doc_curr,
                Flujo.empresa_nombre,
                (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
                and fd.IdFlujo = Flujo.id_flujo) as aut_date,
                (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
                and fd.IdFlujo = Flujo.id_flujo) as creation_date,
                CASE
                   WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
                   WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
                   WHEN Flujo.tipo = 'INTERNA' THEN 1
                END as TieneCheque"
            )
            ->where('FlujoDetalle.IdEstadoFlujo', 17)         
            ->where('FlujoDetalle.FlujoActivo', 1)
            ->where('Flujo.estado', 17)
            ->where('Flujo.tipo', $Tipo)
            ->where('Flujo.activo','=',1)->where('Flujo.eliminado','=',0)
            ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
            ->where(function ($q) use ($listaWhereTextos) {
                foreach ($listaWhereTextos as $value) {
                     $q->orWhere('Flujo.comments', 'like', "%{$value}%");
                }
            })
            ->orderBy('FlujoDetalle.IdFlujo')->get()->toArray();

            $datos['bitacora'] = $flujoDetalle;
        }else{
            $datos['bitacora'] = array();
        }
        return $datos;
    }

    public function aceptadosBanco($IdUsuario, $Tipo)
    {
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
        ->select('roles.objeto', 'RolPermiso.id_permiso', 'permisos.descripcion')
        ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
        ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
        ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
        ->where('roles.activo', 1)->where('roles.eliminado', 0)
        ->where('RolPermiso.activo', 1)->where('RolPermiso.eliminado', 0)
        ->where('permisos.activo', 1)->where('permisos.eliminado', 0)
        ->where('roles.objeto', "Modulo Compensacion Pagos")
        ->where('UsuarioPerfil.id_usuario', $IdUsuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();
        
        $permisos = array();
        $i = 0;
        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $mostrarDatos = 0;
        foreach($permisos as $item){
            if($item['id_permiso'] == 9){
                $mostrarDatos = 1;
            }
        }

        $datos = array();
        if($mostrarDatos == 1){
            $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
            ->where('activo',1)->get()->toArray();

            $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
            ->where('id_usuario',$IdUsuario)
            ->where('eliminado',0)
            ->where('activo',1)
            ->get()->toArray();

            if(!empty($EmpresasDeUsuario)){
                $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
                ->whereIn('empresa_codigo', $EmpresasDeUsuario)
                ->groupBy('empresa_codigo')
                ->groupBy('empresa_nombre')
                ->get()->toArray();
            }else{
                $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
                ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
                ->groupBy('empresa_codigo')
                ->groupBy('empresa_nombre')
                ->get()->toArray();
            }

            $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
            ->where('id_usuario',$IdUsuario)
            ->where('eliminado',0)
            ->where('activo',1)
            ->get()->toArray();
    
            $listaWhereTextos = array();
    
            if(count($listaWhereTextosTmp) > 0){
                foreach($listaWhereTextosTmp as $item){
                    $listaWhereTextos[] = $item['texto'];
                }
            }

            $flujoDetalle = FlujoDetalle::join('Flujo', function($join){
                $join->on('Flujo.id_flujo', '=', 'FlujoDetalle.IdFlujo');
            })
            ->selectRaw(
                "FlujoDetalle.IdFlujoDetalle,
                FlujoDetalle.IdFlujo,
                FlujoDetalle.Comentario,
                Flujo.comments,
                Flujo.activo,
                Flujo.doc_num,
                Flujo.id_grupoautorizacion,
                Flujo.nivel,
                Flujo.estado,
                DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d') as doc_date,
                Flujo.card_name,
                Flujo.en_favor_de,
                Flujo.doc_total,
                Flujo.doc_curr,
                Flujo.empresa_nombre,
                (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
                and fd.IdFlujo = Flujo.id_flujo) as aut_date,
                (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
                and fd.IdFlujo = Flujo.id_flujo) as creation_date,
                CASE
                   WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
                   WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
                   WHEN Flujo.tipo = 'INTERNA' THEN 1
                END as TieneCheque"
            )
            ->where('FlujoDetalle.IdEstadoFlujo', 15)         
            ->where('FlujoDetalle.FlujoActivo', 1)
            ->where('Flujo.estado', 15)
            ->where('Flujo.tipo', $Tipo)
            ->where('Flujo.activo','=',1)->where('Flujo.eliminado','=',0)
            ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
            ->where(function ($q) use ($listaWhereTextos) {
                foreach ($listaWhereTextos as $value) {
                     $q->orWhere('Flujo.comments', 'like', "%{$value}%");
                }
            })
            ->orderBy('FlujoDetalle.IdFlujo')->get()->toArray();

            $datos['bitacora'] = $flujoDetalle;
        }else{
            $datos['bitacora'] = array();
        }
        return $datos;
    }

    public function cancelados($IdUsuario, $Tipo)
    {
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
            ->where('UsuarioAutorizacion.id_usuariotemporal', $IdUsuario)
            ->where('UsuarioAutorizacion.fecha_inicio','<=', 
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
            ->where('UsuarioAutorizacion.fecha_final', '>=',
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $id_usuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $id_usuario = $item['id_usuarioaprobador'];
            }
        }else{
            $id_usuario = $IdUsuario;
        }

        $estados = [8]; 
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $flujoDetalle = FlujoDetalle::join('Flujo', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })->selectRaw(
            "FlujoDetalle.IdFlujo, 
            Flujo.comments,
            Flujo.activo,
            Flujo.doc_num,
            Flujo.id_grupoautorizacion,
            Flujo.nivel,
            Flujo.estado,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d') as doc_date,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
               WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
               WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
               WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque
            "
        )
        ->where('Flujo.estado', 8)   
        //->where('FlujoDetalle.IdEstadoFlujo', 8)                       
        ->where('FlujoDetalle.IdUsuario', $id_usuario)               
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->where('Flujo.tipo', $Tipo)
        ->where('Flujo.activo','=',1)->where('Flujo.eliminado','=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->groupBy('FlujoDetalle.IdFlujo') 
        ->groupBy('Flujo.comments')
        ->groupBy('Flujo.activo')
        ->groupBy('Flujo.doc_num')
        ->groupBy('Flujo.id_grupoautorizacion')
        ->groupBy('Flujo.nivel')
        ->groupBy('Flujo.estado')
        ->groupBy('Flujo.doc_date')
        ->orderBy('FlujoDetalle.IdFlujo')
        ->groupBy('Flujo.card_name')
        ->groupBy('Flujo.doc_total')
        ->groupBy('Flujo.doc_curr')
        ->orderBy('Flujo.empresa_nombre')->get();
        $datos = array();
        $datos['bitacora'] = $flujoDetalle;
        return $datos;
    }

    public function reemplazos($IdUsuario, $Tipo)
    {
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
            ->where('UsuarioAutorizacion.id_usuariotemporal', $IdUsuario)
            ->where('UsuarioAutorizacion.fecha_inicio','<=', 
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
            ->where('UsuarioAutorizacion.fecha_final', '>=',
              date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $id_usuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $id_usuario = $item['id_usuarioaprobador'];
            }
        }else{
            $id_usuario = $IdUsuario;
        }

        $estados = [16]; 
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $flujoDetalle = FlujoDetalle::join('Flujo', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })->selectRaw(
            "FlujoDetalle.IdFlujo, 
            Flujo.comments,
            Flujo.activo,
            Flujo.doc_num,
            Flujo.id_grupoautorizacion,
            Flujo.nivel,
            Flujo.estado,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d') as doc_date,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
               WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
               WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
               WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque
            "
        )
        ->where('Flujo.estado', 16)   
        //->where('FlujoDetalle.IdEstadoFlujo', 8)                       
        ->where('FlujoDetalle.IdUsuario', $id_usuario)               
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->where('Flujo.tipo', $Tipo)
        ->where('Flujo.activo','=',1)->where('Flujo.eliminado','=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->groupBy('FlujoDetalle.IdFlujo') 
        ->groupBy('Flujo.comments')
        ->groupBy('Flujo.activo')
        ->groupBy('Flujo.doc_num')
        ->groupBy('Flujo.id_grupoautorizacion')
        ->groupBy('Flujo.nivel')
        ->groupBy('Flujo.estado')
        ->groupBy('Flujo.doc_date')
        ->orderBy('FlujoDetalle.IdFlujo')
        ->groupBy('Flujo.card_name')
        ->groupBy('Flujo.doc_total')
        ->groupBy('Flujo.doc_curr')
        ->orderBy('Flujo.empresa_nombre')->get();
        $datos = array();
        $datos['bitacora'] = $flujoDetalle;
        return $datos;
    }

    public function store(Request $request)
    {
        $existeFlujoDetalle = FlujoDetalle::where('IdFlujo',$request->IdFlujo)
                ->where('IdEstadoFlujo',$request->IdEstadoFlujo)
                ->where('IdUsuario',$request->IdUsuario)
                ->where('NivelAutorizo',$request->NivelAutorizo)
                ->where('FlujoActivo','=',1)->first();
        if(!$existeFlujoDetalle){
            $flujoDetalle = new FlujoDetalle;
            $flujoDetalle->IdFlujo = $request->IdFlujo;
            $flujoDetalle->IdEstadoFlujo = $request->IdEstadoFlujo;
            $flujoDetalle->IdUsuario = $request->IdUsuario;
            $flujoDetalle->Fecha = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
            $flujoDetalle->Comentario = $request->Comentario;
            $flujoDetalle->NivelAutorizo = $request->NivelAutorizo;
            $flujoDetalle->save();
        }
        return response()->json("OK");
    }

    public function update(Request $request)
    {
        return response()->json("OK");
    }

    public function delete(Request $request)
    {
        return response()->json("OK");
    }
}

