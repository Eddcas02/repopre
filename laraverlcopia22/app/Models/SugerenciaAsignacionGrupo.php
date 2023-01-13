<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class SugerenciaAsignacionGrupo extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_sugerenciagrupo';
    protected $table = 'SugerenciaAsignacionGrupo'; 
	protected $guarded = [];

}