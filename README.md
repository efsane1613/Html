# Otomatik Google Yorum Yanıtlayıcı

Bu depo, Google işletme profillerindeki yorumları tarayıp Gemini API ile yanıtlayan ve sonuçları MySQL tabanlı bir panelde
saklayan örnek bir PHP altyapısı içerir. Sistem, birden fazla işletmeyi yönetebilmeniz ve bütün yorum/yanıt geçmişini tek
panelden izlemeniz için tasarlandı.

## Özellikler

- MySQL veritabanında işletme ve yorum kayıtları
- Admin paneli üzerinden işletme ekleme, yorum ve yanıt geçmişini görüntüleme
- Google My Business API ile yorumları çekme ve otomatik yanıt gönderme
- Gemini (veya uyumlu bir LLM) ile yoruma göre Türkçe, İngilizce veya Almanca yanıt üretimi
- Google OAuth client kimlik bilgileri ile otomatik access/refresh token yönetimi ve bağlantı testi
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
6. Başarılı girişten sonra yönetim paneli açılır; Google OAuth Client ID/Secret bilgileri ve Gemini API anahtarlarını panel
   üzerinden kaydedebilirsiniz.

## Admin Paneli

Panel tek sayfalık bir arayüze sahiptir:

- **Yeni İşletme Ekle:** Google Location kimliği, Google OAuth Client ID/Secret, Authorized Redirect URI ve Authorized JavaScript
  Origin bilgilerini girerek işletme oluşturabilirsiniz. İsterseniz ilk kurulumda aldığınız Google yetkilendirme kodunu da girip access/refresh token'ların
  otomatik oluşmasını sağlayabilirsiniz; kodu sonra girmek isterseniz "Bağlantıyı Test Et" bölümünden tamamlayabilirsiniz.
  Gemini model alanı boş bırakılırsa sistem otomatik olarak `gemini-2.5-flash-lite-preview-09-2025` modelini kullanır.
- **İşletme Listesi:** Kayıtlı işletmeler; OAuth kimlik bilgileri, maskelenmiş access/refresh token'ları, bağlantı durumu & son
  test zamanı ve Gemini anahtarı/modeli ile birlikte listelenir. Aynı blokta çekilen/yanıtlanan/bekleyen yorum adetlerini hızlıca
  görebilir, gerekirse satırdaki "Bağlantıyı Test Et" butonuyla Google bağlantısını doğrulayabilirsiniz.
- **Yorumlar:** Google'dan çekilen tüm yorumlar ve sistemin gönderdiği yanıtlar listelenir. Yanıt bekleyen yorumlar "Yanıt
  Bekliyor" etiketiyle gösterilir. Panel üst kısmındaki meta rozetler toplam/yanıtlanan/bekleyen sayılarını ve son kontrol
  zamanlarını özetler.

## Google OAuth ve Token Alma Adımları

1. Google Cloud Console üzerinde bir proje açın ve **Google My Business API** (Business Profile API) yetkisini etkinleştirin.
2. "OAuth 2.0 Client ID" oluşturun (Web uygulaması veya masaüstü uygulaması olabilir). Panelde kullanacağınız **Client ID** ve
   **Client Secret** değerlerini not alın.
   - Web istemcisi oluştururken aşağıdaki alanları doldurun:
     - **Authorized redirect URIs:** `https://dybot.com.tr/seo/oauth/callback.php`
     - **Authorized JavaScript origins:** `https://dybot.com.tr`
     Bu örnek URL'ler projeyi `https://dybot.com.tr/seo/` altına kurduğunuz varsayımıyla verilmiştir. Farklı bir alan adına
     kurulum yaptığınızda panelde görüntülenen varsayılan değerleri Google Cloud Console'da tanımlayın.
3. OAuth Playground veya kendi yönlendirme URL'niz üzerinden şu kapsamla bir yetkilendirme kodu üretin:

   ```
   https://www.googleapis.com/auth/business.manage
   ```

   OAuth Playground kullanıyorsanız "Use your own OAuth credentials" seçeneğiyle Client ID/Secret bilgilerinizi girin ve
   yetkilendirme kodunu kopyalayın.
