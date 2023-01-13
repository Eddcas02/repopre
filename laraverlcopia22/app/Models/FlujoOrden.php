<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlujoOrden extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujoorden';
    protected $table = 'FlujoOrden';  
    protected $fillable = [
        'id_flujoorden',
        'id_flujo',
        'docu_num',
        'tax_date',
        'doc_date',
        'card_code',
        'card_name',
        'fac_nit',
        'phone1',
        'termino_pago',
        'address',
        'user',
        'item_code',
        'dscription',
        'uom_code',
        'price',
        'quantity',
        'line_total',
        'doc_total',
        'comment',
        'crea_usuario',
        'crea_fecha',
        'autoriza_usuario',
        'autoriza_fecha'
    ];
}
