# Karatas Vidanjör Web Sitesi

Profesyonel vidanjör ve sanitasyon hizmetleri için modern, responsive kurumsal web sitesi.

## Özellikler

- **Responsive Tasarım**: Tüm cihazlarda mükemmel görünüm
- **Modern UI/UX**: Bootstrap 5 ve custom CSS ile çağdaş tasarım
- **SEO Uyumlu**: Google indexleme için optimize edilmiş
- **Güvenli Form**: PHP ile korumalı iletişim formu
- **Google Maps Entegrasyonu**: Konum gösterimi ve harita
- **WhatsApp Entegrasyonu**: Hızlı iletişim için sabit buton
- **Hızlı Yükleme**: Optimize edilmiş kod yapısı

## Teknik Detaylar

### Dil ve Teknolojiler
- **Backend**: PHP 7.4+
- **Veritabanı**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **CSS Framework**: Bootstrap 5.3.0
- **İkonlar**: Font Awesome 6.4.0
- **Maps**: Google Maps API

### Dosya Yapısı
```
karatasvidanjor/
├── config/
│   ├── database.php          # Veritabanı bağlantı ayarları
│   └── database.sql          # Veritabanı şeması
├── css/
│   └── style.css            # Custom CSS dosyası
├── js/
│   └── main.js              # JavaScript fonksiyonları
├── images/                  # Logo ve resimler
├── includes/
│   ├── header.php           # Sayfa başlığı ve menü
│   └── footer.php           # Sayfa alt bilgisi
├── index.php                # Ana sayfa
├── about.php                # Hakkımızda sayfası
├── services.php             # Hizmetler sayfası
├── contact.php              # İletişim sayfası
├── contact_handler.php      # Form işleyicisi
└── README.md               # Bu dosya
```

## Kurulum

### 1. Veritabanı Kurulumu
```sql
-- MySQL veritabanında çalıştırın:
SOURCE config/database.sql;
```

### 2. Konfigürasyon
`config/database.php` dosyasında veritabanı bilgilerini güncelleyin:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'karatasvidanjor');
define('DB_USER', 'kullanici_adi');
define('DB_PASS', 'sifre');
```

### 3. Google Maps API (Opsiyonel)
`contact.php` dosyasının sonunda Google Maps API key'i güncelleyin:
```javascript
// Google Maps API key ile güncelleyin
<script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initGoogleMap"></script>
```

### 4. Logoyu Ekleme
`images/logo.png` dosyasına şirket logosunu ekleyin.

## Hizmetler

Web sitesinde listelenen hizmetler:

1. Kameralı sistem ile tıkalı gider açma
2. Kamera sistemi ile kanal görüntüleme
3. Arıza tespiti ve periyodik bakım hizmetleri
4. Kanalizasyon hattı kazı ve bağlantı işleri
5. Mutfak ve lavabo tıkanıklığı açma
6. Vidanjörle foseptik çekimi ve logar temizliği
7. Tuvalet klozet tıkanıklığını açma
8. Özel cihazlarla pis su temizliği, su kaçak tespiti ve onarımı
9. Kalorifer tesisatı bakım ve onarımı
10. Yangın tesisatı bakım ve onarımı
11. Lavabo gideri açma
12. Mutfak gideri açma
13. Su kaçağı tespiti

## İletişim Bilgileri

- **Telefon**: +90 538 366 03 25
- **E-posta**: karatasvidanjor@gmail.com
- **Adres**: Bağlarbaşı Mahallesi 1. Ömer Oğlu Sokak No:2 Osmangazi/Bursa

## Güvenlik Özellikleri

- Form validasyonu (frontend ve backend)
- SQL injection koruması (PDO prepared statements)
- XSS koruması (htmlspecialchars)
- Email validasyonu
- CSRF token (geliştirilebilir)

## Performans Optimizasyonları

- CSS ve JS dosyaları minify edilmiş
- External CSS/JS CDN kullanımı
- Responsive görsel tasarım
- Lazy loading (geliştirilebilir)

## Browser Desteği

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+
- IE 11+ (temel destek)

## SEO Optimizasyonları

- Semantic HTML5 yapısı
- Meta description ve keywords
- Open Graph meta tags
- Structured data (geliştirilebilir)
- ALT tags (görsel için)
- Responsive viewport meta tag

## Geliştirme Notları

### Yapılacaklar (Todos)
- [ ] Google Maps API key entegrasyonu
- [ ] Email gönderim sistemi (PHPMailer)
- [ ] Admin panel geliştirme
- [ ] Sitemap.xml oluşturma
- [ ] robots.txt ekleme
- [ ] SSL sertifikası kontrolü
- [ ] Backup sistemi

### Deployment Checklist
- [ ] Veritabanı bilgilerini production'a göre düzenle
- [ ] Google Maps API key'i ekle
- [ ] Email SMTP ayarlarını yap
- [ ] Logo dosyasını ekle
- [ ] SSL sertifikası kurulumu
- [ ] Domain DNS ayarları

## Destek

Teknik destek ve geliştirme talepleri için:
- GitHub Issues kullanın
- Pull request gönderebilirsiniz

---

**Oluşturulma Tarihi**: 2024
**Versiyon**: 1.0
**Lisans**: Özel proje (Karatas Vidanjör)