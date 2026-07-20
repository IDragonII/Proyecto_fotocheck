<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ImageProxyController extends Controller
{
    private function normalizeDriveUrl(string $url): string
    {
        $url = trim($url);

        if (str_contains($url, 'drive.google.com')) {
            if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
                return "https://lh3.googleusercontent.com/d/{$m[1]}";
            }
            if (preg_match('/id=([a-zA-Z0-9_-]+)/', $url, $m)) {
                return "https://lh3.googleusercontent.com/d/{$m[1]}";
            }
        }

        return $url;
    }

    public function show(Request $request, string $url)
    {
        $url = strtr($url, '-_', '+/');
        $decodedUrl = base64_decode($url, true);

        if (! $decodedUrl || ! filter_var($decodedUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['message' => 'URL invalida'], 400);
        }

        $directUrl = $this->normalizeDriveUrl($decodedUrl);

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Accept' => 'image/*'])
                ->get($directUrl);

            if (! $response->successful()) {
                return response()->json(['message' => 'No se pudo obtener la imagen'], 404);
            }

            $contentType = $response->header('Content-Type', 'image/jpeg');

            return response($response->body(), 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=86400')
                ->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al proxy la imagen'], 502);
        }
    }
}
