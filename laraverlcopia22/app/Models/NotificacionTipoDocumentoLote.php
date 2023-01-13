<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificacionTipoDocumentoLote extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql';
    protected $primaryKey = 'id_notificaciontipodocumentolote';
    protected $table = 'NotificacionTipoDocumentoLote';
	protected $guarded = [];
}