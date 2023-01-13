<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SeccionAplicacion;
use App\Models\Bitacora;
use Carbon\Carbon;

class SeccionAplicacionController extends Controller
{
    public function index()
    {
        $SeccionAplicacion = SeccionAplicacion::where('activo',1)
        ->where('eliminado', 0)->get();
        $datos = array();
        $datos['seccion'] = $SeccionAplicacion;
        return $datos;
    }

    public function store(Request $request)
    {
        $SeccionAplicacion = new SeccionAplicacion;
        $SeccionAplicacion->nombre = $request->nombre;
        $SeccionAplicacion->direccion = $request->direccion;
        $SeccionAplicacion->direccion_movil = $request->direccion_movil;
        $SeccionAplicacion->activo = 1;
        $SeccionAplicacion->eliminado = 0;
        $SeccionAplicacion->save();

        $fechaActual = Carbon::now('America/Guatemala');
        $bitacora = new Bitacora;
        $bitacora->id_usuario = $request->id_usuario;
        $bitacora->fecha_hora = $fechaActual;
        $bitacora->accion = 'crear';
        $bitacora->objeto = 'SeccionAplicacion';
        $bitacora->parametros_nuevos = 'ID '.$SeccionAplicacion->id_seccionaplicacion;
        $bitacora->save();
                
        return response()->json("OK");
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $SeccionAplicacion = SeccionAplicacion::find($id);
            $datosAnteriores = json_encode($SeccionAplicacion,true);
            $SeccionAplicacion->nombre = $request->nombre;
            $SeccionAplicacion->direccion = $request->direccion;
            $SeccionAplicacion->direccion_movil = $request->direccion_movil;
            $SeccionAplicacion->activo = $request->activo;
            $SeccionAplicacion->eliminado = 0;
            $SeccionAplicacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'editar';
            $bitacora->objeto = 'SeccionAplicacion';
            $bitacora->parametros_anteriores = $datosAnteriores;
            $bitacora->parametros_nuevos = json_encode($request->getContent(),true);
            $bitacora->save();

            return response()->json("OK"); 
        } else if ($opcion == '2') {
            $SeccionAplicacion = SeccionAplicacion::find($id);
            $SeccionAplicacion->eliminado = 1;
            $SeccionAplicacion->save();

            $fechaActual = Carbon::now('America/Guatemala');
            $bitacora = new Bitacora;
            $bitacora->id_usuario = $request->id_usuario;
            $bitacora->fecha_hora = $fechaActual;
            $bitacora->accion = 'eliminar';
            $bitacora->objeto = 'SeccionAplicacion';
            $bitacora->parametros_nuevos = 'ID '.$id;
            $bitacora->save();

            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $SeccionAplicacion->delete();

        return response()->json(null, 204);
    }
}

