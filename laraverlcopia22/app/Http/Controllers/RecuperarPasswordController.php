<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecuperarPassword;
use App\Models\Usuarios;
use Illuminate\Support\Facades\Mail;
use App\Mail\RecuperaPassword;
use App\Models\LogPassword;

class RecuperarPasswordController extends Controller
{
    public function index()
    {
        return true;
    }

    public function show($id)
    {
        return true;        
    }

    public function store(Request $request)
    {
        $llaveAcceso = self::generateRandomString();
        $usuario = $request->usuario;
        $datosUsuario = Usuarios::where('correo','=',$usuario)
                        ->orWhere('nombre_usuario','=',$usuario)
                        ->where('eliminado','=','0')
                        ->first();
        if(is_null($datosUsuario)){
            return response()->json("Vacio");
        }else{
            $clavesAnteriores = RecuperarPassword::where('id_usuario','=',$datosUsuario->id_usuario)
                                ->where('usada','=','0')
                                ->where('activo','=','1')
                                ->where('eliminado','=','0')->update(['activo'=>false]);

            $recuperarPassword = new RecuperarPassword;
            $recuperarPassword->id_usuario = $datosUsuario->id_usuario;
            $recuperarPassword->llave_acceso = $llaveAcceso;
            $recuperarPassword->save();
            $link = 'https://pagos.sion.com.gt/pagos/#/cambiarpassword/'.$llaveAcceso;

            $to_email = $datosUsuario->correo;
            $details=[
                'link' => $link
            ];
            Mail::to($to_email)->send(new RecuperaPassword($details));

            return response()->json($link, 200);
        }
    }

    public function cambio(Request $request)
    {
        $password = $request->password;
        $llaveAcceso = $request->token;

        if (str_contains($llaveAcceso, 'CambioInterno')){
            $partes = explode("|",$llaveAcceso);
            $id_usuario = intval($partes[1]);
            Usuarios::where('id_usuario', '=', $id_usuario)
                ->update(['password' => $password]);

            $logpassword = new LogPassword;
            $logpassword->id_usuario = $id_usuario;
            $logpassword->fecha_cambio = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
            $logpassword->save();
            return response()->json("ok", 200);
        }else{
        
            $claveActiva = RecuperarPassword::where('llave_acceso','=',$llaveAcceso)
                                    ->where('usada','=','0')
                                    ->where('activo','=','1')
                                    ->where('eliminado','=','0')->first();

            if(is_null($claveActiva)){
                return response()->json("Vacio");
            }else{
                Usuarios::where('id_usuario', '=', $claveActiva->id_usuario)
                    ->update(['password' => $password]);
                
                RecuperarPassword::where('id_recuperacion','=',$claveActiva->id_recuperacion)
                    ->update(['usada' => '1']);

                $logpassword = new LogPassword;
                $logpassword->id_usuario = $claveActiva->id_usuario;
                $logpassword->fecha_cambio = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                $logpassword->save();
                return response()->json("ok", 200);
            }
        }
    }

    function generateRandomString($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function calcular($id)
    {        
        $ultimoCambio = LogPassword::selectRaw(
            "TIMESTAMPDIFF(DAY, MAX(fecha_cambio), DATE_ADD(NOW(), INTERVAL 1 HOUR)) as Dias"
        )
        ->where('id_usuario', $id)->groupBy('id_usuario')->first();
        if(!is_null($ultimoCambio)){
            return response()->json($ultimoCambio->Dias);
        }else{
            return response()->json("Vacio");
        }
    }

    public function crear(Request $request)
    {        
        $logpassword = new LogPassword;
        $logpassword->id_usuario = $request->id_usuario;
        $logpassword->fecha_cambio = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
        $logpassword->save();
        return response()->json("OK", 200);
    }
      
}

