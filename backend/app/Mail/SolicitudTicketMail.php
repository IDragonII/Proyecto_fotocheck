<?php

namespace App\Mail;

use App\Models\Solicitud;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Solicitud $solicitud,
    ) {}

    public function envelope(): Envelope
    {
        $tipo = $this->solicitud->tipoSolicitud->nombre ?? 'Solicitud';

        return new Envelope(
            subject: "FUT - {$tipo} [{$this->solicitud->codigo}]",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->generatePdf(),
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('emails.fut-solicitud', ['solicitud' => $this->solicitud]);

        $codigo = $this->solicitud->codigo;

        return [
            Attachment::fromData(fn() => $pdf->output(), "FUT-{$codigo}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    private function generatePdf(): string
    {
        return "<p>Adjunto el Formulario Unico de Tramite (FUT) correspondiente a la solicitud <strong>{$this->solicitud->codigo}</strong>.</p>";
    }
}
