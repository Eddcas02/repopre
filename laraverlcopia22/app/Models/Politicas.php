<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Politicas extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_politica';
    protected $fillable = ['id_politica', 'descripcion', 'identificador', 'valor', 'activo', 'eliminado'];
}