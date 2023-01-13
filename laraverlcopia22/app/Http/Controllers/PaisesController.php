<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paises;

class PaisesController extends Controller
{
    public function index()
    {
        $paises = Paises::all();
        $datos = array();
        $datos['paises'] = $paises;
        return $datos;
    }

    public function show($id)
    {
        $paises = Paises::where('IdPais', $id)->get();
        $datos = array();
        $datos['paises'] = $paises;
        return $datos;
    }
}


