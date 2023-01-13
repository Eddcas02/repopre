<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notificacion;
use App\Models\Usuarios;
use App\Models\Flujos;
use App\Models\UsuarioAutorizacion;
use App\Models\RestriccionEmpresa;
use App\Models\UsuarioRestriccionEmpresa;
use App\Models\UsuarioRestriccionTexto;

class NotificacionController extends Controller
{
    public function index()
    {
        $EmpresasRestringidas = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();
        $notificaciones = Notificacion::join('usuarios', function($join){
            $join->on('usuarios.id_usuario', '=', 'NotificacionUsuario.IdUsuario');
        })->join('Flujo', function($join){
            $join->on('Flujo.id_flujo', '=', 'NotificacionUsuario.IdFlujo');
        })
        ->selectRaw(
            "NotificacionUsuario.IdNotificacion,
             NotificacionUsuario.IdFlujo,
             NotificacionUsuario.IdUsuario,
             NotificacionUsuario.Mensaje,
             Flujo.doc_num as Pago,
             Flujo.id_grupoautorizacion as IdGrupo,
             DATE_FORMAT(Flujo.doc_date,'%d-%m-%Y') as doc_date,
             Flujo.tipo,
             Flujo.comments,
             Flujo.nivel,
             Flujo.estado,
             Flujo.activo,
             NotificacionUsuario.Leido,
             Flujo.card_name,
             Flujo.en_favor_de,
             Flujo.doc_total,
             Flujo.doc_curr,
             Flujo.empresa_nombre,
             (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
             and fd.IdFlujo = Flujo.id_flujo) as creation_date,
             CASE
                WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
                WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
                WHEN Flujo.tipo = 'INTERNA' THEN 1
             END as TieneCheque"
        )
        //->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->orderBy('NotificacionUsuario.IdNotificacion', 'ASC')
        ->get();
        $datos = array();
        $datos['notificaciones'] = $notificaciones;
        \DB::disconnect('mysql');
        return $datos;
    }

    public function show($id)
    {
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
            ->where('UsuarioAutorizacion.id_usuariotemporal', $id)
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
            $IdUsuario = $id;
        }

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

        $notificaciones = Notificacion::join('usuarios', function($join){
            $join->on('usuarios.id_usuario', '=', 'NotificacionUsuario.IdUsuario');
        })->join('Flujo', function($join){
            $join->on('Flujo.id_flujo', '=', 'NotificacionUsuario.IdFlujo');
        })
        ->selectRaw(
            "NotificacionUsuario.IdNotificacion,
             NotificacionUsuario.IdFlujo,
             NotificacionUsuario.IdUsuario,
             NotificacionUsuario.Mensaje,
             Flujo.doc_num as Pago,
             Flujo.id_grupoautorizacion as IdGrupo,
             DATE_FORMAT(Flujo.doc_date,'%d-%m-%Y') as doc_date,
             Flujo.tipo,
             Flujo.comments,
             Flujo.nivel,
             Flujo.estado,
             Flujo.activo,
             NotificacionUsuario.Leido,
             Flujo.card_name,
             Flujo.en_favor_de,
             Flujo.doc_total,
             Flujo.doc_curr,
             Flujo.empresa_nombre,
             (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
             and fd.IdFlujo = Flujo.id_flujo) as creation_date,
             CASE
                WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
                WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
                WHEN Flujo.tipo = 'INTERNA' THEN 1
             END as TieneCheque"
        )
        ->where('NotificacionUsuario.IdUsuario', $IdUsuario)
        ->where('NotificacionUsuario.Leido', 0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('NotificacionUsuario.IdNotificacion', 'ASC')
        ->get();
        $datos = array();
        $datos['notificaciones'] = $notificaciones;
        \DB::disconnect('mysql');
        return $datos;
    }

    public function store(Request $request)
    {
        $bandera = 1;
        foreach($request->pagos as $pago){
            $IdGrupo = 0;
            $DocNum = 0;
            $mensaje = "Pago ";
            $flujos = Flujos::select('id_grupoautorizacion', 'doc_num')
            ->where('id_flujo', $pago)->get()->toArray();
            foreach($flujos as $item){
                $IdGrupo = $item['id_grupoautorizacion'];
                $DocNum = $item['doc_num'];
            }
            $usuarios = Usuarios::join('UsuarioGrupoAutorizacion', function($join){
                $join->on('usuarios.id_usuario', '=', 'UsuarioGrupoAutorizacion.id_usuario');
            })->join('GrupoAutorizacion', function($join){
                $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion',
                '=', 'GrupoAutorizacion.id_grupoautorizacion');
            })->join('Flujo', function($join){
                $join->on('GrupoAutorizacion.id_grupoautorizacion', '=', 
                'Flujo.id_grupoautorizacion');
            })
            ->select('usuarios.id_usuario')
            ->where('usuarios.activo', 1)->where('usuarios.eliminado', 0)
            ->where('GrupoAutorizacion.activo', 1)->where('GrupoAutorizacion.eliminado', 0)
            ->where('UsuarioGrupoAutorizacion.activo', 1)->where('UsuarioGrupoAutorizacion.eliminado', 0)
            ->where('Flujo.id_grupoautorizacion', $IdGrupo)
            ->where('usuarios.id_usuario', '!=', $request->IdUsuario)
            ->where('Flujo.id_flujo', $pago)
            ->groupBy('usuarios.id_usuario')->get();
    
            $users = array();  
            $mensaje.=$DocNum.' '.$request->Mensaje;      
    
            if($usuarios->count() > 0){
                $i = 0;
                foreach($usuarios->toArray() as $user){
                    Notificacion::join('FlujoDetalle', function($join){
                        $join->on('NotificacionUsuario.IdFlujo', 'FlujoDetalle.IdFlujo');
                    })
                    ->where('FlujoDetalle.IdEstadoFlujo', 5)
                    ->where('NotificacionUsuario.Leido', 0)
                    ->where('NotificacionUsuario.IdFlujo', $pago)
                    ->where('NotificacionUsuario.IdUsuario', $user['id_usuario']) 
                    ->update(['NotificacionUsuario.Leido' => 1]); 

                    $notificaciones = new Notificacion;
                    $notificaciones->IdFlujo = $pago;
                    $notificaciones->IdUsuario = $user['id_usuario'];
                    $notificaciones->Mensaje = $mensaje; 
                    $notificaciones->Leido = 0;
                    $notificaciones->save();
                    $bandera *= 1; 
                }
            } 
        }        

        if($bandera == 1){
            return response()->json("OK");
        }
    }

    public function update(Request $request, $opcion)
    {
        if ($opcion == '1') {
            Notificacion::where('Leido', 0)
            ->whereIn('IdFlujo', $request->pagos)
            ->where('IdUsuario', $request->IdUsuario) 
            ->update(['Leido' => 1]);
            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $mensajes->delete();

        return response()->json(null, 204);
    }
}