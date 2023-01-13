<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerfilRol extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_perfilrol';
    protected $table = 'PerfilRol';  
    protected $fillable = [
        'id_perfilrol',
        'id_perfil',
        'id_rol',
        'activo',
        'eliminado'
    ];
}
