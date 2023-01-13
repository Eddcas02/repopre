<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlujoSolicitud extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujosolicitud';
    protected $table = 'FlujoSolicitud';  
    protected $fillable = [
        'id_flujosolicitud',
        'id_flujo',
        'doc_date',
        'req_name',
        'doc_num',
        'item_code',
        'dscription',
        'uom_code',
        'price',
        'quantity',
        'unidades_totales',
        'unidades_por_caja',
        'comments',
        'autorizador1',
        'autorizador2',
        'autorizador3',
        'fecha_aut1',
        'fecha_aut2',
        'fecha_aut3'
    ];
}
