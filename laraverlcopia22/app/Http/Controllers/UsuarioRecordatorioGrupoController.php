<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsuarioRecordatorioGrupo;
use App\Models\UsuarioGrupo;
use App\Models\Bitacora;
use App\Models\RecordatorioUsuario;
use App\Models\Flujos;
use Carbon\Carbon;

class UsuarioRecordatorioGrupoController extends Controller
{
    public function index()
    {
        $UsuarioRecordatorioGrupo = UsuarioRecordatorioGrupo::join('usuarios as usrEmi', function($join){
            $join->on('usrEmi.id_usuario', '=', 'UsuarioRecordatorioGrupo.id_usuario_emisor');
        })->join('GrupoAutorizacion as GrupAut', function($join){
            $join->on('GrupAut.id_grupoautorizacion', '=', 'UsuarioRecordatorioGrupo.id_grupoautorizacion');
        })->join('usuarios as usrRec', function($join){
            $join->on('usrRec.id_usuario', '=', 'UsuarioRecordatorioGrupo.id_usuario_receptor');
        })->selectRaw(
            "
            UsuarioRecordatorioGrupo.id_usuariorecordatoriogrupo,
            UsuarioRecordatorioGrupo.id_usuario_emisor,
            usrEmi.nombre_usuario as usuario_emi,
            usrEmi.nombre as nombre_emi,
            usrEmi.apellido as apellido_emi,
            UsuarioRecordatorioGrupo.id_usuario_receptor,
            usrRec.nombre_usuario as usuario_rec,
            usrRec.nombre as nombre_rec,
            usrRec.apellido as apellido_rec,
            UsuarioRecordatorioGrupo.id_grupoautorizacion,
            GrupAut.identificador,
            GrupAut.descripcion,
            UsuarioRecordatorioGrupo.activo,
            UsuarioRecordatorioGrupo.eliminado
            "
        )
        ->where('UsuarioRecordatorioGrupo.activo',1)
        ->where('UsuarioRecordatorioGrupo.eliminado', 0)->get();
        $datos = array();
        $datos['recordatorio'] = $UsuarioRecordatorioGrupo;
        return $datos;
    }

    public function show($id)
    {
        $UsuarioRecordatorioGrupo = UsuarioRecordatorioGrupo::join('usuarios as usrEmi', function($join){
            $join->on('usrEmi.id_usuario', '=', 'UsuarioRecordatorioGrupo.id_usuario_emisor');
        })->join('GrupoAutorizacion as GrupAut', function($join){
            $join->on('GrupAut.id_grupoautorizacion', '=', 'UsuarioRecordatorioGrupo.id_grupoautorizacion');
        })->join('usuarios as usrRec', function($join){
            $join->on('usrRec.id_usuario', '=', 'UsuarioRecordatorioGrupo.id_usuario_receptor');
        })->selectRaw(
            "
            UsuarioRecordatorioGrupo.id_usuariorecordatoriogrupo,
            UsuarioRecordatorioGrupo.id_usuario_emisor,
            usrEmi.nombre_usuario as usuario_emi,
            usrEmi.nombre as nombre_emi,
            usrEmi.apellido as apellido_emi,
            UsuarioRecordatorioGrupo.id_usuario_receptor,
            usrRec.nombre_usuario as usuario_rec,
            usrRec.nombre as nombre_rec,
            usrRec.apellido as apellido_rec,
            UsuarioRecordatorioGrupo.id_grupoautorizacion,
            GrupAut.identificador,
            GrupAut.descripcion,
            UsuarioRecordatorioGrupo.activo,
            UsuarioRecordatorioGrupo.eliminado
            "
        )->where('UsuarioRecordatorioGrupo.id_usuario_emisor','=',$id)
        ->where('UsuarioRecordatorioGrupo.activo',1)
        ->where('UsuarioRecordatorioGrupo.eliminado', 0)->get();
        $datos = array();
        $datos['recordatorio'] = $UsuarioRecordatorioGrupo;
        return $datos;
    }

