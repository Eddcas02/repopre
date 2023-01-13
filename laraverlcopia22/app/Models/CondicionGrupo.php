<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CondicionGrupo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_condiciongrupoautorizacion';
    protected $table = 'CondicionGrupoAutorizacion';  
    protected $fillable = [
        'id_condiciongrupoautorizacion',
        'id_condicionautorizacion',
        'id_grupoautorizacion',
        'activo',
        'eliminado'
    ];
}
