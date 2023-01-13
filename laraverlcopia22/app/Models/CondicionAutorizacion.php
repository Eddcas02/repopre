<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CondicionAutorizacion extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_condicionautorizacion';
    protected $table = 'CondicionAutorizacion';
    protected $fillable = ['id_condicionautorizacion', 'descripcion', 'parametro', 'activo', 'eliminado'];
}