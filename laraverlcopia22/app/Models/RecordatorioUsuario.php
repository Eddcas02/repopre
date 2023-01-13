<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class RecordatorioUsuario extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_recordatoriousuario';
    protected $table = 'RecordatorioUsuario'; 
	protected $guarded = [];

}