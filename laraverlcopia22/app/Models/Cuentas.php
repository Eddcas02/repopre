<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuentas extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_cuenta';
    protected $table = 'Cuenta';  
    protected $fillable = [
        'id_cuenta',
        'numero_cuenta',
        'nombre',
        'id_empresa',
        'id_banco',
        'id_moneda',
        'empresa',
        'banco',
        'moneda',
        'codigo_ach',
        'eliminado'
    ];
}