<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioAutorizacion extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuarioautorizacion';
    protected $table = 'UsuarioAutorizacion';  
    protected $fillable = [
        'id_usuarioautorizacion',
        'id_usuarioaprobador',
        'id_usuariotemporal',
        'fecha_inicio',
        'fecha_final',
        'activo',
        'eliminado'
    ];
}
