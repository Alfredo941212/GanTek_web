# Ubicaciones de Fincas

## Arquitectura

INEGI → comando Artisan → base de datos local → API de GanTek → formulario Blade / clientes móviles.

Fuente oficial: https://www.inegi.org.mx/servicios/catalogounico.html

Se comprobaron respuestas reales de `mgee/`, `mgem/01` y `localidades/010010001`. El importador consulta `mgee/`, `mgem/{estado}` y `localidades/{municipio}` bajo `https://gaia.inegi.org.mx/wscatgeo/v2/`. Lee `datos`, `cvegeo`, `nomgeo` y las claves de cada nivel; verifica `numReg` cuando está presente. Las claves completas son cadenas de 2, 5 y 9 dígitos, respectivamente. Nunca convertirlas a números.

Las tablas `estados`, `municipios` y `localidades` tienen claves únicas y referencias a sus padres. Los índices por padre y nombre permiten consultas ordenadas. `fincas` incorpora tres referencias opcionales con eliminación restringida. Se mantienen las columnas de texto, sus valores históricos y todos los registros existentes. No se asignan claves por coincidencias ambiguas de nombres. Al editar una finca histórica se muestra su ubicación anterior y se exige confirmar una ubicación del catálogo. Las nuevas escrituras guardan referencias y nombres oficiales obtenidos por Laravel.

## Puesta en marcha

Usar PHP 8.4.1 o superior. En esta máquina se verificó con `C:/Users/jhoha/.config/herd/bin/php84/php.exe`; el `php` predeterminado del terminal era 8.2.12.

```powershell
php artisan migrate --no-interaction
php artisan ubicaciones:sincronizar --estado=07 --no-interaction
# Importación nacional (incluye los 32 estados, municipios y localidades):
php artisan ubicaciones:sincronizar --no-interaction
```

También se puede repetir `--estado`, por ejemplo `--estado=07 --estado=30`. La importación es idempotente y usa un bloqueo de caché para evitar ejecuciones concurrentes. Requiere HTTPS saliente al INEGI y un almacén de caché compartido entre procesos para que el bloqueo sea efectivo.

Cada estado se descarga y valida por completo antes de iniciar su transacción. Se agregan registros y actualizan nombres conservando IDs; nunca se eliminan filas ausentes de una respuesta. Un fallo devuelve código de salida 1 y conserva el estado afectado sin cambios. Los estados que ya finalizaron permanecen importados. Repetir el comando para reanudar. Respuestas vacías, duplicadas, con claves de otro padre, nombres inválidos o conteos inconsistentes cancelan el estado. Cada solicitud tiene tiempos límite y hasta tres intentos. La importación nacional puede tardar por el volumen de localidades y solicitudes.

Los catálogos se consultan siempre desde la base local. No hay llamadas al INEGI durante el registro o la edición de una finca. Hasta importar, el formulario informa que los catálogos no están disponibles. La implementación no ejecutó migraciones ni importaciones sobre la base habitual.

## API

Los catálogos son públicos, de solo lectura, con límite compartido de 60 solicitudes por minuto por IP. Fincas conserva su autenticación y autorización actuales: sesión en web y Sanctum en API; solo el propietario puede editar.

| Método y ruta | Resultado |
| --- | --- |
| `GET /api/ubicaciones/estados` | Estados ordenados por nombre |
| `GET /api/ubicaciones/estados/01/municipios` | Municipios del estado 01 |
| `GET /api/ubicaciones/municipios/01001/localidades?buscar=aguas&page=1&por_pagina=25` | Búsqueda parcial y paginación dentro del municipio |

`por_pagina`: 1–100, predeterminado 25. `page`: 1–10000. `buscar`: texto de hasta 100 caracteres. `seleccionada=010010001` permite recuperar una selección concreta, siempre dentro del municipio, para restaurar el formulario sin cargar todo el catálogo.

Estados y municipios devuelven `{"data": [...]}`. Ejemplo ilustrativo después de importar (los IDs locales pueden variar):

```json
{"data":[{"id":1,"clave_inegi":"01","nombre":"Aguascalientes"}]}
```

Localidades usa el paginador JSON de Laravel: `data`, `current_page`, `per_page`, `total`, `last_page`, `next_page_url`, `prev_page_url` y demás enlaces. Ejemplo abreviado:

```json
{
  "data": [{"id":1,"municipio_id":1,"clave_inegi":"010010001","nombre":"Aguascalientes"}],
  "current_page":1,
  "per_page":25,
  "total":1,
  "last_page":1,
  "next_page_url":null,
  "prev_page_url":null
}
```

Padres inexistentes o rutas con claves mal formadas: 404. Parámetros de consulta inválidos: 422 con `message` y `errors`. Exceso de solicitudes: 429.

