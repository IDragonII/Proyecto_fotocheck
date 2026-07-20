<?php

namespace App\Jobs;

use App\Mail\SolicitudRespuestaMail;
use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSolicitudRespuestaJob implements ShouldQueue
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
            Log::warning("SendSolicitudRespuestaJob: Solicitud #{$this->solicitudId} no encontrada.");

            return;
        }

        $tipoNombre = strtoupper(trim($solicitud->tipoSolicitud->nombre ?? ''));

        if (str_contains($tipoNombre, 'SOPORTE')) {
            Log::info("SendSolicitudRespuestaJob: Solicitud {$solicitud->codigo} es SOPORTE TECNICO. Se omite envio.");

            return;
        }

        // Prioridad 1: correo_personal del formulario
        $correo = $solicitud->correo_personal;

        // Prioridad 2: correo registrado en BD
        if (! $correo && $solicitud->persona) {
            $correoRegistrado = $solicitud->persona->correos()
                ->where('principal', true)
                ->first()?->correo
                ?? $solicitud->persona->correos()->first()?->correo;

            if ($correoRegistrado) {
                $correo = $correoRegistrado;
            }
        }

        if (! $correo) {
            Log::info("SendSolicitudRespuestaJob: Solicitud {$solicitud->codigo} no tiene correo. Se omite envio.");

            return;
        }

        try {
            Mail::to($correo)->send(new SolicitudRespuestaMail($solicitud));
            Log::info("SendSolicitudRespuestaJob: Email enviado a {$correo} para solicitud {$solicitud->codigo}");
        } catch (\Exception $e) {
            Log::error("SendSolicitudRespuestaJob: Error enviando email para {$solicitud->codigo}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendSolicitudRespuestaJob: Job fallido definitivamente para solicitud #{$this->solicitudId}: {$exception->getMessage()}");
    }
}
