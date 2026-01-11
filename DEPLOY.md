# Guia de Despliegue - PulseConnector Website

## Requisitos del Servidor

- Apache 2.4+
- PHP 7.4+ (recomendado PHP 8.x)
- mod_rewrite habilitado

## Pasos para Desplegar

### 1. Subir archivos al servidor

Copia todos los archivos al directorio web del servidor (ej: `/var/www/html/pulseconnector`).

```bash
scp -r * usuario@tu-servidor:/var/www/html/pulseconnector/
```

### 2. Configurar credenciales SMTP

Copia el archivo de ejemplo y edita con tus credenciales:

```bash
cp .env.example .env
nano .env
```

Contenido del `.env`:
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu_correo@gmail.com
SMTP_PASSWORD=tu_app_password
CONTACT_EMAIL=correo_destino@ejemplo.com
SITE_NAME=PulseConnector
```

**Para obtener App Password de Gmail:**
1. Ve a https://myaccount.google.com/apppasswords
2. Genera una contrasena para "Correo"
3. Copia los 16 caracteres al .env

### 3. Habilitar mod_rewrite en Apache

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 4. Configurar VirtualHost

Edita la configuracion de Apache:

```bash
sudo nano /etc/apache2/sites-available/pulseconnector.conf
```

Contenido:
```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /var/www/html/pulseconnector

    <Directory /var/www/html/pulseconnector>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Habilitar el sitio:
```bash
sudo a2ensite pulseconnector.conf
sudo systemctl reload apache2
```

### 5. Permisos de archivos

```bash
sudo chown -R www-data:www-data /var/www/html/pulseconnector
sudo chmod 600 /var/www/html/pulseconnector/.env
```

### 6. Verificar seguridad

Intenta acceder a estos archivos desde el navegador (deben dar error 403):
- https://tu-dominio.com/.env
- https://tu-dominio.com/PHPMailer/

### 7. Probar formulario de contacto

1. Abre el sitio en el navegador
2. Ve a la seccion de contacto
3. Envia un mensaje de prueba
4. Verifica que llegue al correo configurado

## Estructura de Archivos

```
/var/www/html/pulseconnector/
├── .env                 # Credenciales (NO compartir)
├── .env.example         # Plantilla de ejemplo
├── .htaccess            # Proteccion de archivos
├── index.html           # Pagina principal
├── documentation.html   # Documentacion
├── send-email.php       # Backend del formulario
├── PHPMailer/           # Libreria de correo
└── assets/              # CSS, JS, imagenes
```

## Solucion de Problemas

### El formulario no envia correos
- Verifica las credenciales en `.env`
- Revisa los logs: `tail -f /var/log/apache2/error.log`

### Error 403 en el sitio
- Verifica permisos: `ls -la /var/www/html/pulseconnector`
- Verifica AllowOverride en VirtualHost

### .htaccess no funciona
- Asegurate que mod_rewrite esta habilitado
- Verifica que AllowOverride esta en All
