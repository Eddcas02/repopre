<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesionUsuario extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'IdSesion';
    protected $table = 'SesionUsuario';  
    protected $fillable = [
        'IdSesion',
        'IdUsuario',
        'Navegador',
        'IP',
        'FechaHoraInicio',
        'FechaHoraFinal',
        'Activo'
    ];
}
