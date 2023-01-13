<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivosFlujo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_archivoflujo';
    protected $table = 'ArchivoFlujo';
    protected $fillable = [
        'id_archivoflujo',
        'id_flujo',
        'id_usuario',
        'descripcion',
        'archivo',
        'archivo_original',
        'activo',
        'eliminado'
    ];
}
