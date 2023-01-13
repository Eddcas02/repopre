<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monedas extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_moneda';
    protected $table = 'Moneda'; 
    protected $fillable = [
        'id_moneda',
        'nombre',
        'simbolo',
        'activo',
        'eliminado'
    ];
}
