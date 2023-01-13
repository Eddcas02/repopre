<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlujoDetalle extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'IdFlujoDetalle';
    protected $table = 'FlujoDetalle';
    protected $fillable = [
        'IdFlujoDetalle', 
        'IdFlujo', 
        'IdEstadoFlujo', 
        'IdUsuario', 
        'Fecha',
        'Comentario',
        'Parametros',
        'NivelAutorizo',
        'FlujoActivo'
    ];
}
