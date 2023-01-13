<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class UsuarioRedireccion extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuarioredireccion';
    protected $table = 'UsuarioRedireccion'; 
	protected $guarded = [];

}