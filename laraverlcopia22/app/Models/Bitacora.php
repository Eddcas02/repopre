<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_bitacora';
    protected $table = 'Bitacora';  
    protected $fillable = [
        'id_usuario',
        'fecha_hora',
        'accion',
        'objeto',
        'parametros_anteriores',
        'parametros_nuevos'
    ];
}
