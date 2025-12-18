#!/bin/bash

# ==================== LIMPIEZA ====================
echo "=== BORRANDO SITIO ACTUAL ==="
sudo rm -rf /var/www/html/*
sudo rm -rf /tmp/888walle

# ==================== ACTUALIZAR SISTEMA ====================
echo "=== ACTUALIZANDO SISTEMA ==="
sudo apt update -y
sudo apt upgrade -y

# ==================== INSTALAR APACHE ====================
echo "=== INSTALANDO APACHE ==="
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

# ==================== INSTALAR PHP ====================
echo "=== INSTALANDO PHP Y MODULOS ==="
sudo apt install php libapache2-mod-php php-cli php-common php-mysql php-zip php-gd php-mbstring php-curl php-xml php-bcmath -y

# ==================== ACTIVAR MODULO PHP ====================
echo "=== ACTIVANDO MODULO PHP ==="
sudo a2enmod php8.3
sudo systemctl restart apache2

# ==================== INSTALAR GIT ====================
echo "=== INSTALANDO GIT ==="
sudo apt install git -y

# ==================== CLONAR REPO 888WALLE ====================
echo "=== DESCARGANDO TU REPO DE GITHUB ==="
cd /tmp
git clone https://github.com/eduardzsosa72/888walle.git

# ==================== MOVER AL WEB ROOT ====================
echo "=== MOVER ARCHIVOS AL SERVIDOR APACHE ==="
sudo cp -r 888walle/* /var/www/html/

# ==================== VERIFICAR QUE index.php EXISTA ====================
echo "=== VERIFICANDO ARCHIVOS ==="
if [ -f "/var/www/html/index.php" ]; then
    echo "✅ index.php encontrado y listo"
    
    # Opcional: Si también existe index.html, renombrarlo para evitar conflicto
    if [ -f "/var/www/html/index.html" ]; then
        echo "⚠️  index.html también existe, renombrando a index.html.backup"
        sudo mv /var/www/html/index.html /var/www/html/index.html.backup
    fi
else
    echo "❌ ERROR: index.php NO encontrado en el repositorio"
    echo "Asegúrate de que index.php esté en tu repositorio de GitHub"
    exit 1
fi

# ==================== CREAR .HTACCESS SEGURO ====================
echo "=== CREANDO .HTACCESS SEGURO ==="
sudo tee /var/www/html/.htaccess > /dev/null <<EOL
# =============================
# PÁGINA PRINCIPAL (PHP PRIMERO)
# =============================
DirectoryIndex index.php index.html

# =============================
# BLOQUEO DE LISTADO DE DIRECTORIOS
# =============================
Options -Indexes

# =============================
# BLOQUEAR EJECUCIÓN DE SCRIPTS .SH
# =============================
<Files ~ "\.sh$">
    Require all denied
</Files>

# =============================
# BLOQUEAR ACCESO A ARCHIVOS .TXT
# =============================
<Files ~ "\.txt$">
    Require all denied
</Files>

# =============================
# CONFIGURAR ERRORES PHP
# =============================
php_flag display_errors Off
php_flag log_errors On
php_value error_log /var/www/html/php_error.log

# =============================
# REDIRECCIÓN DE ERRORES
# =============================
ErrorDocument 503 /index.php
ErrorDocument 500 /index.php
ErrorDocument 404 /index.php

# =============================
# PROTECCIÓN GENERAL
# =============================
<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql)$">
    Require all denied
</FilesMatch>
EOL

# ==================== AJUSTAR PERMISOS ====================
echo "=== AJUSTANDO PERMISOS ==="
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo find /var/www/html -type d -exec chmod 755 {} \;

# ==================== REINICIAR APACHE ====================
echo "=== REINICIAR APACHE ==="
sudo systemctl restart apache2

# ==================== CREAR TEST PHP ====================
echo "=== CREANDO TEST PHP ==="
echo "<?php 
echo '<h3>PHP funciona correctamente 🎉</h3>';
echo '<p>Fecha: ' . date('Y-m-d H:i:s') . '</p>';
echo '<p>PHP Version: ' . phpversion() . '</p>';
echo '<p><a href=\"/\">Ir a 888Wallet</a></p>';
?>" | sudo tee /var/www/html/test.php

# ==================== MOSTRAR IP DEL SERVIDOR ====================
IP_LOCAL=$(hostname -I | awk '{print $1}')
IP_PUBLICA=$(curl -s ifconfig.me)

echo ""
echo "================================================"
echo "🚀 INSTALACIÓN COMPLETADA"
echo "================================================"
echo "🌐 Sitio disponible en IP local:  http://$IP_LOCAL"
echo "🌍 Sitio disponible en IP pública: http://$IP_PUBLICA"
echo "📊 Test PHP: http://$IP_LOCAL/test.php"
echo ""
echo "📁 Archivos principales en /var/www/html/:"
ls -la /var/www/html/*.php /var/www/html/*.html 2>/dev/null || echo "No hay archivos .php o .html"
echo "================================================"

echo "=== LISTO!! ==="