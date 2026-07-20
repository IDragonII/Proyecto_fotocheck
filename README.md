# Sistema Fotocheck - Universidad Nacional del Altiplano

Sistema web para la gestion de fotochecks institucionales de la Universidad Nacional del Altiplano, Puno. Permite importar trabajadores desde Excel, generar fotochecks con codigos QR unicos y visualizarlos en un visor web con diseno institucional.

---

## Stack Tecnologico

| Capa | Tecnologia |
|------|-----------|
| Backend | Laravel 13 (PHP 8.3) |
| Frontend | React 19 + Vite 8 |
| Base de Datos | MySQL (MariaDB) |
| Servidor Local | XAMPP (Apache puerto 80) |
| Cola | Laravel Queue (database driver) |
| PDF | barryvdh/laravel-dompdf v3.1 |
| Email | SMTP (Gmail/UNAP) |
| Documentacion | LaTeX (pdfLaTeX) |

---

## Estructura del Proyecto

```
fotocheck-project/
├── backend/                    # API REST Laravel
│   ├── app/
│   │   ├── Http/Controllers/   # Controladores API
│   │   ├── Jobs/               # Jobs de cola (SendSolicitudEmailJob)
│   │   ├── Mail/               # Mailables (SolicitudTicketMail)
│   │   ├── Models/             # Modelos Eloquent (Persona, Trabajador, Estudiante, etc.)
│   │   └── Traits/             # Loggable (auditoria)
│   ├── database/migrations/    # Migraciones MySQL
│   ├── resources/views/emails/  # Plantillas Blade para PDF
│   └── routes/api.php          # Rutas API
├── frontend/                   # SPA React
│   ├── src/
│   │   ├── pages/              # Vistas (Dashboard, Trabajadores, Estudiantes, etc.)
│   │   ├── services/api.js     # Cliente HTTP
│   │   └── assets/             # Logo, firma
│   └── public/                 # Favicon
├── CREDENCIALES.txt            # Credenciales de acceso
└── README.md
```

---

## Modulos del Sistema

### 1. Dashboard
- Estadisticas generales: trabajadores, fotochecks, usuarios, accesos QR
- 5 graficos dinamicos con porcentajes:
  - Distribucion de Personal: Administrativos vs Docentes
  - Fotos: Presencial vs Digital
  - Disponibilidad de Fotografia por Tipo
  - Distribucion por Condicion Laboral (barras)
  - Integridad de la Informacion de Contacto

### 2. Gestion de Trabajadores
- CRUD completo de trabajadores
- Importacion masiva desde Excel (.xlsx/.xls/.csv)
- Descarga de plantilla Excel con formato esperado
- Columnas importadas: DNI, Nombres, Apellidos, Correo, Telefono, Condicion, Facultad, Escuela Profesional, Codigo Unico, Codigo NFS, URL Foto Presencial, URL Foto Virtual, URL QR Image, URL QR
- Deteccion automatica: si CONDICION contiene "ESTUDIANTE", se crea en la tabla `estudiantes` en vez de `trabajadores`
- Busqueda por nombre, apellido o DNI
- Paginacion de 15 registros

### 3. Gestion de Estudiantes
- CRUD completo de estudiantes
- Importacion masiva desde Excel (.xlsx/.xls/.csv)
- Columnas importadas: DNI, Nombres, Apellidos, Correo, Telefono, Codigo Unico (→ codigo_universitario), Facultad, Escuela Profesional
- Busqueda por nombre, apellido o DNI
- Paginacion de 15 registros

### 4. Generacion de Fotochecks
- Generacion automatica de fotochecks para trabajadores activos sin fotocheck vigente
- Codigo unico por fotocheck (formato FC-XXXXXXXX)
- Estados: VIGENTE, ANULADO
- Busqueda por nombre o DNI del trabajador

### 5. Visualizador Publico de Fotochecks
- URL publica: `{dominio}/{codigo_unico}` (sin autenticacion)
- Tarjeta CSS con efecto flip (anverso/reverso)
- **Anverso**: Logo universidad, foto del trabajador, nombre, cargo, NFC, codigo
- **Reverso**: Datos complementarios (contacto, informacion laboral, firma autorizada)
- Proxy de imagenes para Google Drive
- Registro de accesos QR (IP, navegador, fecha)
- Rate limiting: 30 req/min

