# Karataş Vidanjör - Yayınlama Talimatları

Bu dokümanda web sitesini production'a (live) çıkarma adımları açıklanmaktadır.

## Sunucu Gereksinimleri

### Minimum Gereksinimler
- **PHP**: 7.4 veya üzeri
- **MySQL**: 5.7 veya üzeri (MariaDB 10.3+ da uyumlu)
- **Web Sunucusu**: Apache 2.4+ veya Nginx 1.16+
- **Disk Alanı**: Minimum 100MB (loglar ve görsel dosyalar için 500MB önerilir)
- **RAM**: Minimum 512MB (1GB önerilir)
- **Apache Modülleri**: mod_rewrite, mod_ssl (HTTPS için)

### Önerilen PHP Uzantıları
```bash
php-mysql
php-pdo
php-curl (email için)
php-mbstring
php-gd (varsa görsel optimizasyonu için)
```

## Adım Adım Kurulum

### 1. Dosyaları Yükleme
```bash
# Dosyaları sunucuya kopyalayın
scp -r karatasvidanjor/ kullanici@sunucuip:/var/www/html/domain.com/

# Veya FTP ile:
# - Tüm dosya ve klasörleri public_html klasörüne yükleyin
# - ZIP dosyası şeklinde yükleyip sunucuda extract edebilirsiniz
```

### 2. Veritabanı Kurulumu
```sql
-- MySQL/MariaDB'de yeni veritabanı oluşturun
CREATE DATABASE karatasvidanjor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Veritabanı kullanıcısı oluşturun (güvenlik için)
CREATE USER 'karatas_user'@'localhost' IDENTIFIED BY 'güçlü_şifre_buraya';
GRANT ALL PRIVILEGES ON karatasvidanjor.* TO 'karatas_user'@'localhost';
FLUSH PRIVILEGES;

-- İçe aktarma
mysql -u karatas_user -p karatasvidanjor < config/database.sql
```

### 3. Dosya İzinleri
```bash
# Dosya sahipliği ve izinleri
chown -R www-data:www-data /var/www/html/domain.com/
chmod -R 755 /var/www/html/domain.com/
chmod -R 644 /var/www/html/domain.com/*.php
chmod 644 /var/www/html/domain.com/.htaccess

# Güvenlik için config klasörü izni
chmod 600 /var/www/html/domain.com/config/database.php
```

### 4. Konfigürasyon Güncellemeleri

#### database.php
```php
// Gerçek sunucu bilgileriyle güncelleyin
define('DB_HOST', 'localhost');
define('DB_NAME', 'karatasvidanjor');
define('DB_USER', 'karatas_user');
define('DB_PASS', 'güçlü_şifre_buraya');
```

