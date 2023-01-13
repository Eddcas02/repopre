<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecordatorioUsuario;
use App\Models\UsuarioRecordatorioGrupo;
use App\Models\Flujos;
use App\Models\UsuarioGrupo;
use App\Models\Bitacora;
use Carbon\Carbon;

class RecordatorioUsuarioController extends Controller
{
    public function index($id)
    {
        $recordatorioUsuario = RecordatorioUsuario::join('Flujo', function($join){
            $join->on('Flujo.id_flujo', '=',
            'RecordatorioUsuario.id_flujo');
        })->selectRaw(
            "
            RecordatorioUsuario.id_recordatoriousuario,
            RecordatorioUsuario.id_flujo,
            RecordatorioUsuario.id_usuario,
            RecordatorioUsuario.activo,
            RecordatorioUsuario.eliminado,
            RecordatorioUsuario.id_usuario_origen
            "
        )
        ->where('RecordatorioUsuario.id_usuario','=',$id)
        ->where('Flujo.estado', '<', 5)
        ->where('RecordatorioUsuario.activo',1)
        ->where('RecordatorioUsuario.eliminado', 0)->get();
        $datos = array();
        $datos['recordatorioUsuario'] = $recordatorioUsuario;
        \DB::disconnect('mysql');
        return $datos;
    }

    public function store(Request $request)
    {
        $flujo = Flujos::where('id_flujo','=', $request->id_flujo)->first();
        $nivel = 0;
        if($flujo->estado == 3){
            $nivel = 1;
        }
        if($flujo->estado == 4){
            $nivel = $flujo->nivel;
        }


        $usuariosEnvia = UsuarioGrupo::where('id_grupoautorizacion','=',$flujo->id_grupoautorizacion)
        ->where('nivel','=',$nivel)
        ->where('activo','=',1)->where('eliminado','=',0)
        ->where('id_usuario','=',$request->id_usuario)->get();

        if(count($usuariosEnvia) > 0){
            $usuariosNotificar = UsuarioRecordatorioGrupo::selectRaw(
                "UsuarioRecordatorioGrupo.id_usuario_receptor"
            )
            ->where('activo','=',1)->where('eliminado','=',0)
            ->where('id_grupoautorizacion','=',$flujo->id_grupoautorizacion)
            ->where('id_usuario_emisor','=',$request->id_usuario)->get();

            $usuarios = UsuarioGrupo::where('id_grupoautorizacion','=',$flujo->id_grupoautorizacion)
            ->where('nivel','=',$nivel)
            ->where('activo','=',1)->where('eliminado','=',0)
            ->where('id_usuario','<>',$request->id_usuario)
            ->whereIn('id_usuario',$usuariosNotificar)->get();

            foreach($usuarios as $item){
                $recordatorioUsuario = new RecordatorioUsuario;
                $recordatorioUsuario->id_usuario = $item->id_usuario;
                $recordatorioUsuario->id_usuario_origen = $request->id_usuario;
                $recordatorioUsuario->id_flujo = $request->id_flujo;
                $recordatorioUsuario->activo = 1;
                $recordatorioUsuario->eliminado = 0;
                $recordatorioUsuario->save();

                $fechaActual = Carbon::now('America/Guatemala');
                $bitacora = new Bitacora;
                $bitacora->id_usuario = $request->id_usuario;
                $bitacora->fecha_hora = $fechaActual;
                $bitacora->accion = 'crear';
                $bitacora->objeto = 'RecordatorioUsuario';
                $bitacora->parametros_nuevos = 'ID '.$recordatorioUsuario->id_recordatoriousuario;
                $bitacora->save();
            }
        }
        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $recordatorioUsuario = RecordatorioUsuario::find($id);
            $datosAnteriores = json_encode($recordatorioUsuario,true);
            $recordatorioUsuario->activo = $request->activo;
            $recordatorioUsuario->eliminado = 0;
            $recordatorioUsuario->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'RecordatorioUsuario';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $recordatorioUsuario = RecordatorioUsuario::find($id);
            $recordatorioUsuario->eliminado = 1;
            $recordatorioUsuario->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'RecordatorioUsuario';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $recordatorioUsuario->delete();

        return response()->json(null, 204);
    }
}

