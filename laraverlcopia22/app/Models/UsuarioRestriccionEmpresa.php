<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class UsuarioRestriccionEmpresa extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuariorestriccionempresa';
    protected $table = 'UsuarioRestriccionEmpresa'; 
	protected $guarded = [];

}