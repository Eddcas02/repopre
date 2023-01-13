<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mensajes;
use App\Models\UsuarioAutorizacion;
use App\Models\RestriccionEmpresa;
use App\Models\UsuarioRestriccionEmpresa;
use App\Models\UsuarioRestriccionTexto;
use App\Models\Flujos;
use App\Models\Usuarios;
use App\Models\Politicas;
use App\Models\FlujoDetalle;

class MensajesController extends Controller
{
    public function showchat($id_pago, $id)
    {
        $mensajes = Mensajes::join('usuarios', function($join){
            $join->on('usuarios.id_usuario', '=', 'Mensaje.id_usuarioenvia');
        })->join('Flujo', function($join){
            $join->on('Flujo.id_flujo', '=', 'Mensaje.id_flujo');
        })
        ->selectRaw(
            "Mensaje.id_mensaje,
             Mensaje.id_flujo,
             Mensaje.id_usuarioenvia,
             Mensaje.id_usuariorecibe,
             DATE_FORMAT(Mensaje.fecha_hora,'%d-%m-%Y %H:%i')as fecha_hora,
             Mensaje.mensaje,
             usuarios.nombre_usuario as usuarioenvia,
             Mensaje.leido,
             Mensaje.eliminado"
        )
        ->where('Mensaje.eliminado', 0)  
        ->where('Mensaje.id_flujo', $id_pago)
        ->where('Mensaje.id_usuarioenvia', $id)
        ->orWhere('Mensaje.eliminado', 0) 
        ->where('Mensaje.id_flujo', $id_pago)
        ->where('Mensaje.id_usuariorecibe', $id)
        ->orderBy('Mensaje.id_mensaje', 'ASC')
        ->get();
        $datos = array();
        $datos['mensajes'] = $mensajes;
        \DB::disconnect('mysql');
        return $datos;
    }

    public function chatapp($id_pago, $id)
    {
        $mensajes = Mensajes::join('usuarios', function($join){
            $join->on('usuarios.id_usuario', '=', 'Mensaje.id_usuarioenvia');
        })->join('Flujo', function($join){
            $join->on('Flujo.id_flujo', '=', 'Mensaje.id_flujo');
        })
        ->selectRaw(
            "Mensaje.id_mensaje,
             Mensaje.id_flujo,
             Mensaje.id_usuarioenvia,
             Mensaje.id_usuariorecibe, 
             Mensaje.fecha_hora,
             Mensaje.mensaje,
             usuarios.nombre_usuario as usuarioenvia,
             Mensaje.leido"
        )        
        ->where('Mensaje.eliminado', 0)  
        ->where('Mensaje.id_flujo', $id_pago)
        ->where('Mensaje.id_usuarioenvia', $id)
        ->orWhere('Mensaje.eliminado', 0) 
        ->where('Mensaje.id_flujo', $id_pago)
        ->where('Mensaje.id_usuariorecibe', $id)
        ->orderBy('Mensaje.fecha_hora', 'DESC')
        ->orderBy('Mensaje.id_mensaje', 'DESC') 
        ->get();
        $datos = array();
        $datos['mensajes'] = $mensajes;
        \DB::disconnect('mysql');
        return $datos;
    }

    public function showcontador($id_pago, $id)
    {
        $mensajes = Mensajes::join('Flujo', function($join){
            $join->on('Flujo.id_flujo', '=', 'Mensaje.id_flujo');
        })
        ->selectRaw(
            "Mensaje.mensaje,
             Mensaje.id_usuarioenvia,
             Mensaje.id_usuariorecibe,
             Mensaje.leido,
             Mensaje.eliminado"
        )
        ->orderBy('Mensaje.id_mensaje', 'ASC')
        ->where('Mensaje.id_flujo', $id_pago)
        ->where('Mensaje.id_usuariorecibe', $id)
        ->where('Mensaje.leido', 0)
        ->get();
        $datos = array();
        $datos['mensajes'] = $mensajes;
        \DB::disconnect('mysql');
        return $datos;
    }

