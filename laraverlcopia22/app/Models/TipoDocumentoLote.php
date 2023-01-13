<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumentoLote extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_tipodocumentolote';
    protected $table = 'TipoDocumentoLote';
	protected $guarded = [];
}