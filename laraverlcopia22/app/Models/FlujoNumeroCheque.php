<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlujoNumeroCheque extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujonumerocheque';
    protected $table = 'FlujoNumeroCheque';  
    protected $fillable = [
        'id_flujonumerocheque',
        'id_flujo',
        'Numero_Cheque'
    ];
}

