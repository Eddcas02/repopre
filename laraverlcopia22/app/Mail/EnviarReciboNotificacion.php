<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnviarReciboNotificacion extends Mailable
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
                ->subject('Notificación recibo de transferencia')
                ->view('email-recibo-not', compact('details'));

        if(array_key_exists('archivoPDF',$this->details)){
            $this->attach($this->details['archivoPDF']);
        }
        return $this;
    }
}
