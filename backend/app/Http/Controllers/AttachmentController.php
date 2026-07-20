<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function show(Request $request, int $id, string $filename)
    {
        $solicitud = Solicitud::findOrFail($id);

        if (! $solicitud->adjuntos) {
            return response()->json(['mensaje' => 'No hay adjuntos'], 404);
        }

        $adjuntos = $solicitud->adjuntos;

        if (! is_array($adjuntos)) {
            $adjuntos = json_decode($adjuntos, true);
        }

        $path = $solicitud->codigo.'/'.$filename;

        if (! in_array($path, $adjuntos)) {
            return response()->json(['mensaje' => 'Archivo no encontrado'], 404);
        }

        $disk = config('filesystems.disks.solicitudes.driver', 'local');

        if ($disk === 'local') {
            $fullPath = Storage::disk('solicitudes')->path($path);

            if (! Storage::disk('solicitudes')->exists($path)) {
                return response()->json(['mensaje' => 'Archivo no encontrado en almacenamiento'], 404);
            }

            $mime = mime_content_type($fullPath);

            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        $url = Storage::disk('solicitudes')->temporaryUrl($path, now()->addMinutes(30));

        return redirect($url);
    }
}
