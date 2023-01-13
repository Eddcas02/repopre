<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlujoGrupo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujogrupo';
    protected $table = 'FlujoGrupoAutorizacion';  
    protected $fillable = [
        'id_flujogrupo',
        'id_flujo',
        'id_grupoautorizacion',
        'activo',
        'eliminado'
    ];
}
