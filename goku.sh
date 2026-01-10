#!/bin/bash

# ==================== LIMPIEZA ====================
echo "=== BORRANDO SITIO ACTUAL ==="
sudo rm -rf /var/www/html/*
sudo rm -rf /tmp/888walle

# ==================== ACTUALIZAR SISTEMA ====================
echo "=== ACTUALIZANDO SISTEMA ==="
sudo yum update -y

# ==================== INSTALAR APACHE ====================
echo "=== INSTALANDO APACHE ==="
sudo yum install httpd -y
sudo systemctl enable httpd
sudo systemctl start httpd

# ==================== INSTALAR PHP ====================
echo "=== INSTALANDO PHP Y MODULOS ==="
sudo yum install php php-cli php-common php-mysqlnd php-zip php-gd php-mbstring php-curl php-xml -y

# ==================== REINICIAR APACHE ====================
echo "=== REINICIANDO APACHE ==="
sudo systemctl restart httpd

# ==================== INSTALAR GIT ====================
echo "=== INSTALANDO GIT ==="
sudo yum install git -y

# ==================== CLONAR REPO 888WALLE ====================
echo "=== DESCARGANDO TU REPO DE GITHUB ==="
cd /tmp || exit 1
git clone https://github.com/eduardzsosa72/888walle.git

# ==================== MOVER AL WEB ROOT ====================
echo "=== MOVER ARCHIVOS AL SERVIDOR APACHE ==="
sudo cp -r /tmp/888walle/* /var/www/html/

# ==================== VERIFICAR QUE index.php EXISTA ====================
echo "=== VERIFICANDO ARCHIVOS ==="
if [ -f "/var/www/html/index.php" ]; then
    echo "✅ index.php encontrado y listo"
else
    echo "❌ ERROR: index.php NO encontrado en el repositorio"
    exit 1
fi

# ==================== CREAR .HTACCESS SEGURO ====================
echo "=== CREANDO .HTACCESS SEGURO ==="
sudo tee /var/www/html/.htaccess > /dev/null <<'EOL'
DirectoryIndex index.php index.html
Options -Indexes

<Files ~ "\.sh$">
    Require all denied
</Files>

<Files ~ "\.txt$">
    Require all denied
</Files>

<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql)$">
    Require all denied
</FilesMatch>

ErrorDocument 403 /index.php
ErrorDocument 404 /index.php
ErrorDocument 500 /index.php
EOL

# ==================== AJUSTAR PERMISOS ====================
echo "=== AJUSTANDO PERMISOS ==="
sudo chown -R apache:apache /var/www/html
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo find /var/www/html -type d -exec chmod 755 {} \;

# ==================== REINICIAR APACHE ====================
echo "=== REINICIAR APACHE ==="
sudo systemctl restart httpd

# ==================== CREAR TEST PHP ====================
echo "=== CREANDO TEST PHP ==="
sudo tee /var/www/html/test.php > /dev/null <<'EOF'
<?php
echo "<h3>PHP funciona correctamente 🎉</h3>";
echo "<p>Fecha: " . date("Y-m-d H:i:s") . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p><a href='/'>Ir a 888Wallet</a></p>";
?>
EOF

# ==================== MOSTRAR IP ====================
IP_LOCAL=$(hostname -I | awk '{print $1}')
IP_PUBLICA=$(curl -s ifconfig.me)

echo ""
echo "================================================"
echo "🚀 INSTALACIÓN COMPLETADA"
echo "================================================"
echo "🌐 Local:   http://$IP_LOCAL"
echo "🌍 Pública: http://$IP_PUBLICA"
echo "🧪 Test:    http://$IP_PUBLICA/test.php"
echo "================================================"
echo "=== LISTO!! ==="