    public function showrecibidos($id)
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

        $mensajes = Mensajes::join('usuarios', function($join){
            $join->on('usuarios.id_usuario', '=', 'Mensaje.id_usuarioenvia');
        })->join('Flujo', function($join){
            $join->on('Flujo.id_flujo', '=', 'Mensaje.id_flujo');
        })
        ->selectRaw(
            "Mensaje.id_mensaje,
             Mensaje.id_flujo as IdFlujo,
             Mensaje.id_usuarioenvia,
             Mensaje.id_usuariorecibe,
             DATE_FORMAT(Mensaje.fecha_hora,'%d-%m-%Y %H:%i')as fecha_hora,
             DATE_FORMAT(Flujo.doc_date,'%d-%m-%Y') as doc_date,
             Mensaje.mensaje,
             usuarios.nombre_usuario as usuarioenvia,
             Flujo.doc_num as Pago,
             Flujo.id_grupoautorizacion as IdGrupo,
             Flujo.tipo,
             Flujo.comments,
             Flujo.nivel,
             Flujo.estado,
             Flujo.activo,
             Mensaje.leido,
             Mensaje.eliminado,
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
        ->where('Mensaje.id_usuariorecibe', $IdUsuario)
        //->where('Mensaje.leido', 0)
        ->where('Flujo.estado', '<', 5)
        ->where('Flujo.activo','=',1)->where('Flujo.eliminado','=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Mensaje.fecha_hora', 'DESC')
        ->get();
        $datos = array();
        $datos['mensajes'] = $mensajes;
        \DB::disconnect('mysql');
        return $datos;
    }

    public function store(Request $request)
    {
        $mensajes = new Mensajes;
        $mensajes->id_flujo = $request->id_flujo;
        $mensajes->id_usuarioenvia = $request->id_usuarioenvia;
        $mensajes->id_usuariorecibe = $request->id_usuariorecibe;
        $mensajes->fecha_hora = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
        $mensajes->mensaje = $request->mensaje;
        $mensajes->leido = 0;
        $mensajes->activo = 1;
        $mensajes->eliminado = 0;
        $mensajes->save();

        $flujo = Flujos::where('id_flujo',$request->id_flujo)->first();
        //Validamos que el pago no esté marcado ya como "con duda"
        if($flujo->ConDuda == 0){
            $usuarioEnvia = Usuarios::where('id_usuario', $request->id_usuarioenvia)->first();
            $politicasMarcarDuda = Politicas::where('identificador', '_TRASLADO_A_DUDA_POR_MENSAJE_')
            ->get();

            foreach($politicasMarcarDuda as $item){
                if(strtoupper(trim($item->valor)) == strtoupper(trim($usuarioEnvia->nombre_usuario))){
                    $flujo->ConDuda = 1;
                    $flujo->save();
            
                    $flujoDetalleCD = new FlujoDetalle;
                    $flujoDetalleCD->IdFlujo = $request->id_flujo;
                    $flujoDetalleCD->IdEstadoFlujo = 18;
                    $flujoDetalleCD->IdUsuario = $request->id_usuarioenvia;
                    $flujoDetalleCD->Fecha = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                    $flujoDetalleCD->Comentario = "Marcado con duda por usuario";
                    $flujoDetalleCD->NivelAutorizo = 0;
                    $flujoDetalleCD->FlujoActivo = 0;
                    $flujoDetalleCD->save();
                }
            }
        }

        return response()->json("OK");
    }

    public function update(Request $request, $opcion)
    {
        if ($opcion == '1') {
            Mensajes::where('leido', 0)
            ->where('id_flujo', $request->id_flujo)
            ->where('id_usuarioenvia', $request->id_usuariorecibe)
            ->where('id_usuariorecibe', $request->id_usuarioenvia)
            ->update(['leido' => 1]);
            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $mensajes->delete();

        return response()->json(null, 204);
    }
}
