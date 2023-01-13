<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CuentaGrupoAutorizacion;
use App\Models\SugerenciaAsignacionGrupo;
use App\Models\ZBancoMaestro;
use App\Models\ZEmpresa;
use App\Models\Flujos;
use App\Models\FlujoDetalle;
use Illuminate\Support\Str;
use App\Models\Usuarios;
use App\Models\FlujoGrupo;
use App\Models\FlujoCambioDias;


class TestFuncionalidadController extends Controller
{
    public function index(Request $request)
    {
        $datos = Flujos::where('dias_credito','=',0)->where('activo',1)->where('eliminado',0)->get();
        foreach($datos as $item){
            $flujoCambioDias = new FlujoCambioDias;
            $flujoCambioDias->id_flujo = $item->id_flujo;
            $flujoCambioDias->activo = 1;
            $flujoCambioDias->eliminado = 0;
            $flujoCambioDias->save();
        }
    }
}