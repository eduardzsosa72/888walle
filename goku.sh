#!/bin/bash
# =====================================================
# SCRIPT DE REINSTALACIÓN COMPLETA PARA 888Wallet
# =====================================================

echo "================================================"
echo "🚀 REINSTALACIÓN COMPLETA DEL SITIO 888Wallet"
echo "================================================"

# Variables
REPO_URL="https://github.com/eduardzsosa72/888walle.git"
WEB_ROOT="/var/www/html"
TEMP_DIR="/tmp/888walle_reinstall"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# ==================== 1. DETENER SERVICIOS ====================
echo -e "\n${YELLOW}1. DETENIENDO SERVICIOS...${NC}"
sudo systemctl stop apache2 2>/dev/null
sudo systemctl stop nginx 2>/dev/null
print_status "Servicios web detenidos"

# ==================== 2. LIMPIAR INSTALACIONES PREVIAS ====================
echo -e "\n${YELLOW}2. LIMPIANDO INSTALACIONES PREVIAS...${NC}"

# Remover Apache
sudo apt purge apache2 apache2-utils apache2-bin apache2-data -y 2>/dev/null

# Remover PHP
sudo apt purge 'php*' 'libapache2-mod-php*' -y 2>/dev/null

# Remover Nginx si existe
sudo apt purge nginx nginx-common nginx-core -y 2>/dev/null

# Limpiar archivos residuales
sudo rm -rf /etc/apache2 /etc/nginx /var/www/html/* /tmp/888walle*

# Limpiar paquetes no usados
sudo apt autoremove -y
sudo apt autoclean

print_status "Instalaciones previas limpiadas"

# ==================== 3. ACTUALIZAR SISTEMA ====================
echo -e "\n${YELLOW}3. ACTUALIZANDO SISTEMA...${NC}"
sudo apt update -y
sudo apt upgrade -y
print_status "Sistema actualizado"

# ==================== 4. INSTALAR APACHE ====================
echo -e "\n${YELLOW}4. INSTALANDO APACHE2...${NC}"
sudo apt install apache2 -y

# Configurar Apache para que inicie automáticamente
sudo systemctl enable apache2
sudo systemctl start apache2
print_status "Apache2 instalado y ejecutándose"

# ==================== 5. INSTALAR PHP Y MÓDULOS NECESARIOS ====================
echo -e "\n${YELLOW}5. INSTALANDO PHP Y MÓDULOS...${NC}"
sudo apt install php libapache2-mod-php php-cli php-common php-mysql php-zip php-gd \
                 php-mbstring php-curl php-xml php-bcmath php-json -y
print_status "PHP y módulos instalados"

# ==================== 6. CONFIGURAR MÓDULO PHP ====================
echo -e "\n${YELLOW}6. CONFIGURANDO MÓDULO PHP...${NC}"
sudo a2enmod php*
sudo a2enmod rewrite
sudo a2enmod headers
print_status "Módulos PHP habilitados"

# ==================== 7. INSTALAR GIT ====================
echo -e "\n${YELLOW}7. INSTALANDO GIT...${NC}"
sudo apt install git -y
print_status "Git instalado"

# ==================== 8. CLONAR REPOSITORIO ====================
echo -e "\n${YELLOW}8. CLONANDO REPOSITORIO...${NC}"
rm -rf "$TEMP_DIR"
git clone "$REPO_URL" "$TEMP_DIR"

if [ $? -eq 0 ]; then
    print_status "Repositorio clonado exitosamente"
else
    print_error "Error al clonar el repositorio"
    exit 1
fi

# ==================== 9. COPIAR ARCHIVOS AL WEB ROOT ====================
echo -e "\n${YELLOW}9. COPIANDO ARCHIVOS AL SERVIDOR WEB...${NC}"

# Limpiar web root
sudo rm -rf "$WEB_ROOT"/*

# Copiar archivos
sudo cp -r "$TEMP_DIR"/* "$WEB_ROOT"/

# Verificar que se copiaron archivos importantes
if [ -f "$WEB_ROOT/index.html" ]; then
    print_status "Archivos copiados correctamente"
else
    print_warning "index.html no encontrado, verificando estructura..."
    ls -la "$WEB_ROOT/"
fi

# ==================== 10. CONFIGURAR PERMISOS ====================
echo -e "\n${YELLOW}10. CONFIGURANDO PERMISOS...${NC}"
sudo chown -R www-data:www-data "$WEB_ROOT"
sudo find "$WEB_ROOT" -type d -exec chmod 755 {} \;
sudo find "$WEB_ROOT" -type f -exec chmod 644 {} \;

# Dar permisos de ejecución a scripts si existen
if [ -f "$WEB_ROOT/login.php" ]; then
    sudo chmod 644 "$WEB_ROOT/login.php"
fi

print_status "Permisos configurados"

# ==================== 11. CREAR ARCHIVOS DE CONFIGURACIÓN ====================
echo -e "\n${YELLOW}11. CREANDO CONFIGURACIONES...${NC}"

# Crear archivo .htaccess seguro pero minimalista
sudo tee "$WEB_ROOT/.htaccess" > /dev/null <<'EOL'
# Configuración básica de seguridad
Options -Indexes

# Proteger archivos sensibles
<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql)$">
    Require all denied
</FilesMatch>

# Configurar errores PHP
php_flag display_errors Off
php_flag log_errors On

# Redireccionar errores a la página principal
ErrorDocument 404 /index.html
ErrorDocument 500 /index.html
ErrorDocument 503 /index.html
EOL
print_status ".htaccess creado"

# Crear archivo phpinfo para diagnóstico
sudo tee "$WEB_ROOT/phpinfo.php" > /dev/null <<'EOL'
<?php
// Archivo temporal para diagnóstico
phpinfo();
?>
EOL
print_status "Archivo phpinfo.php creado para diagnóstico"

# ==================== 12. CONFIGURAR PHP ====================
echo -e "\n${YELLOW}12. CONFIGURANDO PHP...${NC}"

# Crear archivo de configuración PHP personalizado
sudo tee "/etc/php/8.*/apache2/conf.d/99-custom.ini" > /dev/null <<'EOL'
; Configuración PHP personalizada
max_execution_time = 120
max_input_time = 120
memory_limit = 256M
post_max_size = 32M
upload_max_filesize = 32M
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
EOL
print_status "Configuración PHP personalizada aplicada"

