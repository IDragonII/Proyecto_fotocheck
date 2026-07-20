<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = Log::with('usuario');

        if ($request->filled('tabla')) {
            $query->where('tabla_afectada', $request->tabla);
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        $logs = $query->orderBy('fecha', 'desc')->paginate(20);

        return response()->json($logs);
    }
}