### 6. Usuarios y Seguridad
- CRUD de usuarios con roles
- Bloqueo de cuenta: 5 intentos fallidos = 15 min de bloqueo
- Expiracion de sesion configurable (default 120 min)
- Rate limiting: Login 5/min, API 60/min
- Headers de seguridad: HSTS, X-Frame-Options DENY, nosniff, XSS protection
- Auditors automatica via trait Loggable

### 7. Roles y Permisos
- Roles: SUPER_ADMIN (100), ADMIN (80), ADMINISTRADOR_FOTOCHECK (50), CONSULTOR_FOTOCHECK (10), ADMINISTRADOR_SOLICITUD, CONSULTOR_SOLICITUD
- 25 permisos granulares (trabajadores, estudiantes, fotochecks, usuarios, roles, permisos, logs, etc.)
- Asignacion de multiples roles por usuario
- Permisos directos por usuario (additive, solo SUPER_ADMIN gestiona)
- SUPER_ADMIN bypasea todos los permisos

### 8. Logs de Auditoria
- Registro automatico de acciones: Creacion, Actualizacion, Eliminacion, Importacion, etc.
- Filtros por accion, tabla, usuario
- Paginacion de 50 registros

### 9. Accesos QR
- Registro de escaneos de QR
- Datos capturados: IP, navegador, fecha/hora
- Historial por trabajador

### 10. Generacion de PDF y Envio de Email (FUT)
- Al crear un ticket via API externa (`POST /api/ext/tickets`), se genera automaticamente un **FUT (Formulario Unico de Tramite)** en formato PDF
- El PDF replica el diseno original del sistema JSP anterior: header con logos UNAP/OTI, 5 secciones (SOLICITO, DATOS, VINCULO, DETALLES, ADJUNTO), fecha y firma
- Se envia por correo electronico a `correo_personal` del solicitante usando **DomPDF** para generar el PDF en memoria (nunca se guarda en disco)
- El envio de email es **fire-and-forget** via Laravel Queue (job `SendSolicitudEmailJob` con 3 reintentos)
- Mapeo automatico de checkboxes: tipo_solicitud + motivo_solicitud → checkbox seleccionado en Seccion 4

---

## Base de Datos

### Esquema Normalizado

```
personas (base unificada)
├── correos_persona (multiples correos por persona)
├── trabajadores (datos laborales, FK → personas)
└── estudiantes (datos academicos, FK → personas)

fotochecks → trabajadores
accesos_qr → trabajadores

oficinas
solicitudes (tickets FUT) → personas, oficinas, usuarios
solicitud_derivaciones → solicitudes, oficinas, usuarios

usuarios → oficinas → roles (via usuario_roles) → permisos (via rol_permisos)
usuario_permisos (permisos directos al usuario)
logs → usuarios
```

### Tablas Principales

| Tabla | Descripcion |
|-------|-------------|
| `personas` | Base unificada (dni, nombres, apellidos, telefono, foto, estado) |
| `correos_persona` | Emails multiples por persona (PERSONAL/INSTITUCIONAL/ALTERNATIVO) |
| `trabajadores` | Datos laborales (empresa, area, cargo, regimen, etc.) → persona_id |
| `estudiantes` | Datos academicos (facultad, escuela_profesional, codigo_universitario) → persona_id |
| `fotochecks` | Fotochecks con QR (codigo, url_qr, estado VIGENTE/VENCIDO/ANULADO) |
| `accesos_qr` | Registro de escaneos QR |
| `usuarios` | Usuarios del sistema con contrasenas |
| `roles` | Roles del sistema (SUPER_ADMIN, ADMIN, etc.) |
| `permisos` | Permisos granulares (25 permisos) |
| `solicitudes` | Tickets FUT (CORREO, CONTRASENA, ACTIVACION, etc.) |
| `oficinas` | Catalogo de oficinas para solicitudes |
| `logs` | Auditoria del sistema |

### Columnas Clave de `personas`

