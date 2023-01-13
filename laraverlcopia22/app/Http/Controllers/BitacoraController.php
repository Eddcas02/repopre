<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bitacora;
use Carbon\Carbon;

class BitacoraController extends Controller
{
    public function index($inicio, $fin)
    {
        $time_inicio = strtotime($inicio); 
        $date_inicio = date('Y-m-d', $time_inicio); 
        $time_fin = strtotime($fin); 
        $date_fin = date('Y-m-d', $time_fin); 
        $bitacora = Bitacora::join('usuarios', function($join){
            $join->on('Bitacora.id_usuario', '=', 'usuarios.id_usuario');
        })->selectRaw(
            "
            Bitacora.id_bitacora,
            Bitacora.id_usuario,
            usuarios.nombre_usuario,
            usuarios.nombre,
            usuarios.apellido,
            Bitacora.fecha_hora,
            Bitacora.accion,
            Bitacora.objeto,
            Bitacora.parametros_anteriores,
            Bitacora.parametros_nuevos
            "
        )
        ->where('Bitacora.fecha_hora','>=',$date_inicio)
        ->where('Bitacora.fecha_hora','<=',$date_fin)->get();
        $datos = array();
        $datos['bitacora'] = $bitacora;
        return $datos;
    }

    public function show($id)
    {
        $bitacora = Bitacora::join('usuarios', function($join){
            $join->on('Bitacora.id_usuario', '=', 'usuarios.id_usuario');
        })->selectRaw(
            "
            Bitacora.id_bitacora,
            Bitacora.id_usuario,
            usuarios.nombre_usuario,
            usuarios.nombre,
            usuarios.apellido,
            Bitacora.fecha_hora,
            Bitacora.accion,
            Bitacora.objeto,
            Bitacora.parametros_anteriores,
            Bitacora.parametros_nuevos
            "
        )
        ->where('Bitacora.id_bitacora', $id)->get();
        $datos = array();
        $datos['bitacora'] = $bitacora;
        return $datos;
    }
}

