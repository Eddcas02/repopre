<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permisos extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_permiso';
    protected $fillable = ['id_permiso', 'descripcion', 'activo', 'eliminado'];
}