<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensajes extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_mensaje';
    protected $table = 'Mensaje';  
    protected $fillable = [
        'id_mensaje',
        'id_flujo',
        'id_usuarioenvia',
        'id_usuariorecibe',
        'fecha_hora',
        'mensaje',
        'leido',
        'activo',
        'eliminado'
    ];
}
