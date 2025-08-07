#!/bin/bash

set -e

# Configurações
APP_DIR="/opt/captive-portal"
VENV_DIR="$APP_DIR/app/venv"
SERVICE_FILE="/etc/systemd/system/captive-portal.service"
DNSMASQ_CONF="/etc/dnsmasq.d/captive-portal.conf"

echo "[1/6] Instalando dependências do sistema..."
apt update
apt install -y python3 python3-venv python3-pip iptables ipset dnsmasq

echo "[2/6] Criando virtualenv e instalando dependências Python..."
python3 -m venv "$VENV_DIR"
"$VENV_DIR/bin/pip" install --upgrade pip
"$VENV_DIR/bin/pip" install -r "$APP_DIR/app/requirements.txt"

echo "[3/6] Copiando serviço systemd..."
cp "$APP_DIR/systemd/captive-portal.service" "$SERVICE_FILE"
systemctl daemon-reload
systemctl enable captive-portal

echo "[4/6] Criando configuração do dnsmasq..."
cat > "$DNSMASQ_CONF" <<EOF
interface=eth1
dhcp-range=10.0.0.100,10.0.0.200,12h
dhcp-option=3,10.0.0.1
dhcp-option=6,10.0.0.1
address=/#/10.0.0.1
EOF

echo "[5/6] Ativando e reiniciando dnsmasq..."
systemctl restart dnsmasq
systemctl enable dnsmasq

echo "[6/6] Instalando regras de firewall..."
bash "$APP_DIR/scripts/firewall.sh"

echo "✅ Instalação concluída!"
echo "Você pode iniciar o portal com: sudo systemctl start captive-portal"

