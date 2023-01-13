<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioGrupo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuariogrupo';
    protected $table = 'UsuarioGrupoAutorizacion';  
    protected $fillable = [
        'id_usuariogrupo',
        'id_usuario',
        'id_grupoautorizacion',
        'nivel',
        'activo',
        'eliminado'
    ];
}