#### .htaccess
```apache
# HTTPS yönlendirmesi için uncomment yapın
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 5. Google Maps API Kurulumu

1. **Google Cloud Console'a gidin**: https://console.cloud.google.com/
2. **Yeni proje oluşturun**: "Karatas Vidanjör"
3. **Maps JavaScript API'yi etkinleştirin**
4. **API key oluşturun**
5. **Kısıtlamaları ayarlayın**:
   - HTTP referrer: `domain.com/*`, `*.domain.com/*`
   - IP adresleri: sunucu IP'niz

#### contact.php'yi güncelleyin:
```javascript
// Satır 391'i değiştirin:
<script async defer src="https://maps.googleapis.com/maps/api/js?key=GERÇEK_API_KEY&callback=initGoogleMap"></script>

// Satır 399'u aktif edin:
initGoogleMap();
```

### 6. SSL Sertifikası Kurulumu

#### Let's Encrypt (Ücretsiz)
```bash
# Certbot kurulumu
sudo apt install certbot python3-certbot-apache

# Certbot ile sertifika
sudo certbot --apache -d domain.com -d www.domain.com
```

#### Manuel SSL için
- Hosting control panel'den SSL aktif edin
- Sertifika dosyalarını doğru dizine yerleştirin

### 7. Email Ayarları

#### SMTP Geliştirmesi (opsiyonel)
Mail fonksiyonunu gelişmiş SMTP ile değiştirin:

```php
// contact_handler.php'de mail() fonksiyonu yerine:
// PHPMailer veya SwiftMailer kullanın
```

Hosting'inizde SMTP ayarlarını alın:
- Host: mail.domain.com
- Port: 587 (TLS) veya 465 (SSL)
- Username: info@domain.com
- Password: email_şifresi

### 8. Domain Ayarları

#### DNS Kayıtları
```bash
A     @           sunucuip
CNAME www         domain.com
MX    @           10 mail.domain.com
MX    @           20 backup-mail.server.com
```

#### Hosting Control Panel
- Domain'i sunucuya yönlendirin
- Subdomain'ler varsa ayarlayın
- Email hesapları oluşturun

### 9. Logo Ekleme

#### Boyutlar: 200x250px (max)
#### Format: PNG veya SVG (şeffaf arkaplan)
```bash
# Dosyayı klasöre koyun:
/var/www/html/domain.com/images/logo.png
```

### 10. Test ve Kontrol

#### Checklist
- [ ] Ana sayfa açılıyor mu?
- [ ] Menü linkleri çalışıyor mu?
- [ ] Responsive tasarım test edildi mi?
- [ ] İletişim formu çalışıyor mu?
- [ ] Google Maps görünüyor mu?
- [ ] WhatsApp butonu çalışıyor mu?
- [ ] SEO meta tagları var mı?
- [ ] SSL sertifikası aktif mi?
- [ ] Veritabanı bağlantısı çalışıyor mu?

#### Test Komutları
```bash
# Site erişimi
curl -I https://domain.com/

# SSL durumu
curl -I https://domain.com/ -k

# Veritabanı
mysql -u karatas_user -p karatasvidanjor -e "SHOW TABLES;"

# Log kontrolü
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log
```

## Güvenlik Kontrolleri

### 1. Dosya İzinleri
```bash
# Sadece gerekli dosyalar 644 izni
find . -type f -name "*.php" -exec chmod 644 {} \;

# Config dosyaları korumalı
chmod 600 config/database.php

# Upload edilebilir klasörler varsa 755
# chmod 755 uploads/
```

### 2. Firewall Ayarları
```bash
# HTTPS (80, 443)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# SSH (değiştirilmiş port)
sudo ufw allow 2222/tcp

# MySQL'i dışarıdan erişimi kısıtla
sudo ufw deny 3306/tcp
```

### 3. Backup Stratejisi
```bash
# Günlük backup script'i
#!/bin/bash
DATE=$(date +%Y%m%d)
mysqldump -u karatas_user -p karatasvidanjor > backup_$DATE.sql
tar -czf web_backup_$DATE.tar.gz /var/www/html/domain.com/
```

## Monitoring ve Maintenance

### 1. Log İzleme
```bash
# Apache log'ları
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log

# PHP log'ları
tail -f /var/log/php/error.log

# Custom log'lar
tail -f /var/www/html/domain.com/contact_logs.txt
```

### 2. Performans Monitoring
- Google PageSpeed Insights: https://pagespeed.web.dev/
- GTmetrix: https://gtmetrix.com/
- WebPageTest: https://www.webpagetest.org/

### 3. Regular Updates
```bash
# Sistem güncellemeleri
sudo apt update && sudo apt upgrade

# WordPress'iniz varsa da güncel tutun
# Güvenlik yamaları takip edin
```

## Troubleshooting

### Yaygın Sorunlar

#### 1. 500 Hatası
```bash
# Log kontrol
tail -20 /var/log/apache2/error.log

# .htaccess problemi
mv .htaccess .htaccess.bak  # Geçici olarak disable
```

#### 2. Veritabanı Bağlantı Hatası
```php
// database.php'de debugging ekleyin
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connection successful"; // Test için
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage(); // Test için
}
```

#### 3. SSL Sorunu
```bash
# Test
openssl s_client -connect domain.com:443

# Renewal
sudo certbot renew --dry-run
```

## İletişim ve Destek

**Teknik Destek**
- Email: karatasvidanjor@gmail.com  
- Phone: +90 538 366 03 25

**Hosting Sorunları**
- Önce web hosting sağlayıcınızın teknik desteğine başvurun
- Gerekirse sunucu SSH erişimi talep edin

---

**Son Güncelleme**: 2024
**Doküman Versiyonu**: 1.0