- `dni` (8 digitos, unico)
- `nombres`, `apellidos` (VARCHAR 100)
- `url_foto_presencial` - URL foto presencial (Google Drive)
- `url_foto_virtual` - URL foto virtual (Google Drive)
- `estado` - ACTIVO, INACTIVO, SUSPENDIDO

### Columnas Clave de `trabajadores`

- `persona_id` - FK → personas
- `codigo_unico` (VARCHAR 50, unico) - Codigo para URL publica
- `codigo_nfs` - Codigo NFC/NFS
- `empresa`, `area`, `dependencia`, `cargo`, `regimen`

### Columnas Clave de `estudiantes`

- `persona_id` - FK → personas
- `codigo_universitario` (VARCHAR 50)
- `facultad`, `escuela_profesional`

---

## Credenciales por Defecto

| Rol | Usuario | Contrasena |
|-----|---------|------------|
| SUPER_ADMIN | `SUPER_ADMIN.UNAP` | `Un@Super!2026#Seg` |
| ADMIN | `ADMIN.UNAP` | `Un@Adm!n2026#Seg` |
| ADMINISTRADOR_FOTOCHECK | `ADMINISTRADOR_FOTOCHECK.UNAP` | `F0t0ch3ck!2026$` |
| CONSULTOR_FOTOCHECK | `CONSULTOR_FOTOCHECK.UNAP` | `C0nsult0r!2026#` |

> **Nota**: Las credenciales completas estan en `CREDENCIALES.txt` (no commitear al repositorio).

---

## Instalacion

### Requisitos
- PHP 8.3+
- Composer
- Node.js 18+
- MySQL/MariaDB
- XAMPP (o similar)

### Pasos

```bash
# 1. Clonar el repositorio
git clone <url-repositorio>
cd fotocheck-project

# 2. Configurar backend
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed

# 3. Configurar frontend
cd ../frontend
npm install
npm run build

# 4. Iniciar servidores
cd ../backend
composer dev    # Inicia artisan serve + queue + vite
```

### Configuracion XAMPP
- Apache en puerto 80
- MySQL en puerto 3306
- Base de datos: `sistema_fotocheck`
- Usuario: `root`, sin contrasena

---

## Comandos Disponibles

### Backend (`/backend`)
```bash
composer setup        # Instalacion completa
composer dev          # Servidor de desarrollo concurrente
composer test         # Ejecutar pruebas
php artisan migrate   # Ejecutar migraciones
php artisan test      # PHPUnit tests
./vendor/bin/pint     # Formateo de codigo (Laravel Pint)
```

### Frontend (`/frontend`)
```bash
npm run dev      # Servidor de desarrollo Vite
npm run build    # Build de produccion
npm run lint     # ESLint
npm run preview  # Preview de produccion
```

---

## API Endpoints

### Publicos (sin autenticacion)
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| POST | `/api/login` | Iniciar sesion |
| GET | `/api/public/fotocheck/{codigo}` | Obtener fotocheck por codigo unico |
| GET | `/api/proxy/image/{url}` | Proxy de imagenes (Google Drive) |

### Protegidos (requiere sesion)
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | `/api/dashboard` | Estadisticas del dashboard |
| GET/POST | `/api/trabajadores` | Listar/crear trabajadores |
| PUT/DELETE | `/api/trabajadores/{id}` | Actualizar/eliminar trabajador |
| GET | `/api/plantilla-trabajadores` | Descargar plantilla Excel trabajadores |
| POST | `/api/trabajadores/importar` | Importar trabajadores desde Excel |
| GET/POST | `/api/estudiantes` | Listar/crear estudiantes |
| PUT/DELETE | `/api/estudiantes/{id}` | Actualizar/eliminar estudiante |
| GET | `/api/plantilla-estudiantes` | Descargar plantilla Excel estudiantes |
| POST | `/api/estudiantes/importar` | Importar estudiantes desde Excel |
| GET/POST | `/api/fotochecks` | Listar/crear fotochecks |
| DELETE | `/api/fotochecks/{id}` | Anular fotocheck |
| POST | `/api/fotochecks/generar` | Generar fotochecks masivos |
| GET/POST | `/api/usuarios` | Listar/crear usuarios |
| GET/POST | `/api/roles` | Listar/crear roles |
| GET | `/api/logs` | Listar logs de auditoria |
| GET | `/api/accesos-qr` | Listar accesos QR |
| GET/POST | `/api/api-keys` | Gestion de claves API |

