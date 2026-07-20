<?php

namespace App\Mail;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudRespuestaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Solicitud $solicitud,
    ) {}

    public function envelope(): Envelope
    {
        $tipo = $this->solicitud->tipoSolicitud->nombre ?? 'Solicitud';

        return new Envelope(
            subject: "Respuesta a tu {$tipo} [{$this->solicitud->codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->generateHtml(),
        );
    }

    private function generateHtml(): string
    {
        $solicitud = $this->solicitud;
        $persona = $solicitud->persona;
        $tipo = $solicitud->tipoSolicitud->nombre ?? 'Solicitud';
        $respuesta = $solicitud->respuesta ?? 'Sin respuesta detallada.';
        $nombreCompleto = ($persona->nombres ?? '').' '.($persona->apellidos ?? '');
        $estadoTexto = $solicitud->estado === 'RESUELTO' ? 'RESUELTA' : 'RECHAZADA';

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #00467F;'>Respuesta a tu Solicitud</h2>
            
            <p>Estimado(a) <strong>{$nombreCompleto}</strong>,</p>
            
            <p>Su solicitud <strong>{$tipo}</strong> con c&oacute;digo <strong>{$solicitud->codigo}</strong> ha sido <strong>{$estadoTexto}</strong>.</p>
            
            <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #00467F;'>
                <h3 style='margin-top: 0; color: #00467F;'>Respuesta:</h3>
                <p style='white-space: pre-wrap; margin: 0;'>{$respuesta}</p>
            </div>
            
            <p>Si tiene alguna consulta adicional, puede responder a este correo.</p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
            <p style='font-size: 0.85em; color: #666;'>
                Este es un mensaje autom&aacute;tico del Sistema de Tickets FUT - OTI UNA Puno.
            </p>
        </div>
        ";
    }
}
