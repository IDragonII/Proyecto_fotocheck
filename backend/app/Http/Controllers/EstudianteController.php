<?php

namespace App\Http\Controllers;

use App\Models\CorreoPersona;
use App\Models\Estudiante;
use App\Models\Persona;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EstudianteController extends Controller
{
    use Loggable;

    public function index(Request $request)
    {
        $query = Estudiante::with('persona.correos');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('persona', function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%")
                    ->orWhere('dni', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->whereHas('persona', function ($q) use ($request) {
                $q->where('estado', $request->estado);
            });
        }

        $estudiantes = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($estudiantes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dni' => ['required', 'string', 'max:8', \Illuminate\Validation\Rule::unique('personas', 'dni')->ignore($estudiante->persona_id)],
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'codigo_universitario' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('estudiantes', 'codigo_universitario')->ignore($id)],
        ]);

        $persona = Persona::create([
            'dni' => $request->dni,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'grupo_sanguineo' => $request->grupo_sanguineo,
            'url_foto_presencial' => $request->url_foto_presencial,
            'url_foto_virtual' => $request->url_foto_virtual,
            'estado' => $request->estado ?? 'ACTIVO',
        ]);

        if ($request->filled('correo')) {
            CorreoPersona::create([
                'persona_id' => $persona->id,
                'correo' => $request->correo,
                'tipo' => CorreoPersona::determinarTipo($request->correo),
                'principal' => true,
            ]);
        }

        $estudiante = Estudiante::create([
            'persona_id' => $persona->id,
            'codigo_universitario' => $request->codigo_universitario,
            'facultad' => $request->facultad,
            'escuela_profesional' => $request->escuela_profesional,
        ]);

        $this->log($request, 'Creacion', 'estudiantes', $estudiante->id, "Estudiante creado: {$persona->nombres} {$persona->apellidos}");

        return response()->json($estudiante->load('persona.correos'));
    }

    public function show($id)
    {
        $estudiante = Estudiante::with('persona.correos')->findOrFail($id);

        return response()->json($estudiante);
    }

    public function update(Request $request, $id)
    {
        $estudiante = Estudiante::with('persona.correos')->findOrFail($id);

        $request->validate([
            'dni' => ['required', 'string', 'max:8', \Illuminate\Validation\Rule::unique('personas', 'dni')->ignore($estudiante->persona_id)],
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'codigo_universitario' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('estudiantes', 'codigo_universitario')->ignore($id)],
        ]);

        $estudiante->persona->update([
            'dni' => $request->dni,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'grupo_sanguineo' => $request->grupo_sanguineo,
            'url_foto_presencial' => $request->url_foto_presencial,
            'url_foto_virtual' => $request->url_foto_virtual,
            'estado' => $request->estado,
        ]);

        if ($request->has('correos')) {
            $correosRecibidos = $request->correos;
            $correosExistentes = $estudiante->persona->correos()->pluck('id', 'correo')->toArray();

            $correosRecibidosArr = is_array($correosRecibidos) ? $correosRecibidos : [$correosRecibidos];
            $correosRecibidosValues = array_column($correosRecibidosArr, 'correo');

            $idsAEliminar = [];
            foreach ($correosExistentes as $correo => $correoId) {
                if (! in_array($correo, $correosRecibidosValues)) {
                    $idsAEliminar[] = $correoId;
                }
            }
            if (! empty($idsAEliminar)) {
                CorreoPersona::whereIn('id', $idsAEliminar)->delete();
            }

            foreach ($correosRecibidosArr as $correoData) {
                $correoStr = is_string($correoData) ? $correoData : ($correoData['correo'] ?? '');
                if ($correoStr === '') {
                    continue;
                }

                $existing = CorreoPersona::where('persona_id', $estudiante->persona_id)
                    ->where('correo', $correoStr)
                    ->first();

                if (! $existing) {
                    $tienePrincipal = $estudiante->persona->correos()->where('principal', true)->exists();
                    CorreoPersona::create([
                        'persona_id' => $estudiante->persona_id,
                        'correo' => $correoStr,
                        'tipo' => is_array($correoData) ? ($correoData['tipo'] ?? CorreoPersona::determinarTipo($correoStr)) : CorreoPersona::determinarTipo($correoStr),
                        'principal' => ! $tienePrincipal,
                    ]);
                }
            }

            $estudiante->persona->load('correos');
        } elseif ($request->filled('correo')) {
            $correo = $estudiante->persona->correos()->where('principal', true)->first();
            if ($correo) {
                $correo->update(['correo' => $request->correo]);
            } else {
                CorreoPersona::create([
                    'persona_id' => $estudiante->persona_id,
                    'correo' => $request->correo,
                    'tipo' => CorreoPersona::determinarTipo($request->correo),
                    'principal' => true,
                ]);
            }
        }

        $estudiante->update([
            'codigo_universitario' => $request->codigo_universitario,
            'facultad' => $request->facultad,
            'escuela_profesional' => $request->escuela_profesional,
        ]);

        $this->log($request, 'Actualizacion', 'estudiantes', $estudiante->id, "Estudiante actualizado: {$estudiante->persona->nombres} {$estudiante->persona->apellidos}");

        return response()->json($estudiante->load('persona.correos'));
    }

    public function destroy($id)
    {
        $estudiante = Estudiante::with('persona')->findOrFail($id);
        $nombre = "{$estudiante->persona->nombres} {$estudiante->persona->apellidos}";
        $estudiante->delete();
        $this->log(request(), 'Eliminacion', 'estudiantes', $id, "Estudiante eliminado: {$nombre}");

        return response()->json(['message' => 'Estudiante eliminado']);
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('archivo');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestCol = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        if ($highestRow < 2) {
            return response()->json(['message' => 'El archivo no tiene datos'], 422);
        }

        $header = [];
        for ($col = 'A'; $col <= $highestCol; $col++) {
            $header[] = strtoupper(trim((string) $sheet->getCell($col.'1')->getValue()));
        }

        $creados = 0;
        $actualizados = 0;
        $saltados = 0;

        DB::transaction(function () use ($sheet, $highestRow, $header, &$creados, &$actualizados, &$saltados) {
            for ($i = 2; $i <= $highestRow; $i++) {
                $data = [];
                $colIndex = 0;
                for ($col = 'A'; $col <= $highestCol; $col++) {
                    $cell = $sheet->getCell($col.$i);
                    $data[$header[$colIndex]] = trim((string) $cell->getValue());
                    $colIndex++;
                }

                $dni = trim((string) ($data['DNI'] ?? ''));
                $nombres = trim((string) ($data['NOMBRES'] ?? ''));
                $apellidos = trim((string) ($data['APELLIDOS'] ?? ''));
                $codigoUnico = trim((string) ($data['CODIGO_UNICO'] ?? ''));

                if ($dni === '' || $nombres === '' || $apellidos === '' || $codigoUnico === '') {
                    $saltados++;

                    continue;
                }

                $telefono = trim((string) ($data['TELEFONO'] ?? ''));
                $correo = trim((string) ($data['CORREO'] ?? ''));
                $direccion = trim((string) ($data['DIRECCION'] ?? ''));
                $grupoSanguineo = trim((string) ($data['GRUPO_SANGUINEO'] ?? ''));
                $urlFotoPresencial = trim((string) ($data['URL_FOTO_PRESENCIAL'] ?? ''));
                $urlFotoVirtual = trim((string) ($data['URL_FOTO_VIRTUAL'] ?? ''));
                $facultad = trim((string) ($data['FACULTAD'] ?? ''));
                $escuelaProfesional = trim((string) ($data['ESCUELA_PROFESIONAL'] ?? ''));

                $persona = Persona::where('dni', $dni)->first();
                $existeEstudiante = $persona
                    ? $persona->estudiantes()->first()
                    : null;

                if ($existeEstudiante) {
                    $camposPersona = [];
                    if ($nombres !== $persona->nombres) {
                        $camposPersona['nombres'] = $nombres;
                    }
                    if ($apellidos !== $persona->apellidos) {
                        $camposPersona['apellidos'] = $apellidos;
                    }
                    if ($telefono !== '' && $telefono !== $persona->telefono) {
                        $camposPersona['telefono'] = $telefono;
                    }
                    if ($direccion !== '' && $direccion !== $persona->direccion) {
                        $camposPersona['direccion'] = $direccion;
                    }
                    if ($grupoSanguineo !== '' && $grupoSanguineo !== $persona->grupo_sanguineo) {
                        $camposPersona['grupo_sanguineo'] = $grupoSanguineo;
                    }
                    if ($urlFotoPresencial !== '' && $urlFotoPresencial !== $persona->url_foto_presencial) {
                        $camposPersona['url_foto_presencial'] = $urlFotoPresencial;
                    }
                    if ($urlFotoVirtual !== '' && $urlFotoVirtual !== $persona->url_foto_virtual) {
                        $camposPersona['url_foto_virtual'] = $urlFotoVirtual;
                    }

                    if (! empty($camposPersona)) {
                        $persona->update($camposPersona);
                    }

                    if ($correo !== '') {
                        $existeCorreo = $persona->correos()
                            ->where('correo', $correo)
                            ->exists();

                        if (! $existeCorreo) {
                            $tienePrincipal = $persona->correos()
                                ->where('principal', true)
                                ->exists();

                            CorreoPersona::create([
                                'persona_id' => $persona->id,
                                'correo' => $correo,
                                'tipo' => CorreoPersona::determinarTipo($correo),
                                'principal' => ! $tienePrincipal,
                            ]);
                        }
                    }

                    $existeEstudiante->update([
                        'codigo_universitario' => $codigoUnico,
                        'facultad' => $facultad !== '' ? $facultad : $existeEstudiante->facultad,
                        'escuela_profesional' => $escuelaProfesional !== '' ? $escuelaProfesional : $existeEstudiante->escuela_profesional,
                    ]);

                    $actualizados++;
                } else {
                    if ($persona) {
                        $camposPersona = [];
                        if ($nombres !== $persona->nombres) {
                            $camposPersona['nombres'] = $nombres;
                        }
                        if ($apellidos !== $persona->apellidos) {
                            $camposPersona['apellidos'] = $apellidos;
                        }
                        if ($telefono !== '' && $telefono !== $persona->telefono) {
                            $camposPersona['telefono'] = $telefono;
                        }
                        if ($direccion !== '' && $direccion !== $persona->direccion) {
                            $camposPersona['direccion'] = $direccion;
                        }
                        if ($grupoSanguineo !== '' && $grupoSanguineo !== $persona->grupo_sanguineo) {
                            $camposPersona['grupo_sanguineo'] = $grupoSanguineo;
                        }
                        if ($urlFotoPresencial !== '' && $urlFotoPresencial !== $persona->url_foto_presencial) {
                            $camposPersona['url_foto_presencial'] = $urlFotoPresencial;
                        }
                        if ($urlFotoVirtual !== '' && $urlFotoVirtual !== $persona->url_foto_virtual) {
                            $camposPersona['url_foto_virtual'] = $urlFotoVirtual;
                        }

                        if (! empty($camposPersona)) {
                            $persona->update($camposPersona);
                        }
                    } else {
                        $persona = Persona::create([
                            'dni' => $dni,
                            'nombres' => $nombres,
                            'apellidos' => $apellidos,
                            'telefono' => $telefono,
                            'direccion' => $direccion,
                            'grupo_sanguineo' => $grupoSanguineo,
                            'url_foto_presencial' => $urlFotoPresencial,
                            'url_foto_virtual' => $urlFotoVirtual,
                            'estado' => 'ACTIVO',
                        ]);
                    }

                    if ($correo !== '') {
                        $existeCorreo = $persona->correos()
                            ->where('correo', $correo)
                            ->exists();

                        if (! $existeCorreo) {
                            $tienePrincipal = $persona->correos()
                                ->where('principal', true)
                                ->exists();

                            CorreoPersona::create([
                                'persona_id' => $persona->id,
                                'correo' => $correo,
                                'tipo' => CorreoPersona::determinarTipo($correo),
                                'principal' => ! $tienePrincipal,
                            ]);
                        }
                    }

                    Estudiante::create([
                        'persona_id' => $persona->id,
                        'codigo_universitario' => $codigoUnico,
                        'facultad' => $facultad,
                        'escuela_profesional' => $escuelaProfesional,
                    ]);

                    $creados++;
                }
            }
        });

        $this->log($request, 'Importacion', 'estudiantes', null, "Importados: {$creados}, Actualizados: {$actualizados}, Saltados: {$saltados}");

        return response()->json([
            'message' => 'Importacion completada',
            'creados' => $creados,
            'actualizados' => $actualizados,
            'saltados' => $saltados,
        ]);
    }

    public function plantilla()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'DNI', 'NOMBRES', 'APELLIDOS', 'TELEFONO', 'CORREO', 'DIRECCION',
            'GRUPO_SANGUINEO', 'URL_FOTO_PRESENCIAL', 'URL_FOTO_VIRTUAL',
            'CODIGO_UNICO', 'FACULTAD', 'ESCUELA_PROFESIONAL',
        ];

        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];

        foreach ($headers as $i => $header) {
            $cell = $sheet->getCell($colLetters[$i].'1');
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true);
        }

        $sample = [
            '70123456', 'JUAN', 'PEREZ GARCIA', '951234567', 'juan.perez@est.unap.pe', 'Av. Principal 123',
            'O+', 'https://drive.google.com/file/d/ABC/presencial', 'https://drive.google.com/file/d/ABC/virtual',
            'ABC12345', 'INGENIERIA DE SISTEMAS', 'INGENIERIA DE SISTEMAS',
        ];

        foreach ($sample as $i => $value) {
            $sheet->getCell($colLetters[$i].'2')->setValue($value);
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $temp = tempnam(sys_get_temp_dir(), 'plantilla_');
        $writer->save($temp);
        $contents = file_get_contents($temp);
        @unlink($temp);

        return Response::make($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="plantilla_estudiantes.xlsx"',
        ]);
    }
}
