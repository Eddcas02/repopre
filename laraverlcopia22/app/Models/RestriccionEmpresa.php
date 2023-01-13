<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class RestriccionEmpresa extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_restriccionempresa';
    protected $table = 'RestriccionEmpresa'; 
	protected $guarded = [];

}