### Externos (requiere Clave API)
| Metodo | Ruta | Descripcion | Permiso |
|--------|------|-------------|---------|
| POST | `/api/ext/tickets` | Crear ticket | `tickets_crear` |
| GET | `/api/ext/tickets/{codigo}` | Consultar ticket por codigo | `tickets_consultar` |
| GET | `/api/ext/persona/dni/{dni}` | Datos de persona por DNI | `dni_consultar` |
| GET | `/api/ext/tipo-solicitudes` | Listar tipos de solicitud activos | `tipos_solicitud_consultar` |

---

## Conexion de Sistemas Externos

### Autenticacion

Todos los endpoints externos requieren una **Clave API** en el header:

```
Authorization: Bearer <tu_clave_api>
```

La clave se genera desde el panel administrativo en `/api-keys` (solo usuarios con rol SUPER_ADMIN o ADMIN).

### Endpoints Disponibles

| Metodo | Ruta | Descripcion | Permiso Requerido |
|--------|------|-------------|-------------------|
| POST | `/api/ext/tickets` | Crear ticket | `tickets_crear` |
| GET | `/api/ext/tickets/{codigo}` | Consultar ticket por codigo | `tickets_consultar` |
| GET | `/api/ext/persona/dni/{dni}` | Datos de persona por DNI | `dni_consultar` |
| GET | `/api/ext/tipo-solicitudes` | Listar tipos de solicitud activos | `tipos_solicitud_consultar` |

### Ejemplos de Conexion

#### 1. Consultar tipos de solicitud

```bash
curl -X GET "http://localhost:8000/api/ext/tipo-solicitudes" \
  -H "Authorization: Bearer TU_CLAVE_API"
```

**Respuesta exitosa (200):**
```json
{
  "data": [
    {
      "id": 1,
      "nombre": "CORREO",
      "descripcion": "Solicitud de correo institucional",
      "oficina": "Direccion de Tecnologias de la Informacion"
    },
    {
      "id": 2,
      "nombre": "CONTRASEÑA",
      "descripcion": "Recuperacion o cambio de contrasena",
      "oficina": "Direccion de Tecnologias de la Informacion"
    },
    {
      "id": 3,
      "nombre": "ACTIVACION",
      "descripcion": "Activacion de cuenta o servicio",
      "oficina": "Direccion de Tecnologias de la Informacion"
    },
    {
      "id": 4,
      "nombre": "AULA_VIRTUAL",
      "descripcion": "Soporte para aula virtual",
      "oficina": "Secretaria General"
    },
    {
      "id": 5,
      "nombre": "FIRMA_DIGITAL",
      "descripcion": "Solicitud de firma digital",
      "oficina": "Secretaria General"
    },
    {
      "id": 6,
      "nombre": "DOMINIO",
      "descripcion": "Solicitud de dominio o subdominio",
      "oficina": "Direccion de Tecnologias de la Informacion"
    }
  ]
}
```

#### 2. Consultar persona por DNI

```bash
curl -X GET "http://localhost:8000/api/ext/persona/dni/01200053" \
  -H "Authorization: Bearer TU_CLAVE_API"
```

**Respuesta exitosa (200):**
```json
{
  "data": {
    "dni": "01200053",
    "nombres": "FRANZ LUDWIG",
    "apellidos": "ALIAGA MONTESINOS",
    "telefono": "51982156460",
    "direccion": "",
    "correo": "ludwig.aliaga@unap.edu.pe",
    "correos": [
      {
        "correo": "ludwig.aliaga@unap.edu.pe",
        "tipo": "INSTITUCIONAL",
        "principal": true
      }
    ]
  }
}
```

**Persona no encontrada (404):**
```json
{
  "mensaje": "Persona no encontrada con el DNI proporcionado"
}
```

#### 3. Crear un ticket (con archivos adjuntos)

**Opcion A: Subir archivos (multipart/form-data)**

