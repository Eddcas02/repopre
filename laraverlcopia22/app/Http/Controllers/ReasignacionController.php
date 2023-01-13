<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bitacora;
use Carbon\Carbon;
use App\Models\Flujos;
use App\Models\SugerenciaAsignacionGrupo;
use App\Models\Notificacion;
use App\Models\UsuarioGrupo;
use App\Models\FlujoDetalle;
use App\Mail\EnviarNotificacion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\UsuarioAutorizacion;
use App\Models\UsuarioPerfil;
use App\Models\RestriccionEmpresa;
use App\Models\UsuarioRestriccionEmpresa;
use App\Models\UsuarioRestriccionTexto;
use App\Models\UsuarioSinNotificacionCorreo;

class ReasignacionController extends Controller
{
    public function index()
    {
        $datos = array();
        return $datos;
    }

    public function show($id_usuario)
    {
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
        ->where('roles.objeto', "Modulo Autorizacion Pagos")
        ->where('UsuarioPerfil.id_usuario', $IdUsuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();
          

        $flujos = array();
        $estados = array();
        $permisos = array();
        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        foreach($permisos as $item){
            if($item['id_permiso'] == 6){
                $estados[] = 2;
                $estados[] = 3;
                $estados[] = 4;
                $estados[] = 10;
                $estados[] = 11;
            }
        }
        //Cambiar nombre a variable de listado
        //Cambiar todos los NotIn por In
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

        $ListaFlujosEstado = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_tipoflujo,
            Flujo.doc_num,
            Flujo.tipo,
            DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
            Flujo.comments,
            Flujo.activo,
            Flujo.estado,
            Flujo.nivel,
            Flujo.id_grupoautorizacion,
            (select ga.identificador from GrupoAutorizacion as ga where 
            ga.id_grupoautorizacion = Flujo.id_grupoautorizacion) as nombre_grupo,
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
            END as TieneCheque,
            '0' as PuedoAutorizar,
            CASE
                WHEN Flujo.ConDuda = 1 THEN 'AZUL'
                ELSE 'NO'
            END as colorSemaforo,
            (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$IdUsuario." 
            and activo = 1 and eliminado = 0) as marcarRecordado
            "
        )
        ->whereIn('Flujo.estado', $estados)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();

        $j = 0;
        foreach($ListaFlujosEstado as $item){
            $flujos[$j] = $item;
            $j += 1;
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function store(Request $request)
    {
        //Listado de usuarios que no reciben correo
        $UsuariosSinCorreos = UsuarioSinNotificacionCorreo::select(['id_usuario'])->where('eliminado',0)->where('activo',1)->get()->toArray();

        Flujos::where('id_flujo', $request->id_flujo)
        ->update([
            'id_grupoautorizacion' => $request->id_grupoautorizacion,
            'estado' => 3,
            'nivel' => 0
        ]);
        SugerenciaAsignacionGrupo::where('id_flujo', $request->id_flujo)
        ->update([
            'activo' => 0
        ]);
        //Desactivamos notificaciones
        Notificacion::where('IdFlujo', $request->id_flujo)
        ->update([
            'Leido' => 1
        ]);
        //Enviar notificación de autorización
        $usuariosNivel = UsuarioGrupo::join('Flujo', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=', 
            'Flujo.id_grupoautorizacion');
        })->join('usuarios', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_usuario','=','usuarios.id_usuario');
        })
        ->select('usuarios.id_usuario', 'usuarios.correo', 'Flujo.doc_num')
        ->where('Flujo.id_flujo', $request->id_flujo)
        ->where('UsuarioGrupoAutorizacion.nivel', 1)
        ->where('UsuarioGrupoAutorizacion.activo', 1)
        ->where('UsuarioGrupoAutorizacion.eliminado', 0)
        ->where('usuarios.activo', 1)
        ->where('usuarios.eliminado', 0)
        ->whereNotIn('UsuarioGrupoAutorizacion.id_usuario', $UsuariosSinCorreos)->get();

        $flujoDetalle = new FlujoDetalle;
        $flujoDetalle->IdFlujo = $request->id_flujo;
        $flujoDetalle->IdEstadoFlujo = 3;
        $flujoDetalle->IdUsuario = $request->id_usuario;
        $flujoDetalle->Fecha = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
        $flujoDetalle->Comentario = "Reasignación de responsable";
        $flujoDetalle->NivelAutorizo = 0;
        $flujoDetalle->save();

        foreach($usuariosNivel as $usuario){
            
            $details=['id_flujo' => $request->id_flujo];
            $details+=['doc_num' => $usuario->doc_num];
            $details+=['correo' => $usuario->correo];
            //Enviamos corre
            if(filter_var($usuario->correo, FILTER_VALIDATE_EMAIL)){
                try{
                    Mail::to($usuario->correo)->send(new EnviarNotificacion($details));
                }catch(Exception $e){
                    Log::error($e->getMessage());
                }
            }

            //Creamos notificación
            $notificaciones = new Notificacion;
            $notificaciones->IdFlujo = $request->id_flujo;
            $notificaciones->IdUsuario = $usuario->id_usuario;
            $notificaciones->Mensaje = 'Nuevo pago para autorizar No. '.$usuario->doc_num; 
            $notificaciones->Leido = 0;
            $notificaciones->save();
        }

        return response()->json("OK");
    }

    public function update(Request $request, $id)
    {
        return response()->json("OK");
    }

    public function delete(Request $request)
    {
        return response()->json(null, 204);
    }
}
