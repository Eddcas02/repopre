<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoFlujo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_estadoflujo';
    protected $table = 'EstadoFlujo';
    protected $fillable = ['id_estadoflujo', 'id_estadoflujopadre', 'descripcion', 'activo', 'eliminado','accion'];
}