```bash
curl -X POST "http://localhost:8000/api/ext/tickets" \
  -H "Authorization: Bearer TU_CLAVE_API" \
  -F "dni=01200053" \
  -F "tipo_solicitud_id=1" \
  -F "vinculo=VINC-2026-001" \
  -F "motivo_solicitud=CREACION" \
  -F "tipo_cuenta=Correo institucional + VPN" \
  -F "sistema_especifico=SIGAVES" \
  -F "adjuntos[]=@constancia.pdf" \
  -F "adjuntos[]=@dni_frente.jpg" \
  -F "observaciones=Solicitud con documentos adjuntos"
```

**Opcion B: Enviar URLs (JSON)**

```bash
curl -X POST "http://localhost:8000/api/ext/tickets" \
  -H "Authorization: Bearer TU_CLAVE_API" \
  -H "Content-Type: application/json" \
  -d '{
    "dni": "01200053",
    "tipo_solicitud_id": 1,
    "vinculo": "VINC-2026-001",
    "motivo_solicitud": "CREACION",
    "tipo_cuenta": "Correo institucional + VPN",
    "sistema_especifico": "SIGAVES",
    "adjuntos_url": [
      "https://drive.google.com/file/d/ABC123/doc.pdf",
      "https://drive.google.com/file/d/DEF456/foto.jpg"
    ],
    "observaciones": "Solicitud con documentos adjuntos"
  }'
```

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `dni` | string | No* | DNI de la persona (8 digitos). Requerido para tipos que no sean SOPORTE TECNICO |
| `tipo_solicitud_id` | integer | Si | ID del tipo de solicitud (consultar con `GET /api/ext/tipo-solicitudes`) |
| `vinculo` | string | No | Vinculo o referencia externa (max 100 caracteres) |
| `motivo_solicitud` | string | Si* | Ver valores por tipo abajo |
| `tipo_cuenta` | string | No* | Tipo de cuenta (ej: "Correo institucional + VPN") |
| `sistema_especifico` | string | No* | Sistema informático (ej: "SIGAVES") |
| `correo_personal` | string | No* | Correo electronico personal (solo para SOLICITUD DE CORREO) |
| `oficina_sopporte` | string | Si* | Nombre de la oficina de soporte (requerido para SOPORTE TECNICO) |
| `dificultad` | string | Si* | Nivel de dificultad: BAJA / MEDIA / ALTA / CRITICA (requerido para SOPORTE TECNICO) |
| `adjuntos` | file[] | No | Archivos adjuntos (max 5, PDF/JPG/PNG, 10MB c/u) |
| `adjuntos_url` | string[] | No | URLs de documentos (max 5, max 5000 chars c/u) |
| `observaciones` | string | Si* | Mensaje o descripcion del problema (requerido para SOPORTE TECNICO) |

> **Nota**: Los campos marcados con * solo aplican segun el tipo de solicitud: `motivo_solicitud`, `tipo_cuenta`, `sistema_especifico` y `usuario_creado` solo para `SOLICITUD DE ALTA Y BAJA`; `correo_personal` y `motivo_solicitud` solo para `SOLICITUD DE CORREO`; `oficina_sopporte`, `dificultad` y `observaciones` son requeridos para `SOPORTE TECNICO` (no requiere DNI). Los campos `codigo`, `persona_id`, `oficina_actual_id`, `estado`, `fecha_solicitud`, `atendido_por` y `fecha_atencion` se generan/asignan automaticamente en el servidor.
>
> **Valores de motivo_solicitud por tipo:**
> - `SOLICITUD DE ALTA Y BAJA`: CREACION / RENOVACION / MODIFICACION / BAJA
> - `SOLICITUD DE CORREO`: CREACION / RESTABLECIMIENTO / ACTIVACION / OTRO
> - `SOPORTE TECNICO`: No usa motivo_solicitud (usa `observaciones`)

