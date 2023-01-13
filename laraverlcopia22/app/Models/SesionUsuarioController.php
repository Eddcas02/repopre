<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SesionUsuario;

class SesionUsuarioController extends Controller
{
    public function index()
    {
        $sesiones = SesionUsuario::all();
        $datos = array();
        $datos['sesiones'] = $sesiones;
        return $datos;
    }

    public function show($id)
    {
        $sesiones = SesionUsuario::where('IdSesion', $id)->get();
        $datos = array();
        $datos['sesiones'] = $sesiones;
        return $datos;
    }

    public function login(Request $request)
    {        
        $sesiones = new SesionUsuario;
        $sesiones->id_usuario = $request->id_usuario;
        $sesiones->fecha_login = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
        $logLogin->activo = 1;
        $logLogin->save();        
    }

    public function update(Request $request, $opcion)
    {
        if ($opcion == '1') {
            return response()->json("OK");
        }
    }
}
