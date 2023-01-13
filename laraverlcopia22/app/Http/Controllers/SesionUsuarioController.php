<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SesionUsuario;

class SesionUsuarioController extends Controller
{
    public function index()
    {
        $sesiones = SesionUsuario::join('usuarios', function($join){
            $join->on('usuarios.id_usuario', 'SesionUsuario.IdUsuario');
        })->selectRaw(
            "SesionUsuario.IdSesion,
             SesionUsuario.IdUsuario,
             SesionUsuario.Navegador,
             SesionUsuario.IP as IPAddress,
             DATE_FORMAT(SesionUsuario.FechaHoraInicio,'%H:%i:%s')as FechaHoraInicio,
             usuarios.nombre_usuario  as NombreUsuario"
        )->where('SesionUsuario.Activo', 1)->get();
        $datos = array();
        $datos['sesiones'] = $sesiones;
        return $datos;
    }

    public function general()
    {
        $sesiones = SesionUsuario::join('usuarios', function($join){
            $join->on('usuarios.id_usuario', 'SesionUsuario.IdUsuario');
        })->selectRaw(
            "SesionUsuario.IdUsuario,
             DATE_FORMAT(MAX(SesionUsuario.FechaHoraInicio),'%Y-%m-%d %H:%i:%s')as FechaHoraInicio,
             usuarios.nombre_usuario  as NombreUsuario"
        )->groupBy('SesionUsuario.IdUsuario')
        ->groupBy('usuarios.nombre_usuario')->get();
        $datos = array();
        $datos['sesiones'] = $sesiones;
        return $datos;
    }

    public function isconnected($id)
    {
        $sesiones = SesionUsuario::all()
        ->where('IdUsuario', $id)->where('Activo', 1)->first();
        if(!is_null($sesiones)){
            return response()->json("Conectado");
        }else{
            return response()->json("Desconectado");
        }
    }

    public function show($id)
    {         
        $sesiones = SesionUsuario::join('usuarios', function($join){
            $join->on('usuarios.id_usuario', 'SesionUsuario.IdUsuario');
        })->selectRaw(
            "SesionUsuario.IdSesion,
             SesionUsuario.IdUsuario,
             SesionUsuario.Navegador,
             SesionUsuario.IP as IPAddress,
             DATE_FORMAT(SesionUsuario.FechaHoraInicio,'%d-%m-%Y %H:%i:%s')as FechaHoraInicio,
             DATE_FORMAT(SesionUsuario.FechaHoraFinal,'%d-%m-%Y %H:%i:%s') as FechaHoraFinal,
             SesionUsuario.Activo,
             usuarios.nombre_usuario as NombreUsuario"
        )->where('SesionUsuario.IdUsuario', $id)->get();
        $datos = array();
        $datos['sesiones'] = $sesiones;
        return $datos;
    }

    public function store(Request $request)
    {
        if($request->Opcion == 1){     
            $sesiones = new SesionUsuario;
            $sesiones->IdUsuario = $request->IdUsuario;
            $sesiones->Navegador = $request->Navegador;
            $sesiones->IP = $request->IP;
            $sesiones->FechaHoraInicio = date("Y-m-d H:i:s", strtotime('-6 hour',strtotime(now())));
            $sesiones->Activo = 1;
            $sesiones->save();
            return response()->json("OK");
        }else if($request->Opcion == 2){     
            SesionUsuario::where('IdUsuario', $request->IdUsuario)->where('Activo', 1)
            ->update([
                'FechaHoraFinal' => date("Y-m-d H:i:s", strtotime('-6 hour',strtotime(now()))), 
                'Activo' => 0
            ]);
            return response()->json("OK");
        }        
    }

}
