# Otomatik Google Yorum Yanıtlayıcı

Bu depo, Google işletme profillerindeki yorumları tarayıp Gemini API ile yanıtlayan ve sonuçları MySQL tabanlı bir panelde
saklayan örnek bir PHP altyapısı içerir. Sistem, birden fazla işletmeyi yönetebilmeniz ve bütün yorum/yanıt geçmişini tek
panelden izlemeniz için tasarlandı.

## Özellikler

- MySQL veritabanında işletme ve yorum kayıtları
- Admin paneli üzerinden işletme ekleme, yorum ve yanıt geçmişini görüntüleme
- Google My Business API ile yorumları çekme ve otomatik yanıt gönderme
- Gemini (veya uyumlu bir LLM) ile Türkçe, samimi ve profesyonel yanıt üretimi
- Cron betiği ile tam otomatik çalışma, ayrıntılı log takibi

## Gereksinimler

- PHP 8.1+
- MySQL 5.7+ veya MariaDB 10.4+
- `curl` ve `pdo_mysql` PHP uzantıları

## Kurulum

1. Depoyu kopyalayın ve proje klasörüne gidin.
2. `database/schema.sql` dosyasını MySQL sunucunuza uygulayın:

   ```bash
   mysql -u dybotcom_test -p dybotcom_test < database/schema.sql
   ```

3. `config/database.php` dosyasındaki bilgileri doğrulayın. Varsayılan bağlantı detayları:

   ```php
   return [
       'host' => '127.0.0.1',
       'database' => 'dybotcom_test',
       'username' => 'dybotcom_test',
       'password' => ']MtIXV_JNByk',
       'charset' => 'utf8mb4',
   ];
   ```

4. Web sunucunuzu `public/` klasörüne yönlendirin (ör. Nginx `root` veya Apache `DocumentRoot`).
5. `http://localhost/login.php` adresine giderek giriş yapın. Varsayılan kullanıcı adı/şifre **admin / admin**'dir.
6. Başarılı girişten sonra yönetim paneli açılır; Google erişim jetonu ve Gemini API anahtarlarını panel üzerinden
   kaydedebilirsiniz.

## Admin Paneli

Panel tek sayfalık bir arayüze sahiptir:

- **Yeni İşletme Ekle:** Google Location kimliği, Google access token ve Gemini API anahtarı gibi zorunlu alanlarla yeni
  işletme ekleyebilirsiniz.
- **İşletme Listesi:** Kayıtlı işletmeler; Google konum kimliği, maskelenmiş erişim token'ı, Gemini anahtarı/modeli ve son
  cron kontrol zamanı ile birlikte listelenir. Aynı blokta çekilen/yanıtlanan/bekleyen yorum adetlerini hızlıca görebilirsiniz.
- **Yorumlar:** Google'dan çekilen tüm yorumlar ve sistemin gönderdiği yanıtlar listelenir. Yanıt bekleyen yorumlar "Yanıt
  Bekliyor" etiketiyle gösterilir. Panel üst kısmındaki meta rozetler toplam/yanıtlanan/bekleyen sayılarını ve son kontrol
  zamanlarını özetler.

## Cron ile Otomasyon

Google yorumlarını düzenli olarak kontrol etmek için `cron/process_reviews.php` betiğini kullanın:

```bash
php cron/process_reviews.php
```

Örnek cron kaydı (her 1 dakikada bir):

```
* * * * * /usr/bin/php /path/to/project/cron/process_reviews.php >> /var/log/review-cron.log 2>&1
```

Betiği her çalıştırdığınızda sistem veritabanındaki tüm işletmeleri dolaşır, yeni yorumları kaydeder, gerekirse Gemini ile
otomatik yanıt oluşturur ve yanıtları Google'a gönderir. İşlenen her adım `storage/app.log` dosyasına JSON formatında
kaydedilir. Cron betiği ayrıca her işletme için son kontrol zamanını ve ilgili istatistikleri günceller; bu bilgiler panelde
anlık olarak görünür.

> Halihazırda kurulu bir veritabanınız varsa `businesses` tablosuna aşağıdaki alanları ekleyerek yeni panel istatistiklerini
> etkinleştirebilirsiniz:
>
> ```sql
> ALTER TABLE businesses
>   ADD COLUMN last_checked_at DATETIME DEFAULT NULL,
>   ADD COLUMN last_check_fetched INT UNSIGNED NOT NULL DEFAULT 0,
>   ADD COLUMN last_check_replied INT UNSIGNED NOT NULL DEFAULT 0;
> ```

## Güvenlik Notları

- Google ve Gemini kimlik bilgilerini yönetim paneline girerken dikkatli olun; erişimi sadece yetkili kişilere verin.
- Örnek uygulama, kimlik bilgilerini düz metin olarak saklar. Üretim ortamında şifreleme veya gizli değişken yönetimi
  (Secret Manager, Vault vb.) tercih edin.
- Panel varsayılan olarak "admin / admin" bilgileriyle giriş sağlar. Canlı ortamda şifreyi değiştirip ek güvenlik
  katmanları (IP kısıtlama, 2FA vb.) eklemeniz önerilir.

## Özelleştirme

`src/Reviews/PromptBuilder.php` dosyasında prompt kurallarını düzenleyerek yanıtların tonunu ve içeriğini değiştirebilir,
`src/Google/GoogleMyBusinessClient.php` içinde Google API çağrılarını özelleştirebilirsiniz. Paneli genişletmek için
yeni rotalar veya raporlar ekleyebilir, işletme düzenleme/silme yetenekleri tanımlayabilirsiniz.
