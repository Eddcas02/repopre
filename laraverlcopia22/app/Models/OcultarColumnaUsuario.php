<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OcultarColumnaUsuario extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_ocultarcolumnausuario';
    protected $table = 'OcultarColumnaUsuario';
	protected $guarded = [];
}