**Respuesta exitosa (201):**
```json
{
  "mensaje": "Ticket creado exitosamente",
  "data": {
    "codigo": "TICK-2026-001",
    "vinculo": "VINC-2026-001",
    "tipo_solicitud": {
      "id": 1,
      "nombre": "SOLICITUD DE ALTA Y BAJA",
      "oficina": "Sub Oficina de Gobierno Electronico"
    },
    "estado": "PENDIENTE",
    "motivo_solicitud": "CREACION",
    "tipo_cuenta": "Correo institucional + VPN",
    "sistema_especifico": "SIGAVES",
    "adjuntos": ["TICK-2026-001/1719750000_constancia.pdf", "TICK-2026-001/1719750000_dni_frente.jpg"],
    "observaciones": "Solicitud con documentos adjuntos",
    "fecha_solicitud": "2026-06-30T01:03:40.000000Z"
  }
}
```

**Error de validacion (422):**
```json
{
  "message": "The dni field is required. (and 1 more error)",
  "errors": {
    "dni": ["The dni field is required."],
    "tipo_solicitud_id": ["The tipo solicitud id field is required."]
  }
}
```

#### 4. Consultar ticket por codigo

```bash
curl -X GET "http://localhost:8000/api/ext/tickets/TICK-2026-2027" \
  -H "Authorization: Bearer TU_CLAVE_API"
```

**Respuesta exitosa (200):**
```json
{
  "data": {
    "codigo": "TICK-2026-001",
    "vinculo": "VINC-2026-001",
    "tipo_solicitud": {
      "id": 1,
      "nombre": "CORREO",
      "oficina": "Direccion de Tecnologias de la Informacion"
    },
    "estado": "PENDIENTE",
    "adjuntos": ["TICK-2026-001/1719750000_constancia.pdf", "https://drive.google.com/file/d/DEF456/foto.jpg"],
    "observaciones": "Solicitud con documentos adjuntos",
    "fecha_solicitud": "2026-06-30T01:03:40.000000Z",
    "fecha_atencion": null,
    "persona": {
      "dni": "01200053",
      "nombres": "FRANZ LUDWIG",
      "apellidos": "ALIAGA MONTESINOS"
    }
  }
}
```

**Ticket no encontrado (404):**
```json
{
  "mensaje": "Ticket no encontrado"
}
```

### Ejemplo Completo en PHP

```php
<?php

$ch = curl_init();
$claveApi = 'tu_clave_api_aqui';
$baseUrl = 'http://localhost:8000/api/ext';

// === PASO 1: Consultar tipos de solicitud disponibles ===
curl_setopt_array($ch, [
    CURLOPT_URL => "{$baseUrl}/tipo-solicitudes",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer {$claveApi}"],
]);
$tipos = json_decode(curl_exec($ch), true);
echo "Tipos disponibles:\n";
foreach ($tipos['data'] as $tipo) {
    echo "  [{$tipo['id']}] {$tipo['nombre']} - {$tipo['oficina']}\n";
}

// === PASO 2: Consultar persona por DNI ===
$dni = '01200053';
curl_setopt_array($ch, [
    CURLOPT_URL => "{$baseUrl}/persona/dni/{$dni}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer {$claveApi}"],
]);
$persona = json_decode(curl_exec($ch), true);
if (isset($persona['data'])) {
    echo "\nPersona encontrada: {$persona['data']['nombres']} {$persona['data']['apellidos']}\n";
    echo "Correo: {$persona['data']['correo']}\n";
    echo "Telefono: {$persona['data']['telefono']}\n";
}

// === PASO 3: Crear ticket con archivos ===
$campos = [
    'dni' => $dni,
    'tipo_solicitud_id' => 1,  // SOLICITUD DE ALTA Y BAJA
    'vinculo' => 'REF-2026-001',
    'motivo_solicitud' => 'CREACION',
    'tipo_cuenta' => 'Correo institucional + VPN',
    'sistema_especifico' => 'SIGAVES',
    'observaciones' => 'Solicitud de correo para nuevo empleado. Requiere acceso a bandeja compartida.',
];

// Opcion A: Subir archivos
$archivos = [
    ['nombre' => 'adjuntos[]', 'ruta' => '/ruta/constancia.pdf'],
    ['nombre' => 'adjuntos[]', 'ruta' => '/ruta/dni.jpg'],
];
$postFields = array_merge($campos, array_map(
    fn($a) => [$a['nombre'] => new CURLFile($a['ruta'])],
    $archivos
));

// Opcion B: Enviar URLs (descomentar)
// $campos['adjuntos_url'] = [
//     'https://drive.google.com/file/d/ABC123/doc.pdf',
//     'https://drive.google.com/file/d/DEF456/foto.jpg',
// ];
// $postFields = $campos;

curl_setopt_array($ch, [
    CURLOPT_URL => "{$baseUrl}/tickets",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer {$claveApi}"],
]);
$nuevoTicket = json_decode(curl_exec($ch), true);
if (isset($nuevoTicket['data'])) {
    $codigo = $nuevoTicket['data']['codigo'];
    echo "\nTicket creado: {$codigo}\n";
    echo "Tipo: {$nuevoTicket['data']['tipo_solicitud']['nombre']}\n";
    echo "Oficina: {$nuevoTicket['data']['tipo_solicitud']['oficina']}\n";

    // === PASO 4: Consultar el ticket creado ===
    curl_setopt_array($ch, [
        CURLOPT_URL => "{$baseUrl}/tickets/{$codigo}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$claveApi}"],
    ]);
    $detalle = json_decode(curl_exec($ch), true);
    echo "\nDetalle del ticket:\n";
    echo "  Estado: {$detalle['data']['estado']}\n";
    echo "  Persona: {$detalle['data']['persona']['nombres']} {$detalle['data']['persona']['apellidos']}\n";
}

curl_close($ch);
?>
```

