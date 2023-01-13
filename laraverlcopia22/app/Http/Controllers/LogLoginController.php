<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LogLogin;
use App\Models\Usuarios;

class LogLoginController extends Controller
{
    public function index()
    {
    }

    public function show($id)
    {
    }

    public function store(Request $request)
    {
        $cantidad = 0;
        $incorrectos = LogLogin::selectRaw("count(*) as cantidad")
        ->where('id_usuario', $request->id_usuario)->where('activo', 1)->get();
        foreach($incorrectos->toArray() as $item){
            $cantidad = $item['cantidad'];
        }
        
        if($cantidad < ($request->maximo-1)){
            $logLogin = new LogLogin;
            $logLogin->id_usuario = $request->id_usuario;
            $logLogin->fecha_login = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
            $logLogin->activo = 1;
            $logLogin->save();
        }else if($cantidad == ($request->maximo-1)){
            $logLogin = new LogLogin;
            $logLogin->id_usuario = $request->id_usuario;
            $logLogin->fecha_login = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
            $logLogin->activo = 1;
            $logLogin->save();

            LogLogin::where('id_usuario', $request->id_usuario)
            ->where('activo', 1)->update(['activo' => 0]);

            Usuarios::where('id_usuario', $request->id_usuario)->update(['activo' => 0]);
        }
    }

    public function update(Request $request, $opcion)
    {
        if ($opcion == '1') {
            return response()->json("OK");
        }
    }

    public function delete(Request $request)
    {
        $mensajes->delete();

        return response()->json(null, 204);
    }
}

