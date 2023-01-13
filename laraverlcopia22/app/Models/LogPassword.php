<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogPassword extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_logpassword';
    protected $table = 'LogPassword';  
    protected $fillable = [
        'id_logpassword',
        'id_usuario',
        'fecha_cambio'
    ];
}

