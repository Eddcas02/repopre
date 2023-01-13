<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioNotificacionTransaccion extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuarionotificaciontransaccion';
    protected $table = 'UsuarioNotificacionTransaccion';
	protected $guarded = [];
}