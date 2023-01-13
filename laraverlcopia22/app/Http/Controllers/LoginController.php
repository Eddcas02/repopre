<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuarios;
use App\Models\UsuarioToken;
use Carbon\Carbon;

class LoginController extends Controller
{
    public function autenticar(Request $request)
    {
        $usuarios = Usuarios::where('password','=',$request->password)
                            ->where(function($q) use($request){
                                $q->where('correo','=',$request->user)
                                ->orWhere('nombre_usuario','=',$request->user);
                            })->first();
        if($usuarios){
            $CodigoInicial = self::generarCodigo(25);
            $fechaActual = Carbon::now('America/Guatemala');
            $Codigo = md5($fechaActual->toDateString().$CodigoInicial);
            /* Usuarios::where('id_usuario', $usuarios->id_usuario)
                ->update(['api_token' => $Codigo]); */
                $fechaActual = Carbon::now('America/Guatemala');
                $UsuarioToken = new UsuarioToken;
                $UsuarioToken->id_usuario = $usuarios->id_usuario;
                $UsuarioToken->fecha_creacion = $fechaActual;
                $UsuarioToken->api_token = $Codigo;
                $UsuarioToken->save();
            return response()->json($CodigoInicial, 200);
        }else{
            return response()->json(null, 404);
        }
    }

    function generarCodigo($length) { 
        $codigo = "";
        $codigo.=substr(str_shuffle("123456789"), 0, 1);
        $codigo.=substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 1, $length);
        return $codigo;
    }
}
