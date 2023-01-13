<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlujoFacturaDocumento extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_flujofacturadocumento';
    protected $table = 'FlujoFacturaDocumento';  
    protected $fillable = [
        'id_flujofacturadocumento',
        'id_flujo',
        'src_path',
        'file_name',
        'file_ext'
    ];
}

