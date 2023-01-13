<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogLogin extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_loglogin';
    protected $table = 'LogLogin';  
    protected $fillable = [
        'id_logpassword',
        'id_usuario',
        'fecha_login',
        'activo'
    ];
}
