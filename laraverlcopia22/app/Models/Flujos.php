<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flujos extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujo';
    protected $table = 'Flujo';  
    protected $fillable = [
        'id_flujo',
        'id_tipoflujo',
        'doc_num',
        'tipo',
        'tax_date',
        'doc_date',
        'card_code',
        'card_name',
        'comments',
        'doc_total',
        'doc_curr',
        'bank_code',
        'dfl_account',
        'tipo_cuenta_destino',
        'cuenta_orgien',
        'empresa_codigo',
        'empresa_nombre',
        'cheque',
        'en_favor_de',
        'email',
        'activo',
        'eliminado',
        'id_grupoautorizacion',
        'estado',
        'nivel',
        'dias_credito',
        'nombre_condicion_pago_dias',
        'cuenta_contable',
        'ArchivoSubido',
        'NombreXML',
        'origen_datos',
        'ConDuda'
    ];
}
