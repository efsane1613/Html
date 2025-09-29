<?php
$page_title = 'İletişim - Karatas Vidanjör | Bursa Vidanjör Hizmetleri';
$page_description = 'Karatas Vidanjör ile iletişime geçin. +90 538 366 03 25 numaralı telefon ve karatasvidanjor@gmail.com e-posta adresi ile bize ulaşabilirsiniz.';
include 'includes/header.php';
?>

<!-- Page Banner -->
<section class="hero-section py-5" style="padding: 60px 0 !important;">
    <div class="container">
        <div class="hero-content">
            <h1 class="display-4 mb-3">İletişim</h1>
            <p class="lead">Bizimle İletişime Geçin</p>
        </div>
    </div>
</section>

<!-- İletişim Bilgileri -->
<section class="py-5">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h5>Telefon</h5>
                    <p class="text-muted mb-3">7/24 Hizmet</p>
                    <a href="tel:+905383660325" class="btn btn-primary">
                        <i class="fas fa-phone me-2"></i>+90 538 366 03 25
                    </a>
                    <div class="mt-3">
                        <a href="https://wa.me/905383660325" class="btn btn-success btn-sm" target="_blank">
                            <i class="fab fa-whatsapp me-1"></i>WhatsApp
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h5>E-posta</h5>
                    <p class="text-muted mb-3">Günlük kontrol</p>
                    <a href="mailto:karatasvidanjor@gmail.com" class="btn btn-primary">
                        <i class="fas fa-envelope me-2"></i>karatasvidanjor@gmail.com
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h5>Adres</h5>
                    <p class="text-muted mb-3">Hizmet Alanımız</p>
                    <p class="small text-muted mb-3">
                        Bağlarbaşı Mahallesi<br>
                        1. Ömer Oğlu Sokak No:2<br>
                        Osmangazi/Bursa
                    </p>
                    <a href="#map" class="btn btn-primary" onclick="document.getElementById('googleMap').scrollIntoView();">
                        <i class="fas fa-map-marked-alt me-2"></i>Haritada Gör
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Çalışma Saatleri</h5>
                    <p class="text-muted mb-3">Hizmet Süresi</p>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li>Pazartesi - Pazar: 24 Saat</li>
                        <li>Acil Durumlar: 7/24</li>
                        <li>Hızlı Müdahale</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- İletişim Formu -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-envelope me-2"></i>İletişim Formu
                        </h4>
                    </div>
                    <div class="card-body">
                        <form id="contactForm" method="POST" action="contact_handler.php">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Ad Soyad *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">E-posta *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Telefon *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="service" class="form-label">Hizmet Türü</label>
                                    <select class="form-control" id="service" name="service">
                                        <option value="">Hizmet Seçin</option>
                                        <option value="kamerali-gider-acma">Kameralı Sistem ile Tıkalı Gider Açma</option>
                                        <option value="vidanjor-cekimi">Vidanjörle Foseptik Çekimi</option>
                                        <option value="su-kacak-tespiti">Su Kaçağı Tespiti</option>
                                        <option value="kanalizasyon-kazi">Kanalizasyon Hattı Kazı İşleri</option>
                                        <option value="mutfak-lavabo">Mutfak ve Lavabo Tıkanıklığı</option>
                                        <option value="tuvalet-kiloz">Tuvalet Klozet Tıkanıklığı</option>
                                        <option value="kalorifer-bakim">Kalorifer Tesisatı Bakım</option>
                                        <option value="yangin-tesisat">Yangın Tesisatı Bakım</option>
                                        <option value="ariza-tespiti">Arıza Tespiti ve Periyodik Bakım</option>
                                        <option value="acil-mudahale">Acil Müdahale</option>
                                        <option value="diger">Diğer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Mesaj *</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Hizmet talebinizi veya sorununuzu detaylı olarak yazın..."></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="privacy" name="privacy" required>
                                    <label class="form-check-label" for="privacy">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Kişisel Verilerin Korunması</a> şartlarını kabul ediyorum *
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>Mesaj Gönder
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Yan Panel -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Hızlı Bilgi
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Ortalama Müdahale Süresi:</h6>
                        <p class="text-muted mb-3">Acil durumlar için 30-60 dakika</p>
                        
                        <h6>Hizmet Sahası:</h6>
                        <p class="text-muted mb-3">Bursa ve çevre ilçeler</p>
                        
                        <h6>Ödeme Seçenekleri:</h6>
                        <p class="text-muted mb-3">Nakit, Kredi Kartı, Banka Havalesi</p>
                        
                        <h6>Garanti:</h6>
                        <p class="text-muted mb-0">6 ay garanti kapsamında</p>
                    </div>
                </div>
                
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Acil Durum
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <p class="mb-3">Acil müdahale gereken durumlarda:</p>
                        <a href="tel:+905383660325" class="btn btn-danger btn-lg mb-2">
                            <i class="fas fa-phone me-2"></i>Acil Arama
                        </a>
                        <p class="small text-muted mb-0">7/24 hazır ekibimizle</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Google Maps -->