    public function showByGroup($id, $id_flujo)
    {
        $id_grupo = 0;

        $TieneRecordatorio = RecordatorioUsuario::where('id_flujo', $id_flujo)
        ->where('activo',1)
        ->where(function($query) use($id){
            $query->where('id_usuario', $id)
            ->orWhere('id_usuario_origen', $id);
        })->get();

        if($TieneRecordatorio->count() == 0){
            $flujo = Flujos::where('id_flujo', $id_flujo)->first();
            $id_grupo = $flujo->id_grupoautorizacion;
        }

        $UsuarioRecordatorioGrupo = UsuarioRecordatorioGrupo::join('usuarios as usrEmi', function($join){
            $join->on('usrEmi.id_usuario', '=', 'UsuarioRecordatorioGrupo.id_usuario_emisor');
        })->join('GrupoAutorizacion as GrupAut', function($join){
            $join->on('GrupAut.id_grupoautorizacion', '=', 'UsuarioRecordatorioGrupo.id_grupoautorizacion');
        })->join('usuarios as usrRec', function($join){
            $join->on('usrRec.id_usuario', '=', 'UsuarioRecordatorioGrupo.id_usuario_receptor');
        })->selectRaw(
            "
            UsuarioRecordatorioGrupo.id_usuariorecordatoriogrupo,
            UsuarioRecordatorioGrupo.id_usuario_emisor,
            usrEmi.nombre_usuario as usuario_emi,
            usrEmi.nombre as nombre_emi,
            usrEmi.apellido as apellido_emi,
            UsuarioRecordatorioGrupo.id_usuario_receptor,
            usrRec.nombre_usuario as usuario_rec,
            usrRec.nombre as nombre_rec,
            usrRec.apellido as apellido_rec,
            UsuarioRecordatorioGrupo.id_grupoautorizacion,
            GrupAut.identificador,
            GrupAut.descripcion,
            UsuarioRecordatorioGrupo.activo,
            UsuarioRecordatorioGrupo.eliminado
            "
        )->where('UsuarioRecordatorioGrupo.id_usuario_emisor','=',$id)
        ->where('UsuarioRecordatorioGrupo.id_grupoautorizacion','=',$id_grupo)
        ->where('UsuarioRecordatorioGrupo.activo',1)
        ->where('UsuarioRecordatorioGrupo.eliminado', 0)->get();
        $datos = array();
        $datos['recordatorio'] = $UsuarioRecordatorioGrupo;
        return $datos;
    }

    public function store(Request $request)
    {
        $existeRegistro = UsuarioRecordatorioGrupo::where('id_usuario_emisor',$request->id_usuario_emisor)
        ->where('id_grupoautorizacion',$request->id_grupoautorizacion)
        ->where('id_usuario_receptor',$request->id_usuario_receptor)
        ->where('activo',1)
        ->where('eliminado',0)->first();

        if($existeRegistro == null){
            $UsuarioRecordatorioGrupo = new UsuarioRecordatorioGrupo;
            $UsuarioRecordatorioGrupo->id_usuario_emisor = $request->id_usuario_emisor;
            $UsuarioRecordatorioGrupo->id_grupoautorizacion = $request->id_grupoautorizacion;
            $UsuarioRecordatorioGrupo->id_usuario_receptor = $request->id_usuario_receptor;
            $UsuarioRecordatorioGrupo->activo = 1;
            $UsuarioRecordatorioGrupo->eliminado = 0;
            $UsuarioRecordatorioGrupo->save();
    
            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'crear';
            $bitacora->objeto = 'UsuarioRecordatorioGrupo';
            $bitacora->parametros_nuevos = 'ID '.$UsuarioRecordatorioGrupo->id_usuariorecordatoriogrupo;
            $bitacora->save();
        }

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $UsuarioRecordatorioGrupo = UsuarioRecordatorioGrupo::find($id);
            $datosAnteriores = json_encode($UsuarioRecordatorioGrupo,true);
            $UsuarioRecordatorioGrupo->activo = $request->activo;
            $UsuarioRecordatorioGrupo->eliminado = 0;
            $UsuarioRecordatorioGrupo->save();
            

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'UsuarioRecordatorioGrupo';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $UsuarioRecordatorioGrupo = UsuarioRecordatorioGrupo::find($id);
            $UsuarioRecordatorioGrupo->eliminado = 1;
            $UsuarioRecordatorioGrupo->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario_s;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'UsuarioRecordatorioGrupo';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $UsuarioRecordatorioGrupo->delete();

        return response()->json(null, 204);
    }
}

