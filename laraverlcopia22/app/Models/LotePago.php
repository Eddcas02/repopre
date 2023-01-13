<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotePago extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_lotepago';
    protected $table = 'LotePago';  
    protected $fillable = [
        'id_lotepago',
        'tipo',
        'fecha_hora',
        'id_usuario',
        'Activo',
        'Eliminado',
        'PathDocumentoPDF',
        'PathDocumentoExcel'
    ];
}

