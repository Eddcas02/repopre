<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perfiles extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_perfil';
    protected $fillable = ['id_perfil', 'descripcion', 'activo', 'eliminado'];
}