### Ejemplo Completo en Python

```python
import requests

BASE_URL = 'http://localhost:8000/api/ext'
API_KEY = 'tu_clave_api_aqui'
HEADERS = {'Authorization': f'Bearer {API_KEY}'}

# 1. Consultar tipos de solicitud
tipos = requests.get(f'{BASE_URL}/tipo-solicitudes', headers=HEADERS).json()
print("Tipos disponibles:")
for t in tipos['data']:
    print(f"  [{t['id']}] {t['nombre']} - {t['oficina']}")

# 2. Consultar persona por DNI
dni = '01200053'
persona = requests.get(f'{BASE_URL}/persona/dni/{dni}', headers=HEADERS).json()
if 'data' in persona:
    print(f"\nPersona: {persona['data']['nombres']} {persona['data']['apellidos']}")
    print(f"Correo: {persona['data']['correo']}")

# 3. Crear ticket con archivos
campos = {
    'dni': dni,
    'tipo_solicitud_id': 1,  # SOLICITUD DE ALTA Y BAJA
    'vinculo': 'REF-2026-001',
    'motivo_solicitud': 'CREACION',
    'tipo_cuenta': 'Correo institucional + VPN',
    'sistema_especifico': 'SIGAVES',
    'observaciones': 'Solicitud de correo para nuevo empleado. Requiere acceso a bandeja compartida.',
}

# Opcion A: Subir archivos
archivos = [
    ('adjuntos[]', ('constancia.pdf', open('constancia.pdf', 'rb'), 'application/pdf')),
    ('adjuntos[]', ('dni.jpg', open('dni.jpg', 'rb'), 'image/jpeg')),
]
nuevo = requests.post(f'{BASE_URL}/tickets', headers=HEADERS, data=campos, files=archivos).json()

# Opcion B: Enviar URLs (descomentar)
# campos['adjuntos_url'] = [
#     'https://drive.google.com/file/d/ABC123/doc.pdf',
#     'https://drive.google.com/file/d/DEF456/foto.jpg',
# ]
# nuevo = requests.post(f'{BASE_URL}/tickets', headers=HEADERS, json=campos).json()
if 'data' in nuevo:
    codigo = nuevo['data']['codigo']
    print(f"\nTicket creado: {codigo}")
    print(f"Tipo: {nuevo['data']['tipo_solicitud']['nombre']}")

    # 4. Consultar ticket creado
    detalle = requests.get(f'{BASE_URL}/tickets/{codigo}', headers=HEADERS).json()
    print(f"Estado: {detalle['data']['estado']}")
    print(f"Persona: {detalle['data']['persona']['nombres']} {detalle['data']['persona']['apellidos']}")
```

### Codigos de Respuesta

