<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoAutorizacion extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_grupoautorizacion';
    protected $table = 'GrupoAutorizacion';
    protected $fillable = [
        'id_grupoautorizacion',
        'identificador',
        'descripcion',
        'numero_niveles',
        'activo',
        'eliminado'
    ];
}