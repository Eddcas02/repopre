<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Politicas;
use App\Models\Bitacora;
use Carbon\Carbon;
use App\Models\FlujoCambioDias;
use App\Models\Flujos;

class PoliticasController extends Controller
{
    public function index()
    {
        $politicas = Politicas::where('eliminado',0)->get();
        $datos = array();
        $datos['politicas'] = $politicas;
        return $datos;
    }

    public function show($id)
    {
        return $politicas;
    }

    public function store(Request $request)
    {
        $politicas = new Politicas;
        $politicas->descripcion = $request->descripcion;
        $politicas->identificador = $request->identificador;
        $politicas->valor = $request->valor;
        $politicas->activo = 1;
        $politicas->eliminado = 0;
        $politicas->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'Politicas';
        $bitacora->parametros_nuevos = 'ID '.$politicas->id_politica;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $politicas = Politicas::find($id);
            $datosAnteriores = json_encode($politicas,true);
            $politicas->descripcion = $request->descripcion;
            $politicas->identificador = $request->identificador;
            $politicas->valor = $request->valor;
            $politicas->activo = $request->activo;
            $politicas->eliminado = 0;
            $politicas->save();

            if($politicas->identificador == '_DIAS_BASE_CREDITO_'){
                $valorDiasCreditoBase = intval($politicas->valor);
                $datosCambio = FlujoCambioDias::selectRaw(
                    "FlujoCambioDias.id_flujo"
                )
                ->where('activo',1)
                ->where('eliminado',0)->get();

                Flujos::whereIn('id_flujo', $datosCambio)
                ->update([
                    'dias_credito' => $valorDiasCreditoBase
                ]);
            }

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'Politicas';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $politicas = Politicas::find($id);
            $politicas->eliminado = 1;
            $politicas->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'Politicas';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $politicas->delete();

        return response()->json(null, 204);
    }
}
