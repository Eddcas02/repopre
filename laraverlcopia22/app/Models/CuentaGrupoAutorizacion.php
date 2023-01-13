<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaGrupoAutorizacion extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_cuentagrupo';
    protected $table = 'CuentaGrupoAutorizacion'; 
	protected $guarded = [];
}
