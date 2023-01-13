<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlujoIngreso extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujoingreso';
    protected $table = 'FlujoIngreso';  
    protected $fillable = [
        'id_flujoingreso',
        'id_flujo',
        'doc_num',
        'tax_date',
        'doc_date',
        'whs_name',
        'user',
        'item_code',
        'dscription',
        'uom_code',
        'quantity',
        'comments'
    ];
}

