<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bancos;
use App\Models\Bitacora;
use Carbon\Carbon;

class BancosController extends Controller
{
    public function index()
    {
        $bancos = Bancos::join('Pais', function($join){
            $join->on('Banco.id_pais', '=', 'Pais.IdPais');
        })->where('Banco.eliminado', 0)->get();
        $datos = array();
        $datos['bancos'] = $bancos;
        return $datos;
    }

    public function show($id)
    {
        $bancos = Bancos::where('id_banco', $id)->get();
        $datos = array();
        $datos['bancos'] = $bancos;
        return $datos;
    }

    public function store(Request $request)
    {
        $consulta = Bancos::where('Banco.codigo_transferencia', $request->codigo_transferencia)
        ->where('Banco.nombre', $request->nombre)->get();
        if($consulta->count() > 0){
            return response()->json("Repetido");
        }else{
            $bancos = new Bancos;
            $bancos->nombre = $request->nombre;
            $bancos->direccion = $request->direccion;
            $bancos->codigo_transferencia = $request->codigo_transferencia;
            $bancos->codigo_SAP = $request->codigo_SAP;
            $bancos->id_pais = $request->id_pais;
            $bancos->activo = 1;
            $bancos->eliminado = 0;
            $bancos->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'crear';
            $bitacora->objeto = 'Bancos';
            $bitacora->parametros_nuevos = 'ID '.$bancos->id_banco;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $bancos = Bancos::find($id);
            $datosAnteriores = json_encode($bancos,true);
            $bancos->nombre = $request->nombre;
            $bancos->direccion = $request->direccion;
            $bancos->codigo_transferencia = $request->codigo_transferencia;
            $bancos->codigo_SAP = $request->codigo_SAP;
            $bancos->id_pais = $request->id_pais;
            $bancos->activo = $request->activo;
            $bancos->eliminado = 0;
            $bancos->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'Bancos';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $bancos = Bancos::find($id);
            $bancos->eliminado = 1;
            $bancos->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'Bancos';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $bancos->delete();

        return response()->json(null, 204);
    }
}

