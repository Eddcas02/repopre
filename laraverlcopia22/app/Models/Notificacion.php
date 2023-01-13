<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'IdNotificacion';
    protected $table = 'NotificacionUsuario';  
    protected $fillable = [
        'IdNotificacion',
        'IdFlujo',
        'IdUsuario',
        'Mensaje',
        'Leido',
    ];
}