# ==================== 13. REINICIAR APACHE ====================
echo -e "\n${YELLOW}13. REINICIANDO APACHE...${NC}"
sudo systemctl restart apache2

# Verificar que Apache está corriendo
if sudo systemctl is-active --quiet apache2; then
    print_status "Apache reiniciado exitosamente"
else
    print_error "Apache no se pudo reiniciar"
    sudo systemctl status apache2 --no-pager
fi

# ==================== 14. VERIFICAR INSTALACIÓN ====================
echo -e "\n${YELLOW}14. VERIFICANDO INSTALACIÓN...${NC}"

# Obtener IPs
IP_LOCAL=$(hostname -I | awk '{print $1}')
IP_PUBLICA=$(curl -s --max-time 3 ifconfig.me || echo "No disponible")

echo -e "\n${GREEN}================================================${NC}"
echo -e "${GREEN}✅ INSTALACIÓN COMPLETADA${NC}"
echo -e "${GREEN}================================================${NC}"
echo -e "🌐 Dirección local:  ${YELLOW}http://$IP_LOCAL${NC}"
echo -e "🌍 Dirección pública: ${YELLOW}http://$IP_PUBLICA${NC}"
echo -e "📁 Web root:         ${YELLOW}$WEB_ROOT${NC}"
echo -e "📊 Diagnóstico PHP:  ${YELLOW}http://$IP_LOCAL/phpinfo.php${NC}"
echo -e "🏠 Página principal: ${YELLOW}http://$IP_LOCAL/${NC}"
echo -e "${GREEN}================================================${NC}"

# ==================== 15. PRUEBAS FINALES ====================
echo -e "\n${YELLOW}15. EJECUTANDO PRUEBAS FINALES...${NC}"

# Probar Apache localmente
echo -n "Probando Apache localmente... "
if curl -s -o /dev/null -w "%{http_code}" http://localhost/ | grep -q "200\|301\|302"; then
    print_status "Apache responde correctamente"
else
    print_error "Apache no responde"
fi

# Probar PHP
echo -n "Probando PHP... "
if [ -f "$WEB_ROOT/phpinfo.php" ]; then
    if curl -s http://localhost/phpinfo.php | grep -q "phpinfo"; then
        print_status "PHP funciona correctamente"
    else
        print_error "PHP no responde"
    fi
fi

# Verificar logs
echo -e "\n${YELLOW}Últimas líneas del log de errores:${NC}"
sudo tail -5 /var/log/apache2/error.log

# Verificar archivos copiados
echo -e "\n${YELLOW}Archivos en $WEB_ROOT:${NC}"
ls -la "$WEB_ROOT/" | head -10

echo -e "\n${GREEN}✅ Script completado.${NC}"
echo -e "${YELLOW}Nota:${NC} Recuerda borrar el archivo phpinfo.php después de las pruebas:"
echo -e "     ${YELLOW}sudo rm $WEB_ROOT/phpinfo.php${NC}"