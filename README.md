# Google Review Bot

PHP 8.2 ve MySQL ile hazırlanmış, Google İşletme Profile API ve Gemini tabanlı otomatik yanıt botu. Arayüz tamamen Türkçe ve Apple tarzı minimal beyaz tasarım içerir.

## Kurulum
1. Depoyu sunucunuza kopyalayın ve PHP 8.2 + MySQL kurulu olduğundan emin olun.
2. `sql/schema.sql` dosyasını MySQL veritabanınıza uygulayın.
3. `config/config.php` dosyasındaki API anahtarlarını (Google OAuth, Gemini, Stripe, PayPal) ve veritabanı bilgilerini doldurun.
4. Web sunucunuzun kökünü `/public` klasörüne yönlendirin.
5. Cron: `*/5 * * * * php /path/to/backend/cron/review_checker.php` komutunu ekleyin.
6. PDF için `composer require mpdf/mpdf` komutuyla mpdf kurun.

## Önemli Dosyalar
- `public/login.php`, `public/register.php`: kullanıcı girişi ve kaydı
- `public/google_login.php`, `public/google_callback.php`: Google OAuth
- `backend/api/`: Google yorum çekme, AI yanıt ve Google'a yanıt gönderme
- `backend/payments/`: Stripe ve PayPal entegrasyonu için uç noktalar
- `backend/cron/review_checker.php`: 5 dakikada bir çalışacak bot
- `backend/pdf/monthly_report.php`: aylık PDF raporu
- `admin/index.php`: admin paneli