### Escrituras de Fincas y Flutter

**Cambio del contrato de escritura:** altas y ediciones requieren `estado_clave` y `municipio_clave`; `localidad_clave` es opcional. Los clientes anteriores que solo enviaban nombres recibirán 422. Flutter no fue modificado; debe adaptarse antes de publicar conjuntamente este cambio. Los listados y consultas de fincas mantienen los nombres y agregan las referencias locales.

Ejemplo para `POST /api/fincas` o `PUT /api/fincas/{id}`, con un token Sanctum y `Accept: application/json`:

```json
{
  "nombre":"Mi finca",
  "estado_clave":"01",
  "municipio_clave":"01001",
  "localidad_clave":"010010001",
  "superficie":"25.50"
}
```

Puede omitirse `localidad_clave` o enviarse `null`; esto también elimina una localidad previa al actualizar. Las claves deben existir y pertenecer al padre indicado. Laravel obtiene los nombres e IDs del catálogo; los nombres e IDs enviados por el cliente no sustituyen esa resolución. `user_id` continúa prohibido. Creación exitosa: 201 con `message` y `data`; actualización: 200 con los mismos campos.

Ejemplo de validación (422):

```json
{
  "message":"El municipio seleccionado no pertenece al estado indicado.",
  "errors":{"municipio_clave":["El municipio seleccionado no pertenece al estado indicado."]}
}
```

Blade conserva la redirección con errores y los valores `old()`. El formulario usa selectores dependientes, búsqueda con espera breve, paginación y reintentos; descarta respuestas de solicitudes anteriores cuando cambia la selección. No requiere compilar Vite porque el JavaScript específico se sirve desde `public/js` siguiendo el patrón existente.

## Archivos

Creado:
- `database/migrations/2026_10_20_121413_create_ubicaciones_tables.php`
- `app/Console/Commands/SincronizarUbicaciones.php`
- `app/Http/Controllers/Api/UbicacionController.php`
- `resources/views/fincas/_ubicacion.blade.php`
- `public/js/fincas-ubicacion.js`
- `tests/Feature/UbicacionesTest.php`
- `UBICACIONES.md`

Modificado:
- `app/Http/Requests/FincaRequest.php`: pertenencia geográfica y resolución de nombres/IDs.
- `app/Models/Finca.php`: referencias admitidas y recuperación de claves para edición.
- `app/Http/Controllers/FincaController.php` y `app/Http/Controllers/Api/FincaController.php`: usan los datos resueltos.
- `routes/api.php`: consultas del catálogo.
- `resources/views/fincas/_form.blade.php`: integración de los selectores.
- `database/factories/FincaFactory.php`: estado de catálogo ficticio exclusivamente para pruebas.
- `tests/Feature/ApiFincaTest.php` y `tests/Feature/GanaderiaWorkflowTest.php`: payloads válidos con claves.
- `tests/TestCase.php`: permite delimitar las migraciones por clase de prueba, conservando la protección SQLite en memoria.

## Verificación y límites

```powershell
php artisan test --compact tests/Feature/UbicacionesTest.php tests/Feature/ApiFincaTest.php
php artisan test --compact tests/Feature/GanaderiaWorkflowTest.php
php vendor/bin/pint --dirty --format agent
node --check public/js/fincas-ubicacion.js
```

Resultado comprobado: **22 pruebas y 219 aserciones correctas** para Ubicaciones y API de Fincas. Incluye filtros y paginación, claves manipuladas, padres ajenos, creación/edición, localidad opcional, datos antiguos, conservación de entrada Blade, permisos existentes de Fincas, sincronización idempotente, INEGI no disponible, respuesta inconsistente y límite de solicitudes. Las respuestas HTTP de importación se simulan y sus datos se identifican como pruebas; no se importan catálogos ficticios en producción.

La prueba general `GanaderiaWorkflowTest` está bloqueada antes de sus aserciones por la migración previa `2026_10_09_213421_agregar_estado_vencida_a_tareas.php`, que contiene SQL `MODIFY ENUM` incompatible con SQLite. Las pruebas de Fincas y Ubicaciones utilizan únicamente sus migraciones necesarias. No se modificó el módulo de Tareas para resolverlo.

Pendientes operativos: ejecutar migraciones/importación en el entorno de destino, comprobar navegación y apariencia en navegador/móvil, adaptar los payloads de Flutter y elegir la frecuencia de sincronización. No se verificó la migración contra un servidor MySQL ni la descarga nacional completa. Se conservan entradas locales ausentes de futuras respuestas del INEGI; no se implementa retirada automática por cambios de vigencia. Los nombres históricos de fincas no se reescriben al sincronizar catálogos. Revisar esas diferencias requiere una política explícita de conciliación. No se cambiaron dependencias ni se realizaron commits, push o merges.
