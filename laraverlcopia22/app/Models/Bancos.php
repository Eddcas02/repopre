<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bancos extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_banco';
    protected $table = 'Banco';  
    protected $fillable = [
        'id_banco',
        'nombre',
        'direccion',
        'codigo_transferencia',
        'codigo_SAP',
        'id_pais',
        'activo',
        'eliminado'
    ];
}
