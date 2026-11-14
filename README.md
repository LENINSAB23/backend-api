# Manual de Puesta en Marcha y Despliegue del Sistema Multiplataforma
## Proyecto de Tesis: Plataforma Integral de Comercio Electrónico, Telemetría Satelital y Asistencia IA
### Empresa Beneficiaria: **Grupo Rino E.I.R.L. (Huánuco, Perú)**

---

## 1. Requisitos Previos del Entorno de Desarrollo

Para la correcta ejecución coordinada de todo el ecosistema (Backend API, Frontend Web y Aplicación Móvil), el entorno del desarrollador debe contar con:

1. **Servidor Local Laragon** (o XAMPP/WAMP):
   - **PHP 8.2+** con extensiones activas: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`.
   - **MySQL 8.0+** o MariaDB 10.4+.
   - **Composer** (gestor de dependencias de PHP).
2. **Node.js**: Versión 18.x o 20.x LTS y gestor de paquetes **npm**.
3. **Flutter SDK**: Versión 3.x con canal `stable` y Dart 3.x configurado en el `PATH` del sistema.
4. **Android SDK & Platform Tools**:
   - `adb` (Android Debug Bridge) accesible desde la terminal.
   - Dispositivo físico Android conectado mediante cable USB con **Opciones de Desarrollador** y **Depuración por USB** activadas.
5. **Git**: Para el control de versiones y sincronización de repositorios.

---

## 2. Paso a Paso de Puesta en Marcha

### Paso 1: Configurar e Iniciar la Base de Datos con Laragon
1. Abra la interfaz de **Laragon** y presione el botón **"Iniciar Todo"** (*Start All*) para levantar Apache/Nginx y el servidor MySQL.
2. Abra el gestor de base de datos integrado (HeidiSQL o phpMyAdmin) o su terminal de MySQL.
3. Cree la base de datos del proyecto si aún no existe:
   ```sql
   CREATE DATABASE IF NOT EXISTS `tessis_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Verifique que las credenciales de conexión en el archivo `.env` de `backend-api` coincidan:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tessis_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

---

### Paso 2: Inicialización del Servidor Backend (`backend-api`)
Abra una terminal de PowerShell en la carpeta raíz del backend:
```powershell
cd C:\Users\LENIN\Documents\TESSIS\Proyecto\backend-api
```

1. **Instalar dependencias de Composer** (si es la primera vez):
   ```powershell
   composer install
   ```

2. **Generar la clave criptográfica de la aplicación**:
   ```powershell
   php artisan key:generate
   ```

3. **Ejecutar migraciones y poblar la base de datos (Seeding)**:
   ```powershell
   php artisan migrate --seed
   ```
   > **Nota:** Esto registrará automáticamente los usuarios de prueba, el catálogo oficial de repuestos automotrices, las categorías con compatibilidad vehicular (Autos, Motocicletas y Universal) y los pedidos iniciales.

4. **Crear el enlace simbólico para archivos multimedia**:
   ```powershell
   php artisan storage:link
   ```

5. **Iniciar el servidor HTTP de Laravel**:
   Ejecútelo escuchando en `0.0.0.0` para admitir solicitudes locales y del túnel ADB del dispositivo móvil:
   ```powershell
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   *El servidor quedará operativo en `http://127.0.0.1:8000/` y en `http://0.0.0.0:8000/`.*

---

### Paso 3: Inicialización del Portal Web (`frontend_web`)
Abra una segunda terminal de PowerShell:
```powershell
cd C:\Users\LENIN\Documents\TESSIS\Proyecto\frontend_web
```

1. **Instalar dependencias de Node.js**:
   ```powershell
   npm install
   ```

2. **Iniciar el servidor de desarrollo Vite**:
   ```powershell
   npm run dev
   ```
3. Abra su navegador web en la dirección indicada por Vite:
   ```text
   http://localhost:5173/
   ```

