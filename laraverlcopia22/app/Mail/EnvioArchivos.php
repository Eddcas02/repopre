<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnvioArchivos extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $details = $this->details;
        $this->from('soporte@sion.com.gt')
                ->subject('Archivos de lote')
                ->view('email-archivos', compact('details'));

        if(array_key_exists('archivoPDF',$this->details)){
            $this->attach($this->details['archivoPDF']);
        }

        if(array_key_exists('archivoExcel',$this->details)){
            $this->attach($this->details['archivoExcel']);
        }
        
        return $this;
    }
}