| Codigo | Significado |
|--------|-------------|
| 200 | Operacion exitosa |
| 201 | Ticket creado exitosamente |
| 401 | Clave API invalida o no proporcionada |
| 403 | Clave API inactiva, expirada o sin permisos |
| 404 | Recurso no encontrado (DNI o codigo inexistente) |
| 422 | Error de validacion (datos incompletos o invalidos) |
| 429 | Limite de solicitudes excedido (rate limit) |

### Rate Limiting

Cada clave API tiene un limite configurable de **600 solicitudes por minuto** (por defecto). Si se excede, se retorna codigo `429`.

### Permisos por Clave

Al crear una clave API, se asignan los permisos que tendra:

| Permiso | Descripcion |
|---------|-------------|
| `tickets_crear` | Puede crear tickets nuevos |
| `tickets_consultar` | Puede consultar tickets por codigo |
| `dni_consultar` | Puede consultar datos de personas por DNI |
| `tipos_solicitud_consultar` | Puede listar tipos de solicitud activos |

### Seguridad de Claves

- La clave API **solo se muestra una vez** al crearla o regenerarla
- Despues de cerrar el modal, solo se muestra el prefijo (`****abcd`)
- Se almacena como hash SHA-256, nunca en texto plano
- Cada clave tiene un tiempo de vida configurable (30 dias, 90 dias, 1 ano, sin expiracion)

---

## Seguridad

- **Autenticacion**: Basada en localStorage (sin tokens JWT/Sanctum)
- **Rate Limiting**: Login 5/min, API 60/min, publico 30/min
- **Bloqueo de cuenta**: 5 intentos fallidos → 15 min bloqueado
- **Headers de seguridad**: HSTS, X-Frame-Options DENY, X-Content-Type-Options nosniff
- **CORS**: Solo permite `http://localhost:5173`
- **Rate limiting**: Aplicado via middleware `throttle`

---

## Configuracion SMTP (Envio de Emails)

El sistema envia correos automaticamente al crear tickets via API externa. Configura las siguientes variables en `backend/.env`:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_usuario@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_FROM_ADDRESS="pruebas14@unap.edu.pe"
MAIL_FROM_NAME="OTI - Oficina de Tecnologias"
```

> **Nota**: Para Gmail, genera una **App Password** en https://myaccount.google.com/apppasswords (requiere 2FA activado).

### Dependencias Backend (PDF)

- `barryvdh/laravel-dompdf` v3.1 — Genera PDFs en memoria desde plantillas Blade
- El PDF se genera en memoria (ByteArrayOutputStream) y se adjunta al correo, nunca se guarda en disco

### Flujo de Email

```
POST /api/ext/tickets → crearTicket()
  ├── Solicitud::create()
  └── SendSolicitudEmailJob::dispatch($solicitudId)
        └── SolicitudTicketMail
              ├── Blade template (fut-solicitud.blade.php)
              ├── DomPDF → PDF en memoria
              └── Mail::to($correo_personal)->send()
```

---

## Diseno del Fotocheck

### Anverso
- Header blanco con logo universidad y nombre
- Strip azul derecho
- Foto del trabajador centrada
- Nombre en azul, cargo en gris
- Footer gris con icono NFC y codigo

### Reverso
- Header azul "DATOS COMPLEMENTARIOS"
- Seccion Contacto: Email y telefono del trabajador
- Seccion Informacion Laboral: Regimen, Dependencia, Cargo, Fecha de Ingreso
- Firma autorizada con imagen
- Footer: "Propiedad de la Universidad Nacional del Altiplano"

---

## Notas Tecnicas

- **Sin TypeScript**: El frontend usa JSX puro
- **Nombres en espanol**: Tablas, columnas y mensajes en espanol
- **URLs configurables**: Todas las URLs se definen en `.env`
- **Photos via proxy**: Las imagenes de Google Drive se sirven through el backend
- **codigo_unico**: Codigo hex de 8 caracteres por trabajador para URLs publicas (privacidad sobre DNI)
- **Estados de fotocheck**: Solo se verifica `VIGENTE` en la vista publica

---

## Autor

**Ivan Rony Condori Inquilla**
Universidad Nacional del Altiplano - Puno
(051) 363-282 | rrhh@unap.edu.pe
zzzzzzzzzzzzzzzzzzz#   P r o y e c t o _ f o t o c h e c k  
 