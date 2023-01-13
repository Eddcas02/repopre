<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class UsuarioSinNotificacionCorreo extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuariosinnotificacioncorreo';
    protected $table = 'UsuarioSinNotificacionCorreo'; 
	protected $guarded = [];

}