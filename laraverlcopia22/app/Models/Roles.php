<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;



class Roles extends Model

{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_rol';
    protected $fillable = ['id_rol', 'descripcion','objeto','activo','eliminado'];

}