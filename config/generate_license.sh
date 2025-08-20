#!/bin/bash
#title: generate_license_ip.sh
#desc:  Generate license berdasarkan IP server dengan input tahun dari user

# --- Konfigurasi database ---
DB_HOST="192.168.99.173"
DB_USER="flamingo"
DB_PASS="fid1234"
DB_NAME="monitoring_db"

# Secret unik
SECRET="RAHASIA-SUPER"

# Ambil IP server utama
SERVER_IP=$(hostname -I | awk '{print $1}')
if [ -z "$SERVER_IP" ]; then
    echo "? Tidak bisa mengambil IP server!"
    exit 1
fi

# --- Input durasi dari user ---
echo "Berapa tahun license berlaku? (1-20): "
read -e TAHUN
if ! [[ $TAHUN =~ ^[0-9]+$ ]] || [ "$TAHUN" -lt 1 ] || [ "$TAHUN" -gt 20 ]; then
    echo "? Tahun tidak valid!"
    exit 1
fi

# Hitung expiry date
EXPIRY_DATE=$(date -d "+$TAHUN years" +"%Y-%m-%d")

# Generate license key (IP + SECRET + expiry date)
LICENSE=$(echo -n "$SERVER_IP$SECRET$EXPIRY_DATE" | sha256sum | awk '{print $1}')

# Insert/update ke licenses & license_active
QUERY1="INSERT INTO licenses (license_key, domain, expiry_date, status) 
        VALUES ('$LICENSE', '$SERVER_IP', '$EXPIRY_DATE', 'active')
        ON DUPLICATE KEY UPDATE expiry_date='$EXPIRY_DATE', status='active';"

QUERY2="DELETE FROM license_active; 
        INSERT INTO license_active (license_key) VALUES ('$LICENSE');"

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$QUERY1"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$QUERY2"

# Output
echo "===================================="
echo " Server IP   : $SERVER_IP"
echo " License Key : $LICENSE"
echo " Expiry Date : $EXPIRY_DATE"
echo "===================================="
echo "? License berhasil disimpan ke database"

