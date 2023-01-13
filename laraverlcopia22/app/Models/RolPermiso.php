<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolPermiso extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_rolpermiso';
    protected $table = 'RolPermiso';  
    protected $fillable = [
        'id_rolpermiso',
        'id_rol',
        'id_permiso',
        'activo',
        'eliminado'
    ];
}
