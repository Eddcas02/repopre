<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flujos;
use App\Models\UsuarioPerfil;
use App\Models\UsuarioAutorizacion;
use App\Models\UsuarioGrupo;
use App\Models\FlujoGrupo;
use App\Models\FlujoOrden;
use App\Models\FlujoDetalle;
use App\Models\Bancos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\FlujoFacturaCantidad;
use App\Models\FlujoFacturaDocumento;
use App\Models\FlujoIngreso;
use App\Models\FlujoOferta;
use App\Models\FlujoSolicitud;
use App\Models\LotePago;
use App\Models\FlujoLotePago;
use App\Models\FlujoNumeroCheque;
use PDF;
use QrCode;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Exports\ArchivoPrimarioExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnvioArchivos;
use App\Mail\EnviarNotificacion;
use File;
use App\Models\Politicas;
use App\Models\RestriccionEmpresa;
use App\Models\UsuarioRestriccionEmpresa;
use App\Models\UsuarioRestriccionTexto;
use App\Models\SugerenciaAsignacionGrupo;
use App\Models\UsuarioNotificacionTransaccion;
use App\Models\Usuarios;
use App\Models\NotificacionTipoDocumentoLote;
use App\Models\ZBancoMaestro;
use App\Models\ZEmpresa;
use App\Models\RecordatorioUsuario;
use App\Models\FlujoCambioDias;
use App\Models\Notificacion;
use App\Models\UsuarioSinNotificacionCorreo;

class ConsultorController extends Controller
{
    public function pendientes($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }

        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [1,2,3,4,10,11])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function autorizados($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }
        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [5])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function rechazados($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }
        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [6])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function compensados($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }
        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [7])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function cancelados($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }
        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [8])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function rechazadosBanco($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }
        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [9,12,13])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function noVisados($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }
        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [14])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function pagadosBanco($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }
        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [15])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function enviadosBanco($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }
        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [17])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function reemplazados($id_usuario)
    {
        $permisos = array();
        
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
        ->where('roles.objeto', "Modulo Consultor")
        ->where('UsuarioPerfil.id_usuario', $id_usuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
            }
        }
        $flujos = array();

        if($consultor > 0){
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
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$id_usuario." 
                and activo = 1 and eliminado = 0) as marcarRecordado
                "
            )
            ->whereIn('Flujo.estado', [16])
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

            $j=0;
            foreach($ListaFlujosEstado as $item){
                $flujos[$j] = $item;
                $j += 1;
            }
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }
}
