<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlujoLotePago extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujolotepago';
    protected $table = 'FlujoLotePago';  
    protected $fillable = [
        'id_flujolotepago',
        'id_lotepago',
        'id_flujo'
    ];
}

