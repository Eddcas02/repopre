<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class SeccionAplicacion extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_seccionaplicacion';
    protected $table = 'SeccionAplicacion'; 
	protected $guarded = [];

}