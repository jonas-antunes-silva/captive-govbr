#!/bin/bash

# Interfaces de rede
WAN_IFACE="ens18"
LAN_IFACE="ens19"

# Obtém o IP da interface LAN
LAN_IP=$(ip -4 addr show "$LAN_IFACE" | grep -oP '(?<=inet\s)\d+(\.\d+){3}')

# Verifica se o IP foi obtido corretamente
if [ -z "$LAN_IP" ]; then
    echo "Erro: não foi possível obter o IP da interface $LAN_IFACE. Verifique se a interface está ativa e possui um IP."
    exit 1
fi

# Ativa o encaminhamento de IPs
echo 1 > /proc/sys/net/ipv4/ip_forward

# Limpa regras antigas
iptables -F
iptables -X
iptables -t nat -F
iptables -t mangle -F
iptables -t mangle -F internet_access
iptables -t mangle -X

ipset flush
ipset destroy authenticated_clients 2>/dev/null || true
ipset destroy temp_clients 2>/dev/null || true
ipset destroy autorizadores 2>/dev/null || true

# Cria ipset para IPs autenticados
ipset create authenticated_clients hash:ip,mac
ipset create temp_clients hash:ip,mac timeout 60
ipset create autorizadores hash:ip family inet

# Define políticas padrão
iptables -P INPUT ACCEPT
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT

# NAT para acesso à internet
iptables -t nat -A POSTROUTING -o "$WAN_IFACE" -j MASQUERADE

# Permite tráfego relacionado/estabelecido
#iptables -A FORWARD -i "$LAN_IFACE" -o "$WAN_IFACE" -m state --state RELATED,ESTABLISHED -j ACCEPT

# libera oauth do google
#iptables -A FORWARD -m set --match-set autorizadores dst,src -j ACCEPT

# Permite tráfego apenas para clientes autenticados
iptables -A FORWARD -i "$WAN_IFACE" -o "$LAN_IFACE"  -j ACCEPT

iptables -A FORWARD -i "$LAN_IFACE" -o "$WAN_IFACE" -m set --match-set authenticated_clients src,src -j ACCEPT

#iptables -A FORWARD -i "$LAN_IFACE" -o "$WAN_IFACE" -m set --match-set autorizadores dst -m set --match-set temp_clients src,src -j ACCEPT
#iptables -A FORWARD -i "$WAN_IFACE" -o "$LAN_IFACE" -m set --match-set autorizadores src -m set --match-set temp_clients dst,dst -j ACCEPT
iptables -A FORWARD -i "$LAN_IFACE" -o "$WAN_IFACE" -m set --match-set autorizadores dst -j ACCEPT


#Redireciona clientes não autenticados
iptables -t mangle -N internet_access
iptables -t mangle -A PREROUTING -i "$LAN_IFACE" -j internet_access


# Não marca pacotes com origem ou destino em authenticated_clients
iptables -t mangle -A internet_access -i "$LAN_IFACE" -m set --match-set authenticated_clients src,src -j LOG --log-prefix "aut-cli-src"
iptables -t mangle -A internet_access -i "$LAN_IFACE" -m set --match-set authenticated_clients src,src -j RETURN

# Não marca pacotes com origem em temp_clients e destino em autorizadores
iptables -t mangle -A internet_access -i "$LAN_IFACE" -m set --match-set temp_clients src,src -m set --match-set autorizadores dst -j LOG  --log-prefix "logip"
iptables -t mangle -A internet_access -i "$LAN_IFACE" -m set --match-set temp_clients src,src -m set --match-set autorizadores dst -j RETURN


iptables -t mangle -A internet_access -i "$LAN_IFACE" -j MARK --set-mark 99




# Marca pacotes de clientees não autorizados e que não vão para autorizadores
#iptables -t mangle -A internet_access -m set ! --match-set autorizadores dst -m set ! --match-set authenticated_clients src -m set ! --match-set temp_clients src -j MARK --set-mark 99

iptables -t nat -A PREROUTING -i "$LAN_IFACE" -m mark --mark 99 -p tcp --dport 80 -j DNAT --to-destination "$LAN_IP":80
iptables -t nat -A PREROUTING -i "$LAN_IFACE" -m mark --mark 99 -p tcp --dport 443 -j DNAT --to-destination "$LAN_IP":443

#Redireciona DNS para evitar bypass
iptables -t nat -A PREROUTING -i "$LAN_IFACE" -p udp --dport 53 -j DNAT --to-destination "$LAN_IP":53
