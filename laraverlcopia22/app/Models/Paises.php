<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paises extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'IdPais';
    protected $table = 'Pais';  
    protected $fillable = [
        'IdPais',
        'CodigoPais',
        'Nombre'
    ];
}
