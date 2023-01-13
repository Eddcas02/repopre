<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlujoFacturaCantidad extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujofacturacantidad';
    protected $table = 'FlujoFacturaCantidad';  
    protected $fillable = [
        'id_flujofacturacantidad',
        'id_flujo',
        'doc_num',
        'cant_facturas'
    ];
}

