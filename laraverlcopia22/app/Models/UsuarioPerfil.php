<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioPerfil extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuarioperfil';
    protected $table = 'UsuarioPerfil';  
    protected $fillable = [
        'id_usuarioperfil',
        'id_usuario',
        'id_perfil',
        'activo',
        'eliminado'
    ];
}
