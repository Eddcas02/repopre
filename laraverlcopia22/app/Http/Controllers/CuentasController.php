<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cuentas;
use App\Models\Bitacora;
use Carbon\Carbon;

class CuentasController extends Controller
{
    public function index()
    {
        $cuentas = Cuentas::join('Flujo', function($join){
            $join->on('Flujo.empresa_codigo', '=', 'Cuenta.id_empresa');
        })->join('Banco', function($join){
            $join->on('Banco.id_banco', '=', 'Cuenta.id_banco');
        })->join('Moneda', function($join){
            $join->on('Moneda.id_moneda', '=', 'Cuenta.id_moneda');
        })
        ->selectRaw(
            "Cuenta.id_cuenta,
             Cuenta.numero_cuenta,
             Cuenta.nombre,
             Cuenta.id_empresa,
             Cuenta.id_banco,
             Cuenta.id_moneda,
             Cuenta.codigo_ach,
             Flujo.empresa_nombre as empresa,
             Banco.nombre as banco,
             Moneda.nombre as moneda"
        )
        ->where('Cuenta.eliminado', 0)
        ->get();
        $datos = array();
        $datos['cuentas'] = $cuentas;
        return $datos;
    }

    public function show($id)
    {
        $cuentas = Cuentas::join('Flujo', function($join){
            $join->on('Flujo.empresa_codigo', '=', 'Cuenta.id_empresa');
        })->join('Banco', function($join){
            $join->on('Banco.id_banco', '=', 'Cuenta.id_banco');
        })->join('Moneda', function($join){
            $join->on('Moneda.id_moneda', '=', 'Cuenta.id_moneda');
        })
        ->selectRaw(
            "Cuenta.id_cuenta,
             Cuenta.numero_cuenta,
             Cuenta.nombre,
             Cuenta.id_empresa,
             Cuenta.id_banco,
             Cuenta.id_moneda,
             Cuenta.codigo_ach,
             Flujo.empresa_nombre as empresa,
             Banco.nombre as banco,
             Moneda.nombre as moneda"
        )
        ->where('Cuenta.id_cuenta', $id)->get();
        $datos = array();
        $datos['cuentas'] = $cuentas;
        return $datos;
    }

    public function store(Request $request)
    {
        $cuentas = new Cuentas;
        $cuentas->numero_cuenta = $request->numero_cuenta;
        $cuentas->nombre = $request->nombre;
        $cuentas->id_empresa = $request->id_empresa;
        $cuentas->id_banco = $request->id_banco;
        $cuentas->id_moneda = $request->id_moneda;
        $cuentas->codigo_ach = $request->codigo_ach;
        $cuentas->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'Cuentas';
        $bitacora->parametros_nuevos = 'ID '.$cuentas->id_cuenta;
        $bitacora->save();

        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $cuentas = Cuentas::find($id);
            $datosAnteriores = json_encode($cuentas,true);
            $cuentas->numero_cuenta = $request->numero_cuenta;
            $cuentas->nombre = $request->nombre;
            $cuentas->id_empresa = $request->id_empresa;
            $cuentas->id_banco = $request->id_banco;
            $cuentas->id_moneda = $request->id_moneda;
            $cuentas->codigo_ach = $request->codigo_ach;
            $cuentas->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'Cuentas';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $cuentas = Cuentas::find($id);
            $cuentas->eliminado = 1;

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'Cuentas';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            $cuentas->save();
            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $cuentas->delete();

        return response()->json(null, 204);
    }
}

