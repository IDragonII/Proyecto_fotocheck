<?php

namespace App\Http\Controllers;

use App\Models\CorreoPersona;
use App\Models\Estudiante;
use App\Models\Fotocheck;
use App\Models\Persona;
use App\Models\Trabajador;
use App\Traits\Loggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TrabajadorController extends Controller
{
    use Loggable;

    public function index(Request $request)
    {
        $query = Trabajador::with('persona.correos');

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

        $trabajadores = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($trabajadores);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dni' => 'required|string|max:8|unique:personas,dni',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'codigo_unico' => 'required|string|max:50|unique:trabajadores,codigo_unico',
        ]);

        $persona = Persona::create([
            'dni' => $request->dni,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'fecha_nacimiento' => $request->fecha_nacimiento,
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

        $trabajador = Trabajador::create([
            'persona_id' => $persona->id,
            'codigo_unico' => $request->codigo_unico,
            'codigo_nfs' => $request->codigo_nfs,
            'empresa' => $request->empresa,
            'area' => $request->area,
            'dependencia' => $request->dependencia,
            'cargo' => $request->cargo,
            'regimen' => $request->regimen,
            'resolucion_rectoral' => $request->resolucion_rectoral,
            'vigencia' => $request->vigencia,
            'fecha_emision' => $request->fecha_emision,
            'fecha_ingreso' => $request->fecha_ingreso,
        ]);

        $this->log($request, 'Creacion', 'trabajadores', $trabajador->id, "Trabajador creado: {$persona->nombres} {$persona->apellidos}");

        return response()->json($trabajador->load('persona'), 201);
    }

    public function show($id)
    {
        $trabajador = Trabajador::with('persona')->findOrFail($id);

        return response()->json($trabajador);
    }

    public function update(Request $request, $id)
    {
        $trabajador = Trabajador::with('persona.correos')->findOrFail($id);

        $request->validate([
            'dni' => ['required', 'string', 'max:8', \Illuminate\Validation\Rule::unique('personas', 'dni')->ignore($trabajador->persona_id)],
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'codigo_unico' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('trabajadores', 'codigo_unico')->ignore($id)],
        ]);

        $trabajador->persona->update([
            'dni' => $request->dni,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'grupo_sanguineo' => $request->grupo_sanguineo,
            'url_foto_presencial' => $request->url_foto_presencial,
            'url_foto_virtual' => $request->url_foto_virtual,
            'estado' => $request->estado,
        ]);

        if ($request->has('correos')) {
            $correosRecibidos = $request->correos;
            $correosExistentes = $trabajador->persona->correos()->pluck('id', 'correo')->toArray();

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

                $existing = CorreoPersona::where('persona_id', $trabajador->persona_id)
                    ->where('correo', $correoStr)
                    ->first();

                if (! $existing) {
                    $tienePrincipal = $trabajador->persona->correos()->where('principal', true)->exists();
                    CorreoPersona::create([
                        'persona_id' => $trabajador->persona_id,
                        'correo' => $correoStr,
                        'tipo' => is_array($correoData) ? ($correoData['tipo'] ?? CorreoPersona::determinarTipo($correoStr)) : CorreoPersona::determinarTipo($correoStr),
                        'principal' => ! $tienePrincipal,
                    ]);
                }
            }

            $trabajador->persona->load('correos');
        } elseif ($request->filled('correo')) {
            $correo = $trabajador->persona->correos()->where('principal', true)->first();
            if ($correo) {
                $correo->update(['correo' => $request->correo]);
            } else {
                CorreoPersona::create([
                    'persona_id' => $trabajador->persona_id,
                    'correo' => $request->correo,
                    'tipo' => CorreoPersona::determinarTipo($request->correo),
                    'principal' => true,
                ]);
            }
        }

        $trabajador->update([
            'codigo_unico' => $request->codigo_unico,
            'codigo_nfs' => $request->codigo_nfs,
            'empresa' => $request->empresa,
            'area' => $request->area,
            'dependencia' => $request->dependencia,
            'cargo' => $request->cargo,
            'regimen' => $request->regimen,
            'resolucion_rectoral' => $request->resolucion_rectoral,
            'vigencia' => $request->vigencia,
            'fecha_emision' => $request->fecha_emision,
            'fecha_ingreso' => $request->fecha_ingreso,
        ]);

        $this->log($request, 'Actualizacion', 'trabajadores', $trabajador->id, "Trabajador actualizado: {$trabajador->persona->nombres} {$trabajador->persona->apellidos}");

        return response()->json($trabajador->load('persona.correos'));
    }

    public function destroy($id)
    {
        $trabajador = Trabajador::with('persona')->findOrFail($id);
        $nombre = "{$trabajador->persona->nombres} {$trabajador->persona->apellidos}";
        $trabajador->delete();
        $this->log(request(), 'Eliminacion', 'trabajadores', $id, "Trabajador eliminado: {$nombre}");

        return response()->json(['message' => 'Trabajador eliminado']);
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
                $empresa = trim((string) ($data['EMPRESA'] ?? ''));
                $area = trim((string) ($data['AREA'] ?? ''));
                $dependencia = trim((string) ($data['DEPENDENCIA'] ?? ''));
                $cargo = trim((string) ($data['CONDICION'] ?? ''));
                $regimen = trim((string) ($data['REGIMEN'] ?? ''));
                $resolucionRectoral = trim((string) ($data['RESOLUCION_RECTORAL'] ?? ''));
                $vigencia = trim((string) ($data['VIGENCIA'] ?? ''));
                $fechaEmision = trim((string) ($data['FECHA_EMISION'] ?? ''));
                $fechaIngreso = trim((string) ($data['FECHA_INGRESO'] ?? ''));
                $codigoNfs = trim((string) ($data['CODIGO_NFS'] ?? ''));
                $urlQrImage = trim((string) ($data['URL_QR_IMAGE'] ?? ''));
                $urlQr = trim((string) ($data['URL_QR'] ?? ''));
                $facultad = trim((string) ($data['FACULTAD'] ?? ''));
                $escuelaProfesional = trim((string) ($data['ESCUELA_PROFESIONAL'] ?? ''));

                $esEstudiante = stripos($cargo, 'ESTUDIANTE') !== false;

                if ($esEstudiante) {
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

                    continue;
                }

                $existeTrabajador = Trabajador::where('codigo_unico', $codigoUnico)->first();

                if ($existeTrabajador) {
                    $existeTrabajador->persona->update([
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'telefono' => $telefono !== '' ? $telefono : $existeTrabajador->persona->telefono,
                        'direccion' => $direccion !== '' ? $direccion : $existeTrabajador->persona->direccion,
                        'grupo_sanguineo' => $grupoSanguineo !== '' ? $grupoSanguineo : $existeTrabajador->persona->grupo_sanguineo,
                        'url_foto_presencial' => $urlFotoPresencial !== '' ? $urlFotoPresencial : $existeTrabajador->persona->url_foto_presencial,
                        'url_foto_virtual' => $urlFotoVirtual !== '' ? $urlFotoVirtual : $existeTrabajador->persona->url_foto_virtual,
                    ]);

                    if ($correo !== '') {
                        $existeCorreo = $existeTrabajador->persona->correos()
                            ->where('correo', $correo)
                            ->exists();

                        if (! $existeCorreo) {
                            $tienePrincipal = $existeTrabajador->persona->correos()
                                ->where('principal', true)
                                ->exists();

                            CorreoPersona::create([
                                'persona_id' => $existeTrabajador->persona_id,
                                'correo' => $correo,
                                'tipo' => CorreoPersona::determinarTipo($correo),
                                'principal' => ! $tienePrincipal,
                            ]);
                        }
                    }

                    $camposTrabajador = [];
                    if ($empresa !== '') {
                        $camposTrabajador['empresa'] = $empresa;
                    }
                    if ($area !== '') {
                        $camposTrabajador['area'] = $area;
                    }
                    if ($dependencia !== '') {
                        $camposTrabajador['dependencia'] = $dependencia;
                    }
                    if ($cargo !== '') {
                        $camposTrabajador['cargo'] = $cargo;
                    }
                    if ($regimen !== '') {
                        $camposTrabajador['regimen'] = $regimen;
                    }
                    if ($resolucionRectoral !== '') {
                        $camposTrabajador['resolucion_rectoral'] = $resolucionRectoral;
                    }
                    if ($vigencia !== '') {
                        $camposTrabajador['vigencia'] = $vigencia;
                    }
                    if ($fechaEmision !== '') {
                        $camposTrabajador['fecha_emision'] = $fechaEmision;
                    }
                    if ($fechaIngreso !== '') {
                        $camposTrabajador['fecha_ingreso'] = $fechaIngreso;
                    }
                    if ($codigoNfs !== '') {
                        $camposTrabajador['codigo_nfs'] = $codigoNfs;
                    }
                    if (! empty($camposTrabajador)) {
                        $existeTrabajador->update($camposTrabajador);
                    }

                    $fotocheck = Fotocheck::where('trabajador_id', $existeTrabajador->id)
                        ->where('estado', 'VIGENTE')
                        ->orderBy('fecha_emision', 'desc')
                        ->first();

                    if ($fotocheck) {
                        $camposFotocheck = [];
                        if ($urlQrImage !== '') {
                            $camposFotocheck['qr_imagen'] = $urlQrImage;
                        }
                        if ($urlQr !== '') {
                            $camposFotocheck['url_qr'] = $urlQr;
                        }
                        if (! empty($camposFotocheck)) {
                            $fotocheck->update($camposFotocheck);
                        }
                    }
                    $actualizados++;
                } else {
                    $persona = Persona::where('dni', $dni)->first();

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

                    $nuevo = Trabajador::create([
                        'persona_id' => $persona->id,
                        'codigo_unico' => $codigoUnico,
                        'codigo_nfs' => $codigoNfs,
                        'empresa' => $empresa,
                        'area' => $area,
                        'dependencia' => $dependencia,
                        'cargo' => $cargo,
                        'regimen' => $regimen,
                        'resolucion_rectoral' => $resolucionRectoral,
                        'vigencia' => $vigencia,
                        'fecha_emision' => $fechaEmision !== '' ? $fechaEmision : null,
                        'fecha_ingreso' => $fechaIngreso !== '' ? $fechaIngreso : null,
                    ]);

                    if ($nuevo->codigo_unico) {
                        $codigo = $urlQr !== '' ? null : 'FC-'.strtoupper(Str::random(8));
                        $urlPublica = $urlQr !== ''
                            ? $urlQr
                            : config('app.frontend_url', 'http://localhost:5173')."/{$nuevo->codigo_unico}";
                        Fotocheck::create([
                            'trabajador_id' => $nuevo->id,
                            'codigo' => $codigo,
                            'url_qr' => $urlPublica,
                            'qr_imagen' => $urlQrImage !== '' ? $urlQrImage : null,
                            'estado' => 'VIGENTE',
                            'fecha_emision' => now(),
                        ]);
                    }

                    $creados++;
                }
            }
        });

        $this->log($request, 'Importacion', 'trabajadores', null, "Importados: {$creados}, Actualizados: {$actualizados}, Saltados: {$saltados}");

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
            'EMPRESA', 'AREA', 'DEPENDENCIA', 'CONDICION', 'FACULTAD', 'ESCUELA_PROFESIONAL',
            'REGIMEN', 'RESOLUCION_RECTORAL', 'VIGENCIA', 'FECHA_EMISION', 'FECHA_INGRESO',
            'CODIGO_UNICO', 'CODIGO_NFS', 'URL_QR_IMAGE', 'URL_QR',
        ];

        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X'];

        foreach ($headers as $i => $header) {
            $cell = $sheet->getCell($colLetters[$i].'1');
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true);
        }

        $sample = [
            '70123456', 'JUAN', 'PEREZ GARCIA', '951234567', 'juan.perez@unap.edu.pe', 'Av. Principal 123',
            'O+', 'https://drive.google.com/file/d/ABC/presencial', 'https://drive.google.com/file/d/ABC/virtual',
            'UNA', 'FACULTAD DE INGENIERIA', 'DEP. SISTEMAS', 'DOCENTE', '', '',
            'NOMBRADO', 'RR-001-2020', '2025-12-31', '2020-01-15', '2020-01-15',
            'ABC12345', 'NFS001', 'https://drive.google.com/file/d/ABC/qr-image', 'https://dominio.com/qr/ABC',
        ];

        foreach ($sample as $i => $value) {
            $sheet->getCell($colLetters[$i].'2')->setValue($value);
        }

        foreach (range('A', 'X') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $temp = tempnam(sys_get_temp_dir(), 'plantilla_');
        $writer->save($temp);
        $contents = file_get_contents($temp);
        @unlink($temp);

        return Response::make($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="plantilla_trabajadores.xlsx"',
        ]);
    }
}
