<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlujoGrupo;
use App\Models\FlujoDetalle;
use App\Models\Flujos;
use App\Models\UsuarioGrupo;

class FlujoGrupoController extends Controller
{
    public function index()
    {
        $flujogrupo = FlujoGrupo::join('GrupoAutorizacion', function($join){
            $join->on('FlujoGrupoAutorizacion.id_grupoautorizacion',
                '=',
                'GrupoAutorizacion.id_grupoautorizacion'
            );
        })
        ->selectRaw(
            "FlujoGrupoAutorizacion.id_flujogrupo,
             FlujoGrupoAutorizacion.id_flujo,
             FlujoGrupoAutorizacion.id_grupoautorizacion,
             FlujoGrupoAutorizacion.activo,
             FlujoGrupoAutorizacion.eliminado"
        )
        ->orderBy('FlujoGrupoAutorizacion.id_flujogrupo')
        ->get();
        $datos = array();
        $datos['detalle'] = $flujogrupo;
        return $datos;
    }

    public function show($id)
    {
        $flujogrupo = FlujoGrupo::join('GrupoAutorizacion', function($join){
            $join->on('FlujoGrupoAutorizacion.id_grupoautorizacion',
                '=',
                'GrupoAutorizacion.id_grupoautorizacion'
            );
        })
        ->selectRaw(
            "FlujoGrupoAutorizacion.id_flujogrupo,
             FlujoGrupoAutorizacion.id_flujo,
             FlujoGrupoAutorizacion.id_grupoautorizacion,
             FlujoGrupoAutorizacion.activo,
             FlujoGrupoAutorizacion.eliminado"
        )
        ->where('FlujoGrupoAutorizacion.id_usuario', $id)
        ->orderBy('FlujoGrupoAutorizacion.id_flujogrupo')
        ->get();
        $datos = array();
        $datos['detalle'] = $flujogrupo;
        return $datos;
    }

    public function store(Request $request)
    {
        $usuarios = UsuarioGrupo::select('UsuarioGrupoAutorizacion.nivel')
        ->where('UsuarioGrupoAutorizacion.id_usuario', $request->id_usuario)
        ->get()->toArray();

        $nivel = 0;

        foreach($usuarios as $item){
            $nivel = $item['nivel'];
        }

        $flujos = FlujoGrupo::join('Flujo', function($join){
            $join->on('FlujoGrupoAutorizacion.id_flujo', '=', 'Flujo.id_flujo');
        })
        ->select('FlujoGrupoAutorizacion.id_flujogrupo')
        ->where('FlujoGrupoAutorizacion.id_flujo', $request->id_flujo)
        ->where('Flujo.eliminado', 0)
        ->where('Flujo.activo', 1)
        ->get();
        if ($flujos->count() > 0) {
            return response()->json("Repetido");        
        }else{
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'id_grupoautorizacion' => $request->id_grupoautorizacion,
                'estado' => 3,
                'nivel' => 0
            ]);
            $flujogrupo = new FlujoGrupo;
            $flujogrupo->id_flujo = $request->id_flujo;
            $flujogrupo->id_grupoautorizacion = $request->id_grupoautorizacion;
            $flujogrupo->activo = 1;
            $flujogrupo->eliminado = 0;
            $flujogrupo->save();
            return response()->json("OK");
        }
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $flujogrupo = FlujoGrupo::find($id);
            $flujogrupo->eliminado = 1;
            $flujogrupo->save();
            return response()->json("OK"); 
        } else if ($opcion == '2') {
            FlujoGrupo::where('id_flujo', $request->id_flujo)
            ->update(['id_grupoautorizacion' => $request->id_grupoautorizacion]);
            return response()->json("OK");
        } else if ($opcion == '3') {
            $flujogrupo = FlujoGrupo::find($id);
            $flujogrupo->activo = $request->activo;
            $flujogrupo->save();
            return response()->json("OK");
        }
    }

    public function delete($id)
    {
        $id->delete();

        return response()->json(null, 204);
    }
}
