<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenciaGrupoAutorizacion extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_referenciagrupo';
    protected $table = 'ReferenciaGrupoAutorizacion'; 
	protected $guarded = [];
}
