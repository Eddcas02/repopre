<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlujoOferta extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujooferta';
    protected $table = 'FlujoOferta';
    protected $fillable = [
        'id_flujooferta',
        'id_flujo',
        'doc_num',
        'doc_date',
        'card_code',
        'card_name',
        'item_code',
        'dscription',
        'uom_code',
        'price',
        'quantity'
    ];
}
