<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Flujos;
use App\Models\RestriccionEmpresa;
use App\Models\UsuarioRestriccionEmpresa;
use App\Models\UsuarioRestriccionTexto;
use App\Models\UsuarioGrupo;
use App\Models\Politicas;
use Carbon\Carbon;

class ReportesController extends Controller
{
    public function pendientesreporte(Request $request, $id)
    {
        if($request->fechaInicial == "" && $request->fechaFinal == "")
        {
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $yearConsulta = $fechaActualTmp->year;
            $mesConsulta =  $fechaActualTmp->month;
            if($request->year > 0){
                $yearConsulta = $request->year;
            }
            if($request->mes > 0){
                $mesConsulta = $request->mes;
            }
            $diaInicialConsulta = Carbon::create($yearConsulta, $mesConsulta)->startOfMonth()->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($yearConsulta, $mesConsulta)->lastOfMonth()->format('Y-m-d');    
        }else{
            $diaInicialConsulta = Carbon::create($request->fechaInicial)->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($request->fechaFinal)->format('Y-m-d');    
        }
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id)
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
        ->where('id_usuario',$id)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $pagos = Flujos::leftJoin('FlujoDetalle', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo')
            ->on('FlujoDetalle.IdEstadoFlujo','=','Flujo.estado');
        })
        ->leftJoin('EstadoFlujo', function($join){
            $join->on('EstadoFlujo.id_estadoflujo', '=', 'FlujoDetalle.IdEstadoFlujo');
        })
        ->selectRaw(
            "Flujo.doc_num,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s')as doc_date,
            Flujo.comments,
            Flujo.tipo,
            Flujo.doc_total,
            Flujo.doc_curr,
            EstadoFlujo.descripcion as estado,
            Flujo.dias_credito,
            Flujo.dias_credito - TIMESTAMPDIFF(DAY, Flujo.doc_date, DATE_ADD(NOW(), INTERVAL 1 HOUR)) as dias_vencimiento,
            MAX(FlujoDetalle.NivelAutorizo) as nivel,
            ((TIMESTAMPDIFF(DAY, Flujo.doc_date, DATE_ADD(NOW(), INTERVAL 1 HOUR))*100)/Flujo.dias_credito) as porcentaje"
        )
        ->where('Flujo.estado', '<', 5)
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereBetween('Flujo.doc_date',[$diaInicialConsulta, $diaFinalConsulta])
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')
        ->groupBy('Flujo.id_flujo',
        'Flujo.doc_num',
        'Flujo.doc_date',
        'Flujo.comments',
        'Flujo.doc_total',
        'Flujo.doc_curr',
        'Flujo.tipo',
        'EstadoFlujo.descripcion',
        'Flujo.dias_credito')  
        ->get();

        foreach($pagos as $item){
            if($item['dias_vencimiento'] < 0){
                $item['dias_vencimiento'] = 0;
            }
            if($item['porcentaje'] == null){
                $item['porcentaje'] = 0;
            }else{
                $item['porcentaje'] = (float)$item['porcentaje'];
            }
            if($item['nivel'] > 0){
                $item['estado'] = 'Autorizado nivel '.$item['nivel'];
            }else{
                $item['estado'] = $item['estado'];
            }
        }
        
        $datos = array();
        $datos['flujos'] = $pagos;
        return $datos;        
    }

    public function canceladosreporte(Request $request, $id)
    {
        if($request->fechaInicial == "" && $request->fechaFinal == "")
        {
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $yearConsulta = $fechaActualTmp->year;
            $mesConsulta =  $fechaActualTmp->month;
            if($request->year > 0){
                $yearConsulta = $request->year;
            }
            if($request->mes > 0){
                $mesConsulta = $request->mes;
            }
            $diaInicialConsulta = Carbon::create($yearConsulta, $mesConsulta)->startOfMonth()->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($yearConsulta, $mesConsulta)->lastOfMonth()->format('Y-m-d');    
        }else{
            $diaInicialConsulta = Carbon::create($request->fechaInicial)->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($request->fechaFinal)->format('Y-m-d');    
        }

        $campoFiltro = "Flujo.doc_date";
        if($request->campo == "Fecha"){
            $campoFiltro = "FlujoDetalle.Fecha";
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id)
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
        ->where('id_usuario',$id)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $pagos = Flujos::join('FlujoDetalle', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })
        ->selectRaw(
            "Flujo.empresa_nombre,
            Flujo.doc_num,
            Flujo.dfl_account,
            Flujo.en_favor_de,
            Flujo.comments,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.tipo,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s') as doc_date,
            DATE_FORMAT(FlujoDetalle.Fecha,'%Y-%m-%dT%H:%i:%s') as fecha"
        )
        ->where('FlujoDetalle.IdEstadoFlujo', 8)
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereBetween($campoFiltro,[$diaInicialConsulta, $diaFinalConsulta])
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();
        
        $datos = array();
        $datos['flujos'] = $pagos;
        return $datos;        
    }

    public function rechazadosreporte(Request $request, $id)
    {
        if($request->fechaInicial == "" && $request->fechaFinal == "")
        {
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $yearConsulta = $fechaActualTmp->year;
            $mesConsulta =  $fechaActualTmp->month;
            if($request->year > 0){
                $yearConsulta = $request->year;
            }
            if($request->mes > 0){
                $mesConsulta = $request->mes;
            }
            $diaInicialConsulta = Carbon::create($yearConsulta, $mesConsulta)->startOfMonth()->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($yearConsulta, $mesConsulta)->lastOfMonth()->format('Y-m-d');    
        }else{
            $diaInicialConsulta = Carbon::create($request->fechaInicial)->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($request->fechaFinal)->format('Y-m-d');    
        }

        $campoFiltro = "Flujo.doc_date";
        if($request->campo == "Fecha"){
            $campoFiltro = "FlujoDetalle.Fecha";
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id)
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
        ->where('id_usuario',$id)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $pagos = Flujos::join('FlujoDetalle', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })
        ->selectRaw(
            "Flujo.empresa_nombre,
            Flujo.doc_num,
            Flujo.dfl_account,
            Flujo.en_favor_de,
            Flujo.comments,
            Flujo.doc_total,
            Flujo.doc_curr,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s')as doc_date,
            Flujo.tipo,
            DATE_FORMAT(FlujoDetalle.Fecha,'%Y-%m-%dT%H:%i:%s') as fecha,
            FlujoDetalle.Comentario"
        )
        ->where('FlujoDetalle.IdEstadoFlujo', 9)
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereBetween($campoFiltro,[$diaInicialConsulta, $diaFinalConsulta])
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();
        
        $datos = array();
        $datos['flujos'] = $pagos;
        return $datos;        
    }

    public function pendientesvalidacionreporte(Request $request, $id)
    {
        if($request->fechaInicial == "" && $request->fechaFinal == "")
        {
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $yearConsulta = $fechaActualTmp->year;
            $mesConsulta =  $fechaActualTmp->month;
            if($request->year > 0){
                $yearConsulta = $request->year;
            }
            if($request->mes > 0){
                $mesConsulta = $request->mes;
            }
            $diaInicialConsulta = Carbon::create($yearConsulta, $mesConsulta)->startOfMonth()->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($yearConsulta, $mesConsulta)->lastOfMonth()->format('Y-m-d');    
        }else{
            $diaInicialConsulta = Carbon::create($request->fechaInicial)->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($request->fechaFinal)->format('Y-m-d');    
        }

        $campoFiltro = "Flujo.doc_date";
        if($request->campo == "Fecha"){
            $campoFiltro = "FlujoDetalle.Fecha";
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id)
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
        ->where('id_usuario',$id)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $pagosEnFlujo = Flujos::leftJoin('FlujoDetalle', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo')
            ->on('FlujoDetalle.IdEstadoFlujo','=','Flujo.estado');
        })
        ->leftJoin('EstadoFlujo', function($join){
            $join->on('EstadoFlujo.id_estadoflujo', '=', 'FlujoDetalle.IdEstadoFlujo');
        })
        ->selectRaw(
            "Flujo.id_flujo,
            Flujo.empresa_nombre,
            Flujo.doc_num,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s') as doc_date,
            Flujo.en_favor_de,
            Flujo.comments,
            Flujo.doc_total,
            Flujo.id_grupoautorizacion,
            Flujo.nivel,
            Flujo.estado,
            Flujo.tipo,
            DATE_FORMAT(max(FlujoDetalle.Fecha),'%Y-%m-%dT%H:%i:%s') as fecha_asignacion,
            TIMESTAMPDIFF(DAY, Flujo.doc_date, DATE_ADD(NOW(), INTERVAL 1 HOUR)) as dias"
        )
        ->whereIn('Flujo.estado', [3,4])
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereBetween($campoFiltro,[$diaInicialConsulta, $diaFinalConsulta])
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')
        ->groupBy('Flujo.id_flujo',
        'Flujo.empresa_nombre',
        'Flujo.doc_num',
        'Flujo.doc_date',
        'Flujo.en_favor_de',
        'Flujo.comments',
        'Flujo.doc_total',
        'Flujo.id_grupoautorizacion',
        'Flujo.nivel',
        'Flujo.estado',
        'Flujo.tipo') 
        ->get();

        $listaGrupos = array();

        foreach($pagosEnFlujo as $item){
            $listaGrupos[] = $item->id_grupoautorizacion;
        }

        $usuariogrupo = UsuarioGrupo::join('GrupoAutorizacion', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion',
                '=',
                'GrupoAutorizacion.id_grupoautorizacion'
            );
        })->join('usuarios', function($join){
            $join->on('usuarios.id_usuario','=','UsuarioGrupoAutorizacion.id_usuario');
        })
        ->selectRaw(
            "UsuarioGrupoAutorizacion.id_usuariogrupo,
             UsuarioGrupoAutorizacion.id_usuario,
             UsuarioGrupoAutorizacion.id_grupoautorizacion,
             GrupoAutorizacion.identificador,
             GrupoAutorizacion.descripcion,
             UsuarioGrupoAutorizacion.nivel,
             UsuarioGrupoAutorizacion.activo,
             UsuarioGrupoAutorizacion.eliminado,
             usuarios.nombre_usuario as nombre_usuario,
             usuarios.nombre as nombre,
             usuarios.apellido as apellido"
        )
        ->whereIn('UsuarioGrupoAutorizacion.id_grupoautorizacion', $listaGrupos)
        ->where('UsuarioGrupoAutorizacion.activo', 1)
        ->where('UsuarioGrupoAutorizacion.eliminado', 0)
        ->where('usuarios.activo', 1)
        ->where('usuarios.eliminado', 0)
        ->orderBy('UsuarioGrupoAutorizacion.id_usuariogrupo')
        ->get();

        $pagos = array();

        foreach($pagosEnFlujo as $item){
            foreach($usuariogrupo as $itemUsuario){
                if(($item->id_grupoautorizacion == $itemUsuario->id_grupoautorizacion && $item->nivel == $itemUsuario->nivel && $item->estado == 4) || ($item->id_grupoautorizacion == $itemUsuario->id_grupoautorizacion && $item->nivel == 0 && $itemUsuario->nivel == 1 && $item->estado == 3)){
                    $nivelActual = "";
                    if($item->nivel == 0){
                        $nivelActual = "Asignado";
                    }else{
                        $nivelActual = $item->nivel -1;
                    }

                    $pagos[] =  [ 
                        "id_flujo" => $item->id_flujo,
                        "empresa_nombre" => $item->empresa_nombre,
                        "doc_num" => $item->doc_num,
                        "doc_date" => $item->doc_date,
                        "en_favor_de" => $item->en_favor_de,
                        "comments" => $item->comments,
                        "doc_total" => $item->doc_total,
                        "fecha_asignacion" => $item->fecha_asignacion,
                        "nombre_usuario" => $itemUsuario->nombre . ' ' . $itemUsuario->apellido,
                        "dias" => $item->dias,
                        "tipo" => $item->tipo,
                        "nivel" => $nivelActual
                        ];
                }
            }
        }

        $datos['flujos'] = $pagos;
        return $datos;        
    }

    public function compensadosreporte(Request $request, $id)
    {
        if($request->fechaInicial == "" && $request->fechaFinal == "")
        {
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $yearConsulta = $fechaActualTmp->year;
            $mesConsulta =  $fechaActualTmp->month;
            if($request->year > 0){
                $yearConsulta = $request->year;
            }
            if($request->mes > 0){
                $mesConsulta = $request->mes;
            }
            $diaInicialConsulta = Carbon::create($yearConsulta, $mesConsulta)->startOfMonth()->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($yearConsulta, $mesConsulta)->lastOfMonth()->format('Y-m-d');    
        }else{
            $diaInicialConsulta = Carbon::create($request->fechaInicial)->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($request->fechaFinal)->format('Y-m-d');    
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id)
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
        ->where('id_usuario',$id)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $pagosCompensados = Flujos::selectRaw(
            "Flujo.empresa_nombre,
            Flujo.doc_num,
            Flujo.dfl_account,
            Flujo.en_favor_de,
            Flujo.comments,
            Flujo.doc_total,
            Flujo.doc_curr,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s')as doc_date,
            Flujo.tipo,
            (select ef.descripcion from EstadoFlujo as ef where ef.id_estadoflujo = Flujo.estado) as nombre_estado,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 7
            and fd.IdFlujo = Flujo.id_flujo) as fecha,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date"
        )
        ->where('Flujo.estado', 7)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get()->toArray();

        $pagosEnviados = Flujos::selectRaw(
            "Flujo.empresa_nombre,
            Flujo.doc_num,
            Flujo.dfl_account,
            Flujo.en_favor_de,
            Flujo.comments,
            Flujo.doc_total,
            Flujo.doc_curr,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s')as doc_date,
            Flujo.tipo,
            (select ef.descripcion from EstadoFlujo as ef where ef.id_estadoflujo = Flujo.estado) as nombre_estado,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 17
            and fd.IdFlujo = Flujo.id_flujo) as fecha,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date"
        )
        ->where('Flujo.estado', 17)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get()->toArray();

        $pagosAceptados = Flujos::selectRaw(
            "Flujo.empresa_nombre,
            Flujo.doc_num,
            Flujo.dfl_account,
            Flujo.en_favor_de,
            Flujo.comments,
            Flujo.doc_total,
            Flujo.doc_curr,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s')as doc_date,
            Flujo.tipo,
            (select ef.descripcion from EstadoFlujo as ef where ef.id_estadoflujo = Flujo.estado) as nombre_estado,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 15
            and fd.IdFlujo = Flujo.id_flujo) as fecha,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date"
        )
        ->where('Flujo.estado', 15)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get()->toArray();
        
        $pagos = array_merge($pagosCompensados, $pagosEnviados, $pagosAceptados);

        $datos = array();
        if($request->campo == "doc_date"){
            $new = array_filter($pagos, function ($var) use ($diaInicialConsulta, $diaFinalConsulta) {
                return ($var['doc_date'] >= $diaInicialConsulta && $var['doc_date'] <= $diaFinalConsulta );
            });
        }else if($request->campo == "aut_date"){
            $new = array_filter($pagos, function ($var) use ($diaInicialConsulta, $diaFinalConsulta) {
                return ($var['aut_date'] >= $diaInicialConsulta && $var['aut_date'] <= $diaFinalConsulta );
            });
        }else{
            $new = array_filter($pagos, function ($var) use ($diaInicialConsulta, $diaFinalConsulta) {
                return ($var['fecha'] >= $diaInicialConsulta && $var['fecha'] <= $diaFinalConsulta );
            });
        }
        $pagosNew = array();

        foreach($new as $item){
            $pagosNew[] = $item;
        }
        $datos['flujos'] = $pagosNew;
        return $datos;        
    }

    public function novisadoreporte(Request $request, $id)
    {
        if($request->fechaInicial == "" && $request->fechaFinal == "")
        {
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $yearConsulta = $fechaActualTmp->year;
            $mesConsulta =  $fechaActualTmp->month;
            if($request->year > 0){
                $yearConsulta = $request->year;
            }
            if($request->mes > 0){
                $mesConsulta = $request->mes;
            }
            $diaInicialConsulta = Carbon::create($yearConsulta, $mesConsulta)->startOfMonth()->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($yearConsulta, $mesConsulta)->lastOfMonth()->format('Y-m-d');    
        }else{
            $diaInicialConsulta = Carbon::create($request->fechaInicial)->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($request->fechaFinal)->format('Y-m-d');    
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id)
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
        ->where('id_usuario',$id)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $pagos = Flujos::join('FlujoDetalle', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })
        ->selectRaw(
            "Flujo.empresa_nombre,
            Flujo.doc_num,
            Flujo.dfl_account,
            Flujo.en_favor_de,
            Flujo.comments,
            Flujo.doc_total,
            Flujo.doc_curr,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s')as doc_date,
            Flujo.tipo,
            DATE_FORMAT(FlujoDetalle.Fecha,'%Y-%m-%dT%H:%i:%s') as fecha,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date"
        )
        ->where('FlujoDetalle.IdEstadoFlujo', 14)
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->whereBetween('Flujo.doc_date',[$diaInicialConsulta, $diaFinalConsulta])
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();
        
        $datos = array();
        $datos['flujos'] = $pagos;
        return $datos;        
    }

    public function reemplazosreporte(Request $request, $id)
    {
        if($request->fechaInicial == "" && $request->fechaFinal == "")
        {
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $yearConsulta = $fechaActualTmp->year;
            $mesConsulta =  $fechaActualTmp->month;
            if($request->year > 0){
                $yearConsulta = $request->year;
            }
            if($request->mes > 0){
                $mesConsulta = $request->mes;
            }
            $diaInicialConsulta = Carbon::create($yearConsulta, $mesConsulta)->startOfMonth()->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($yearConsulta, $mesConsulta)->lastOfMonth()->format('Y-m-d');    
        }else{
            $diaInicialConsulta = Carbon::create($request->fechaInicial)->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($request->fechaFinal)->format('Y-m-d');    
        }
        
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id)
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
        ->where('id_usuario',$id)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $pagos = Flujos::join('FlujoDetalle', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })
        ->selectRaw(
            "Flujo.empresa_nombre,
            Flujo.doc_num,
            Flujo.dfl_account,
            Flujo.en_favor_de,
            Flujo.comments,
            Flujo.doc_total,
            Flujo.doc_curr,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s')as doc_date,
            Flujo.tipo,
            DATE_FORMAT(FlujoDetalle.Fecha,'%Y-%m-%dT%H:%i:%s') as fecha,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%dT%H:%i:%s') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date"
        )
        ->where('FlujoDetalle.IdEstadoFlujo', 16)
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->whereBetween('Flujo.doc_date',[$diaInicialConsulta, $diaFinalConsulta])
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();
        
        $datos = array();
        $datos['flujos'] = $pagos;
        return $datos;        
    }

    public function pendientecompensarreporte(Request $request, $id)
    {
        if($request->fechaInicial == "" && $request->fechaFinal == "")
        {
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $yearConsulta = $fechaActualTmp->year;
            $mesConsulta =  $fechaActualTmp->month;
            if($request->year > 0){
                $yearConsulta = $request->year;
            }
            if($request->mes > 0){
                $mesConsulta = $request->mes;
            }
            $diaInicialConsulta = Carbon::create($yearConsulta, $mesConsulta)->startOfMonth()->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($yearConsulta, $mesConsulta)->lastOfMonth()->format('Y-m-d');    
        }else{
            $diaInicialConsulta = Carbon::create($request->fechaInicial)->format('Y-m-d');
            $diaFinalConsulta = Carbon::create($request->fechaFinal)->format('Y-m-d');    
        }

        $campoFiltro = "Flujo.doc_date";
        if($request->campo == "Fecha"){
            $campoFiltro = "FlujoDetalle.Fecha";
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id)
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
        ->where('id_usuario',$id)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $pagos = Flujos::join('FlujoDetalle', function($join){
            $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo');
        })
        ->selectRaw(
            "Flujo.empresa_nombre,
            Flujo.doc_num,
            Flujo.tipo,
            Flujo.dfl_account,
            Flujo.en_favor_de,
            Flujo.comments,
            Flujo.doc_total,
            Flujo.doc_curr,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s') as doc_date,
            DATE_FORMAT(FlujoDetalle.Fecha,'%Y-%m-%dT%H:%i:%s') as fecha"
        )
        ->where('FlujoDetalle.IdEstadoFlujo', 5)
        ->where('FlujoDetalle.FlujoActivo', 1)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereBetween($campoFiltro,[$diaInicialConsulta, $diaFinalConsulta])
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();
        
        $datos = array();
        $datos['flujos'] = $pagos;
        return $datos;        
    }

    public function graficoSemaforoIndividual(Request $request)
    {        
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$request->id_usuario)
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
        ->where('id_usuario',$request->id_usuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $usuariogrupo = UsuarioGrupo::join('GrupoAutorizacion', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=',
            'GrupoAutorizacion.id_grupoautorizacion');
        })
        ->select('UsuarioGrupoAutorizacion.id_grupoautorizacion', 'UsuarioGrupoAutorizacion.nivel')
        ->where('UsuarioGrupoAutorizacion.id_usuario', $request->id_usuario)
        ->where('UsuarioGrupoAutorizacion.activo', 1)->where('UsuarioGrupoAutorizacion.eliminado', 0)
        ->where('GrupoAutorizacion.activo', 1)->where('GrupoAutorizacion.eliminado', 0)
        ->get();

        $ListaGruposUsuarios = array();
        $flujos = array();
        if($usuariogrupo->count()>0){
            $ListaGruposUsuarios = $usuariogrupo->toArray();
        }
        $i = 0;
        $grupos = array();
        foreach($ListaGruposUsuarios as $item){
            $grupos[$i] = $item['id_grupoautorizacion'];
            $i += 1;
        }
        
        $ListaFlujosGrupo = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_grupoautorizacion,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s')as doc_date,
            Flujo.nivel,
            Flujo.dias_credito,
            Flujo.estado,
            Flujo.ConDuda
            "
        )
        ->where('Flujo.tipo', $request->tipo)
        ->whereIn('Flujo.id_grupoautorizacion', $grupos)
        ->where('Flujo.estado', '<', 5)
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

        $politicaVerde = Politicas::where('identificador','=','_SEMAFORO_VERDE')
        ->where('activo',1)->where('eliminado',0)->first();
        $valorVerde = intval($politicaVerde->valor);

        $politicaAmarillo = Politicas::where('identificador','=','_SEMAFORO_AMARILLO')
        ->where('activo',1)->where('eliminado',0)->first();
        $valorAmarillo = intval($politicaAmarillo->valor);

        $j = 0;
        foreach($ListaGruposUsuarios as $item){
            foreach($ListaFlujosGrupo as $itemFlujo){
                if($item['id_grupoautorizacion'] == $itemFlujo['id_grupoautorizacion'] && 
                $item['nivel'] == $itemFlujo['nivel']){
                    $flujos[$j] = $itemFlujo;
                    $j += 1;
                }elseif($item['id_grupoautorizacion'] == $itemFlujo['id_grupoautorizacion'] && 
                $itemFlujo['estado'] == 3 && $itemFlujo['nivel'] == 0 && $item['nivel'] == 1){
                    $flujos[$j] = $itemFlujo;
                    $j += 1;
                }
            }
        }  

        $totalRojo = 0;
        $totalAmarillo = 0;
        $totalVerde = 0;
        $totalAzul = 0;

        foreach($flujos as $item){
            $diasCredito = intval($item['dias_credito']);
            $fechaDocumentoTmp = strtotime($item['doc_date']);
            $fechaDocumentoTmp2 = date('Y-m-d',$fechaDocumentoTmp);
            $fechaDocumento = date_create($fechaDocumentoTmp2);
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $fechaActualTmp2 = strtotime($fechaActualTmp);
            $fechaActualTmp3 = date('Y-m-d',$fechaActualTmp2);
            $fechaActual = date_create($fechaActualTmp3);
            $diferencia = (array) date_diff($fechaDocumento,$fechaActual);
            $diasDesdeCreacion = $diferencia['days'];
            $porcentaje = 100;
            if($item['ConDuda']== 1){
                $totalAzul++;
            }else{
                if($diasCredito > 0){
                    $porcentaje = intval(($diasDesdeCreacion * 100) / $diasCredito);
                }
                if($porcentaje <= $valorVerde){
                    $totalVerde++;
                }
                if($porcentaje > $valorVerde && $porcentaje <= $valorAmarillo){
                    $totalAmarillo++;
                }
                if($porcentaje > $valorAmarillo){
                    $totalRojo++;
                }           
            }
        }
        $datos = array();
        $datos[] = array(
            "nombreSemaforo" => "ROJO",
            "cantidad" => $totalRojo
        );
        $datos[] = array(
            "nombreSemaforo" => "AMARILLO",
            "cantidad" => $totalAmarillo
        );
        $datos[] = array(
            "nombreSemaforo" => "VERDE",
            "cantidad" => $totalVerde
        );
        $datos[] = array(
            "nombreSemaforo" => "Con Duda",
            "cantidad" => $totalAzul
        );
		$datosFinal = array();
        $datosFinal['flujos'] = $datos;
        return $datosFinal;
    }

    public function graficoSemaforo(Request $request){

        
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$request->id_usuario)
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
        ->where('id_usuario',$request->id_usuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $flujos = array();
        
        $ListaFlujosGrupo = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_grupoautorizacion,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%dT%H:%i:%s')as doc_date,
            Flujo.nivel,
            Flujo.dias_credito,
            Flujo.estado,
            Flujo.ConDuda
            "
        )
        ->where('Flujo.estado', '<', 5)
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

        $politicaVerde = Politicas::where('identificador','=','_SEMAFORO_VERDE')
        ->where('activo',1)->where('eliminado',0)->first();
        $valorVerde = intval($politicaVerde->valor);

        $politicaAmarillo = Politicas::where('identificador','=','_SEMAFORO_AMARILLO')
        ->where('activo',1)->where('eliminado',0)->first();
        $valorAmarillo = intval($politicaAmarillo->valor);

        $j = 0;
        foreach($ListaFlujosGrupo as $itemFlujo){
            $flujos[$j] = $itemFlujo;
            $j += 1;
        }

        $totalRojo = 0;
        $totalAmarillo = 0;
        $totalVerde = 0;
        $totalAzul = 0;

        foreach($flujos as $item){
            $diasCredito = intval($item['dias_credito']);
            $fechaDocumentoTmp = strtotime($item['doc_date']);
            $fechaDocumentoTmp2 = date('Y-m-d',$fechaDocumentoTmp);
            $fechaDocumento = date_create($fechaDocumentoTmp2);
            $fechaActualTmp = Carbon::now('America/Guatemala');
            $fechaActualTmp2 = strtotime($fechaActualTmp);
            $fechaActualTmp3 = date('Y-m-d',$fechaActualTmp2);
            $fechaActual = date_create($fechaActualTmp3);
            $diferencia = (array) date_diff($fechaDocumento,$fechaActual);
            $diasDesdeCreacion = $diferencia['days'];
            $porcentaje = 100;
            if($item['ConDuda']== 1){
                $totalAzul++;
            }else{
                if($diasCredito > 0){
                    $porcentaje = intval(($diasDesdeCreacion * 100) / $diasCredito);
                }
                if($porcentaje <= $valorVerde){
                    $totalVerde++;
                }
                if($porcentaje > $valorVerde && $porcentaje <= $valorAmarillo){
                    $totalAmarillo++;
                }
                if($porcentaje > $valorAmarillo){
                    $totalRojo++;
                }           
            }
        }
        $datos = array();
        $datos[] = array(
            "nombreSemaforo" => "ROJO",
            "cantidad" => $totalRojo
        );
        $datos[] = array(
            "nombreSemaforo" => "AMARILLO",
            "cantidad" => $totalAmarillo
        );
        $datos[] = array(
            "nombreSemaforo" => "VERDE",
            "cantidad" => $totalVerde
        );
        $datos[] = array(
            "nombreSemaforo" => "Con Duda",
            "cantidad" => $totalAzul
        );
		$datosFinal = array();
        $datosFinal['flujos'] = $datos;
        return $datosFinal;
    }
}