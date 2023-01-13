<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuarios extends Authenticatable
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuario';
    protected $fillable = [
        'id_usuario',
        'correo',
        'password',
        'nombre_usuario',
        'nombre',
        'apellido',
        'cambia_password',
        'activo',
        'eliminado',
        'api_token',
    ];
} 