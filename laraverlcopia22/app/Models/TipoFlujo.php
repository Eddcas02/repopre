<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoFlujo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_tipoflujo';
    protected $table = 'TipoFlujo';
    protected $fillable = ['id_tipoflujo', 'descripcion', 'id_estadoinicial', 'activo', 'eliminado'];
}