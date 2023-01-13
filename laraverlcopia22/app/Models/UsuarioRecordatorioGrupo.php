<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioRecordatorioGrupo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_usuariorecordatoriogrupo';
    protected $table = 'UsuarioRecordatorioGrupo';
	protected $guarded = [];
}