<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #000; }
        .page { width: 100%; padding: 40px; }

        /* ── Colores ── */
        .azul { color: #00467F; }
        .azul-bg { background-color: #DCF0FA; }
        .gris { color: #646464; font-size: 8pt; }

        /* ── Tabla cabecera (3 columnas) ── */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .header-table td {
            border: 1px solid #00467F;
            text-align: center;
            vertical-align: middle;
            padding: 10px;
        }
        .header-table .logo-cell { width: 22%; }
        .header-table .title-cell { width: 56%; }
        .header-table .oti-cell { width: 22%; }
        .header-table .title-text { font-size: 13pt; font-weight: bold; color: #00467F; line-height: 1.4; }

        /* ── Secciones ── */
        .section-header {
            background-color: #DCF0FA;
            border: 1px solid #00467F;
            padding: 6px 8px;
            font-weight: bold;
            color: #00467F;
            font-size: 9pt;
        }
        .section-content {
            border: 1px solid #00467F;
            border-top: none;
            padding: 8px;
            min-height: 20px;
        }

        /* ── Tabla datos ── */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .data-table td {
            border: 1px solid #00467F;
            padding: 5px;
            vertical-align: top;
        }
        .data-table .label-cell {
            width: 34%;
            font-weight: bold;
            font-size: 8pt;
        }
        .data-table .value-cell { width: 66%; }

        /* ── Checkboxes (vinculo y detalles) ── */
        .check-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .check-table td {
            border: 1px solid #00467F;
            padding: 4px;
            vertical-align: middle;
        }
        .check-table .chk { width: 5%; text-align: center; }
        .check-table .lbl { width: 28.33%; }

        /* ── Adjunto ── */
        .adjunto-content {
            border: 1px solid #00467F;
            border-top: none;
            padding: 8px;
            min-height: 60px;
        }
        .nota-bold { font-weight: bold; font-size: 8pt; }
        .nota-item { font-size: 8pt; color: #646464; }

        /* ── Firma ── */
        .firm-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .firm-table td { border: none; vertical-align: bottom; }
        .firm-table .empty-cell { width: 66%; }
        .firm-table .firm-cell { width: 34%; text-align: center; }
        .firm-line { border-bottom: 1px solid #000; width: 80%; margin: 0 auto 4px auto; }

        /* ── Espaciado ── */
        .spacer { height: 10px; }
        .spacer-sm { height: 6px; }
    </style>
</head>
<body>
<div class="page">

    {{-- ════════════ HEADER: UNAP | TITULO | OTI ════════════ --}}
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(file_exists(public_path('assets/img/unap.png')))
                    <img src="{{ public_path('assets/img/unap.png') }}" style="max-width:60px; max-height:60px;" />
                @else
                    <span class="azul" style="font-weight:bold; font-size:12pt;">UNAP</span>
                @endif
            </td>
            <td class="title-cell">
                <div class="title-text">FORMATO</div>
                <div class="title-text">SOLICITUDES</div>
            </td>
            <td class="oti-cell">
                @if(file_exists(public_path('assets/img/oti_logo.png')))
                    <img src="{{ public_path('assets/img/oti_logo.png') }}" style="max-width:50px; max-height:50px;" />
                @else
                    <span class="azul" style="font-weight:bold; font-size:12pt;">OTI</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- ════════════ TITULO FUT ════════════ --}}
    <div class="spacer"></div>
    <p class="azul" style="font-weight:bold; font-size:9pt;">FORMULARIO UNICO DE TRAMITE (FUT)</p>
    <div class="spacer"></div>

    {{-- ════════════ DESTINATARIO ════════════ --}}
    <p>Senor:</p>
    <div class="spacer-sm"></div>
    <p><strong>Jefe de la Oficina de Tecnologias de la Informacion</strong></p>
    <p>Ing. Edison Jossep Ramos Munoz</p>
    <div class="spacer"></div>

    {{-- ════════════ 1. SOLICITO ════════════ --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
        <tr>
            <td class="section-header">1. SOLICITO:</td>
        </tr>
        <tr>
            <td class="section-content" style="min-height:55px;">
                {{ $solicitud->tipoSolicitud->nombre ?? ' ' }}
            </td>
        </tr>
    </table>

    {{-- ════════════ 2. DATOS DEL SOLICITANTE ════════════ --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:0;">
        <tr>
            <td class="section-header">2. DATOS DEL SOLICITANTE:</td>
        </tr>
    </table>
    <table class="data-table">
        <tr>
            <td class="label-cell">APELLIDOS Y NOMBRES:</td>
            <td class="value-cell">{{ $solicitud->persona->nombres ?? '' }} {{ $solicitud->persona->apellidos ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">DNI / CI / N° de Identificacion:</td>
            <td class="value-cell">{{ $solicitud->persona->dni ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">DOMICILIO:</td>
            <td class="value-cell">{{ $solicitud->persona->direccion ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">CELULAR:</td>
            <td class="value-cell">{{ $solicitud->persona->telefono ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">CORREO PERSONAL ALTERNATIVO:</td>
            <td class="value-cell">{{ $solicitud->correo_personal ?? '' }}</td>
        </tr>
    </table>

    {{-- ════════════ 3. VINCULO ════════════ --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:0;">
        <tr>
            <td class="section-header">3. TIPO DE VINCULO CON LA INSTITUCION&nbsp;&nbsp;&nbsp;Marcar con una (X) el CARGO / FUNCION:</td>
        </tr>
    </table>
    @php
        $vinculos = [
            'DOCENTE NOMBRADO',
            'DOCENTE CONTRATADO',
            'ESTUDIANTE',
            'ADMINISTRATIVO NOMBRADO',
            'ADMINISTRATIVO CONTRATADO',
            'OTROS',
            'ADMINISTRATIVO CAS',
            'LOCACION DE SERVICIOS',
        ];
        $vinculoSeleccionado = strtoupper(trim($solicitud->vinculo ?? ''));
    @endphp
    <table class="check-table">
        @for($i = 0; $i < count($vinculos); $i += 3)
            <tr>
                @for($j = 0; $j < 3; $j++)
                    @php $idx = $i + $j; @endphp
                    @if($idx < count($vinculos))
                        <td class="chk">{{ $vinculoSeleccionado === strtoupper($vinculos[$idx]) ? '(X)' : '( )' }}</td>
                        <td class="lbl">{{ $vinculos[$idx] }}</td>
                    @else
                        <td class="chk">( )</td>
                        <td class="lbl">&nbsp;</td>
                    @endif
                @endfor
            </tr>
        @endfor
    </table>

    {{-- ════════════ 4. DETALLES DE LA SOLICITUD ════════════ --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:0;">
        <tr>
            <td class="section-header">4. DETALLES DE LA SOLICITUD:</td>
        </tr>
    </table>
    @php
        $tipoNombre = $solicitud->tipoSolicitud->nombre ?? '';
        $motivo = $solicitud->motivo_solicitud ?? '';
    @endphp
    <table class="data-table">
        <tr>
            <td class="label-cell">TIPO DE SOLICITUD:</td>
            <td class="value-cell">{{ $tipoNombre }}</td>
        </tr>
        @if($motivo)
        <tr>
            <td class="label-cell">MOTIVO:</td>
            <td class="value-cell">{{ $motivo }}</td>
        </tr>
        @endif
        @if($solicitud->tipo_cuenta)
        <tr>
            <td class="label-cell">TIPO DE CUENTA:</td>
            <td class="value-cell">{{ $solicitud->tipo_cuenta }}</td>
        </tr>
        @endif
        @if($solicitud->sistema_especifico)
        <tr>
            <td class="label-cell">SISTEMA ESPECIFICO:</td>
            <td class="value-cell">{{ $solicitud->sistema_especifico }}</td>
        </tr>
        @endif
        @if($solicitud->correo_personal)
        <tr>
            <td class="label-cell">CORREO PERSONAL:</td>
            <td class="value-cell">{{ $solicitud->correo_personal }}</td>
        </tr>
        @endif
        @if($solicitud->oficina_sopporte)
        <tr>
            <td class="label-cell">OFICINA:</td>
            <td class="value-cell">{{ $solicitud->oficina_sopporte }}</td>
        </tr>
        @endif
        @if($solicitud->dificultad)
        <tr>
            <td class="label-cell">DIFICULTAD:</td>
            <td class="value-cell">{{ $solicitud->dificultad }}</td>
        </tr>
        @endif
        @if($solicitud->adjuntos)
        <tr>
            <td class="label-cell">ADJUNTOS:</td>
            <td class="value-cell">{{ is_array($solicitud->adjuntos) ? implode(', ', $solicitud->adjuntos) : $solicitud->adjuntos }}</td>
        </tr>
        @endif
        @if($solicitud->observaciones)
        <tr>
            <td class="label-cell">OBSERVACIONES:</td>
            <td class="value-cell">{{ $solicitud->observaciones }}</td>
        </tr>
        @endif
    </table>

    {{-- ════════════ 5. ADJUNTO ════════════ --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
        <tr>
            <td class="section-header">5. ADJUNTO:</td>
        </tr>
        <tr>
            <td class="adjunto-content">
                <p class="nota-bold">Nota: Adjuntar obligatoriamente para creaciones de correo y aula virtual:</p>
                <p class="nota-item">&nbsp;&nbsp;&nbsp;&nbsp;- COPIA DE DNI</p>
                <p class="nota-item">&nbsp;&nbsp;&nbsp;&nbsp;- COPIA DE RESOLUCION RECTORAL O DECANAL</p>
            </td>
        </tr>
    </table>

    <div class="spacer"></div>
    <div class="spacer"></div>

    {{-- ════════════ FECHA ════════════ --}}
    @php
        $fecha = $solicitud->fecha_solicitud
            ? $solicitud->fecha_solicitud->locale('es')->isoFormat('D [de] MMMM [del] YYYY')
            : now()->locale('es')->isoFormat('D [de] MMMM [del] YYYY');
    @endphp
    <p>Puno, {{ $fecha }}.</p>

    <div class="spacer"></div>
    <div class="spacer"></div>

    {{-- ════════════ FIRMA ════════════ --}}
    <table class="firm-table">
        <tr>
            <td class="empty-cell">&nbsp;</td>
            <td class="firm-cell">
                <div class="firm-line">&nbsp;</div>
                <strong style="font-size:8pt;">FIRMA</strong>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
