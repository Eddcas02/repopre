<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class FlujoCompensarSeleccionado extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujocompensarseleccionado';
    protected $table = 'FlujoCompensarSeleccionado'; 
	protected $guarded = [];

}