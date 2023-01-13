<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class UsuarioRestriccionTexto extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuariorestricciontexto';
    protected $table = 'UsuarioRestriccionTexto'; 
	protected $guarded = [];

}