<section id="map" class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="text-center mb-4">
                    <h2 class="section-title">Konumumuz</h2>
                    <p class="text-muted">Bizi kolayca bulabilirsiniz</p>
                </div>
                
                <!-- Google Maps Container -->
                <div class="google-maps">
                    <div id="googleMap" style="height: 400px; width: 100%;"></div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Bağlarbaşı Mahallesi 1. Ömer Oğlu Sokak No:2 Osmangazi/Bursa<br>
                        Navigasyon için telefon uygulamanızı kullanabilirsiniz
                    </small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Bölümü -->
<section class="py-5">
    <div class="container">
        <div class="section-title">
            <h2>Sık Sorulan Sorular</h2>
            <p>Müşterilerimizin en çok merak ettiği sorular</p>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h6 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Acil durumda ne kadar sürede yanınızda olursunuz?
                            </button>
                        </h6>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Acil durumlarda Bursa merkez için ortalama 30-60 dakika içinde yanınızdayız. 
                                Çevre ilçeler için süre biraz daha uzayabilir ancak telefonda daha net bilgi veririz.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Hizmet ücretleriniz nasıl belirlenir?
                            </button>
                        </h6>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Ücretlerimiz hizmet türüne ve işin karmaşıklığına göre belirlenir. 
                                Telefonda ön keşif yaparak size öngörü ücret verebiliriz.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Kameralı sistem nasıl çalışır?
                            </button>
                        </h6>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                HD kameralı sistemimizle tıkanıklık noktasını görüntülüyoruz. 
                                Bu sayede doğru teşhis koyuyor ve size görüntülü rapor sunuyoruz.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Hangi ödeme yöntemlerini kabul ediyorsunuz?
                            </button>
                        </h6>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Nakit, kredi kartı ve banka havalesi ile ödeme kabul ediyoruz. 
                                Kurumsal müşterilerimiz için çekle ödeme de mümkündür.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h6 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Garanti süreniz ne kadar?
                            </button>
                        </h6>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Genel temizlik hizmetleri için 3 ay, onarım işleri için 6 ay garanti veriyoruz. 
                                Garanti kapsamı işlem öncesi size açıklanır.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Privacy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kişisel Verilerin Korunması</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Kişisel Veri İşleme Amaçları:</h6>
                <ul>
                    <li>İletişim taleplerinizin değerlendirilmesi</li>
                    <li>Hizmet sunumu ve takibi</li>
                    <li>Fatura ve muhasebe işlemleri</li>
                    <li>Müşteri memnuniyeti araştırmaları</li>
                </ul>
                
                <h6 class="mt-3">Paylaşım:</h6>
                <p>Kişisel verileriniz sadece hizmet sunumu için gerekli olan personelimiz ve hukuki zorunluluk hallerinde yetkili kurumlarla paylaşılır.</p>
                
                <h6 class="mt-3">Haklarınız:</h6>
                <p>Kişisel verilerinizin işlenme durumu, düzeltilmesi ve silinmesi hakkında bizimle iletişime geçebilirsiniz.</p>
                
                <p class="small text-muted mt-3">
                    Detaylı bilgi için: karatasvidanjor@gmail.com
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- Google Maps API Script -->
<script>
    function initGoogleMap() {
        const mapElement = document.getElementById('googleMap');
        if (!mapElement) return;
        
        const mapOptions = {
            center: { lat: 40.1894, lng: 29.0610 }, // Osmangazi/Bursa coordinates
            zoom: 15,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        };
        
        const map = new google.maps.Map(mapElement, mapOptions);
        
        // Add marker
        const marker = new google.maps.Marker({
            position: { lat: 40.1894, lng: 29.0610 },
            map: map,
            title: 'Karatas Vidanjör',
            animation: google.maps.Animation.DROP
        });
        
        // Add info window
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px;">
                    <h6 style="margin: 0 0 5px 0; color: #1e3a8a;">Karatas Vidanjör</h6>
                    <p style="margin: 0; font-size: 12px;">Bağlarbaşı Mahallesi 1. Ömer Oğlu Sokak No:2<br>Osmangazi/Bursa</p>
                    <p style="margin: 5px 0 0 0; font-size: 12px;"><i class="fas fa-phone" style="margin-right: 5px;"></i>+90 538 366 03 25</p>
                </div>
            `
        });
        
        marker.addListener('click', function() {
            infoWindow.open(map, marker);
        });
        
        // Auto open info window
        infoWindow.open(map, marker);
    }
</script>

<!-- Placeholder for Google Maps API -->
<!-- Production'da aşağıdaki satırı Google Maps API key ile değiştirin -->
<script>
    // Bu kısım deployment sonrası Google Maps API key ile güncellenecek
    // initGoogleMap(); // Bu satır aktif edilecek
</script>

<?php include 'includes/footer.php'; ?>