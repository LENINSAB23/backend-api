<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Paso a paso de ejecución integrada:
Activación de Laragon (Apache, MySQL, PHP 8.2+).
Puesta en marcha de backend-api (php artisan migrate --seed y php artisan serve --host=0.0.0.0 --port=8000).
Ejecución de frontend_web (npm run dev en http://localhost:5173/).
Ejecución de mobile_app mediante Depuración por USB:
Configuración de opciones de desarrollador en Android.
Comando clave para redirigir el puerto al teléfono sin depender del Wi-Fi:
powershell
adb reverse tcp:8000 tcp:8000
Compilación y despliegue con flutter run.
Credenciales preconfiguradas de prueba (Cliente, Repartidor y Administrador).

##