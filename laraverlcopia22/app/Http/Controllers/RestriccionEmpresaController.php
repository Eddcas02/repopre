<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RestriccionEmpresa;
use App\Models\Flujos;
use App\Models\Bitacora;
use Carbon\Carbon;

class RestriccionEmpresaController extends Controller
{
    public function index()
    {
        $RestriccionEmpresa = RestriccionEmpresa::where('eliminado',0)->get();
        $datos = array();
        $datos['restriccion_empresa'] = $RestriccionEmpresa;
        return $datos;
    }

    public function EmpresasDisponibles(){
        $EmpresasRestringidas = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDisponibles = Flujos::select(['empresa_codigo', 'empresa_nombre'])
        ->whereNotIn('empresa_codigo', $EmpresasRestringidas)
        ->groupBy('empresa_codigo')
        ->groupBy('empresa_nombre')
        ->get();
        $datos = array();
        $datos['restriccion_empresa'] = $EmpresasDisponibles;
        
        return $datos;
    }

    public function show(RestriccionEmpresa $RestriccionEmpresa)
    {
        return $RestriccionEmpresa;
    }

    public function store(Request $request)
    {
        $RestriccionEmpresa = new RestriccionEmpresa;
        $RestriccionEmpresa->empresa_codigo = $request->empresa_codigo;
        $RestriccionEmpresa->empresa_nombre = $request->empresa_nombre;
        $RestriccionEmpresa->activo = 1;
        $RestriccionEmpresa->eliminado = 0;
        $RestriccionEmpresa->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'RestriccionEmpresa';
        $bitacora->parametros_nuevos = 'ID '.$RestriccionEmpresa->id_restriccionempresa;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $RestriccionEmpresa = RestriccionEmpresa::find($id);
            $datosAnteriores = json_encode($RestriccionEmpresa,true);
            $RestriccionEmpresa->empresa_codigo = $request->empresa_codigo;
            $RestriccionEmpresa->empresa_nombre = $request->empresa_nombre;
            $RestriccionEmpresa->activo = $request->activo;
            $RestriccionEmpresa->eliminado = 0;
            $RestriccionEmpresa->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'RestriccionEmpresa';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $RestriccionEmpresa = RestriccionEmpresa::find($id);
            $RestriccionEmpresa->eliminado = 1;
            $RestriccionEmpresa->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'RestriccionEmpresa';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(RestriccionEmpresa $RestriccionEmpresa)
    {
        $RestriccionEmpresa->delete();

        return response()->json(null, 204);
    }
}
