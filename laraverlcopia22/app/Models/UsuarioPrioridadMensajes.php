<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class UsuarioPrioridadMensajes extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuarioprioridadmensajes';
    protected $table = 'UsuarioPrioridadMensajes'; 
	protected $guarded = [];

}