4. Google OAuth ekranındaki yönlendirme işleminden sonra sistem `public/oauth/callback.php` sayfasında yetkilendirme kodunu
   gösterir. Kodu kopyalayıp yönetim panelindeki "Bağlantıyı Test Et" alanına yapıştırarak access/refresh token oluşturabilirsiniz.
5. Yönetim panelinde işletme eklerken bu kodu girerseniz sistem otomatik olarak access/refresh token değerlerini alır ve Google
   bağlantısını test eder. Kodu girmeden kaydederseniz daha sonra listedeki **Bağlantıyı Test Et** formuna kodu yapıştırıp
   bağlantıyı doğrulayabilirsiniz. Test işlemi hem yeni token oluşturur (gerekirse) hem de Google My Business API çağrısının
   başarılı olduğunu teyit eder.

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
kaydedilir. Cron betiği access token süresi yaklaştığında refresh token ile otomatik yeniler, bağlantı durumunu günceller ve her
işletme için son kontrol zamanını/istatistiklerini panelde gösterir.

> Halihazırda kurulu bir veritabanınız varsa `businesses` tablosunu aşağıdaki alanlarla güncelleyerek OAuth ve istatistik
> özelliklerini etkinleştirebilirsiniz:
>
> ```sql
> ALTER TABLE businesses
>   ADD COLUMN google_client_id VARCHAR(255) NOT NULL AFTER google_location,
>   ADD COLUMN google_client_secret VARCHAR(255) NOT NULL AFTER google_client_id,
>   ADD COLUMN google_oauth_redirect_uri VARCHAR(255) NOT NULL AFTER google_client_secret,
>   ADD COLUMN google_oauth_javascript_origin VARCHAR(255) NOT NULL AFTER google_oauth_redirect_uri,
>   ADD COLUMN google_access_token TEXT DEFAULT NULL AFTER google_oauth_javascript_origin,
>   ADD COLUMN google_refresh_token TEXT DEFAULT NULL AFTER google_access_token,
>   ADD COLUMN google_access_token_expires_at DATETIME DEFAULT NULL AFTER google_refresh_token,
>   ADD COLUMN connection_status VARCHAR(32) DEFAULT 'never' AFTER google_access_token_expires_at,
>   ADD COLUMN connection_message TEXT DEFAULT NULL AFTER connection_status,
>   ADD COLUMN connection_checked_at DATETIME DEFAULT NULL AFTER connection_message,
>   ADD COLUMN last_checked_at DATETIME DEFAULT NULL,
>   ADD COLUMN last_check_fetched INT UNSIGNED NOT NULL DEFAULT 0,
>   ADD COLUMN last_check_replied INT UNSIGNED NOT NULL DEFAULT 0;
> ```
>
> Mevcut kayıtlar için yeni alanları varsayılan değerlerle güncellemek isterseniz (örnek alan adınız `dybot.com.tr` ise):
>
> ```sql
> UPDATE businesses
> SET google_oauth_redirect_uri = 'https://dybot.com.tr/seo/oauth/callback.php',
>     google_oauth_javascript_origin = 'https://dybot.com.tr'
> WHERE (google_oauth_redirect_uri IS NULL OR google_oauth_redirect_uri = '')
>    OR (google_oauth_javascript_origin IS NULL OR google_oauth_javascript_origin = '');
> ```
>
> Daha önce eklenen kayıtların Gemini modeli boş veya eski değeri içeriyorsa aşağıdaki sorgu ile varsayılan
> `gemini-2.5-flash-lite-preview-09-2025` modeline geçirebilirsiniz:
>
> ```sql
> UPDATE businesses
> SET gemini_model = 'gemini-2.5-flash-lite-preview-09-2025'
> WHERE gemini_model IS NULL OR gemini_model = '';
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
