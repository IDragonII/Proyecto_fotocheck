<?php

namespace App\Jobs;

use App\Mail\SolicitudTicketMail;
use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSolicitudEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $solicitudId,
    ) {}

    public function handle(): void
    {
        $solicitud = Solicitud::with(['persona', 'tipoSolicitud'])->find($this->solicitudId);

        if (! $solicitud) {
            Log::warning("SendSolicitudEmailJob: Solicitud #{$this->solicitudId} no encontrada.");

            return;
        }

        $tipoNombre = strtoupper(trim($solicitud->tipoSolicitud->nombre ?? ''));

        if (str_contains($tipoNombre, 'SOPORTE')) {
            Log::info("SendSolicitudEmailJob: Solicitud {$solicitud->codigo} es SOPORTE TECNICO. Se omite envio.");

            return;
        }

        $correo = null;

        if ($solicitud->persona) {
            $correoRegistrado = $solicitud->persona->correos()
                ->where('principal', true)
                ->first()?->correo
                ?? $solicitud->persona->correos()->first()?->correo;

            if ($correoRegistrado) {
                $correo = $correoRegistrado;
            }
        }

        if (! $correo) {
            $correo = $solicitud->correo_personal;
        }

        if (! $correo) {
            Log::info("SendSolicitudEmailJob: Solicitud {$solicitud->codigo} no tiene correo registrado ni correo_personal. Se omite envio.");

            return;
        }

        try {
            Mail::to($correo)->send(new SolicitudTicketMail($solicitud));
            Log::info("SendSolicitudEmailJob: Email enviado a {$correo} para solicitud {$solicitud->codigo}");
        } catch (\Exception $e) {
            Log::error("SendSolicitudEmailJob: Error enviando email para {$solicitud->codigo}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendSolicitudEmailJob: Job fallido definitivamente para solicitud #{$this->solicitudId}: {$exception->getMessage()}");
    }
}