> **Diagnóstico 502 Bad Gateway en Web:**
> El archivo `vite.config.js` implementa un proxy inverso hacia `http://127.0.0.1:8000`. Si el navegador muestra error *502 Bad Gateway* o *ECONNREFUSED* en `/api/products`, significa que el comando `php artisan serve` de Laravel (Paso 2) aún no ha sido iniciado o fue detenido. Una vez iniciado el backend, las peticiones responderán inmediatamente con código 200 OK.

---

### Paso 4: Inicialización de la Aplicación Móvil (`mobile_app`) mediante Depuración USB

Para garantizar estabilidad absoluta de conexión durante pruebas de campo en Huánuco sin depender de la misma red Wi-Fi ni de direcciones IP locales cambiantes, se utiliza la **redirección de puertos por cable USB mediante ADB**:

1. **Conectar el dispositivo móvil Android por USB**:
   - Conecte el teléfono a su computadora mediante cable de datos de alta velocidad.
   - En el teléfono, confirme el diálogo de seguridad: *"¿Permitir depuración por USB?"* marcando la casilla de recordar.

2. **Verificar reconocimiento del dispositivo**:
   Abra una tercera terminal de PowerShell:
   ```powershell
   cd C:\Users\LENIN\Documents\TESSIS\Proyecto\mobile_app
   adb devices
   ```
   *Debe listar su dispositivo con estado `device` (por ejemplo: `9610eaba device`).*

3. **Ejecutar el comando de túnel inverso TCP (Fundamental)**:
   ```powershell
   adb reverse tcp:8000 tcp:8000
   ```
   > **Explicación técnica:** Este comando mapea el puerto 8000 del teléfono directamente al puerto 8000 de su computadora a través del cable USB. Gracias a esto, la aplicación Flutter puede comunicarse con la API de Laravel en `http://localhost:8000` con cero latencia y sin bloqueos de firewall de Windows.

4. **Compilar y desplegar la aplicación**:
   ```powershell
   flutter run -d <id_del_dispositivo>
   ```
   *Por ejemplo:*
   ```powershell
   flutter run -d 9610eaba
   ```

---

## 3. Credenciales de Acceso para Pruebas y Evaluación

| Rol | Correo Electrónico | Contraseña | Funcionalidad en el Sistema |
| :--- | :--- | :--- | :--- |
| **Cliente** | `cliente@rino.pe` | `password` | Exploración de catálogo, compatibilidad de repuestos para su vehículo registrado, carrito de compras, solicitud de pedidos y rastreo de entrega en tiempo real por GPS. |
| **Repartidor** | `repartidor@rino.pe` | `password` | Tablero de pedidos asignados, cambio de estados (en camino, entregado), transmisión de telemetría de ubicación continua por GPS hacia el servidor. |
| **Administrador** | `admin@rino.pe` | `password` | Control de stock, gestión integral de órdenes de compra y supervisión de rutas. |

---

## 4. Características de Diseño y Arquitectura

- **Minimalismo Visual Estricto**: Paleta neutra/monocromática de alto contraste, tipografía limpia y sin emojis en pantallas, diálogos o registros técnicos para cumplir con los estándares de rigor de una tesis de ingeniería.
- **Selector de Modo Claro y Oscuro**: Disponible tanto en la cabecera del portal web como en la barra superior y pestaña de perfil de la app móvil, con persistencia automática de las preferencias del usuario (`localStorage` en Web y `SharedPreferences` en Móvil).
- **Asistencia Inteligente RinoBot**: Motor conversacional con la API de Google Gemini restringido exclusivamente a asesoría técnica de repuestos automotrices, aceites homologados y seguimiento logístico en Huánuco.
- **Tolerancia a Fallos Offline**: Tanto la web como la app móvil cuentan con almacenamiento en caché local del catálogo oficial de repuestos para permitir la navegación fluida incluso ante caídas temporales de red.
