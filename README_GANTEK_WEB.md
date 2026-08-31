# GanTek Web - Starter

Este paquete contiene los archivos principales para montar GanTek Web sobre un proyecto Laravel limpio.

## 1. Crear proyecto
```bash
composer create-project laravel/laravel gantek-web
cd gantek-web
```

## 2. Copiar los archivos
Copia el contenido de este paquete sobre la raíz del proyecto Laravel.

## 3. Configurar MySQL en `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gantek_web
DB_USERNAME=root
DB_PASSWORD=
```

## 4. Crear base de datos
```sql
CREATE DATABASE gantek_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 5. Ejecutar migraciones y usuario inicial
```bash
php artisan migrate
php artisan db:seed
```

Usuario inicial:
- Correo: admin@gantek.com
- Contraseña: GanTek1234

Cambia esta contraseña antes de una entrega real.

## 6. Ejecutar
```bash
php artisan serve
```

Abrir:
http://127.0.0.1:8000

## Módulos incluidos
- Login / logout
- Dashboard
- CRUD de ganado
- Vacunación
- Ventas
- Reportes
