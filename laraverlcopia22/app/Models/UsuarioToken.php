<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UsuarioToken extends Authenticatable
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuario_token';
    protected $table = 'UsuarioToken';
    protected $fillable = [
        'id_usuario',
        'fecha_creacion',
        'api_token',
    ];
} 