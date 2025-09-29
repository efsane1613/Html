<?php
require_once 'config/database.php';

// Sayfa bilgilerini ayarla
$page_title = isset($page_title) ? $page_title : 'Karatas Vidanjör - Profesyonel Vidanjör Hizmetleri';
$page_description = isset($page_description) ? $page_description : 'Bursa ve çevresinde vidanjör, tıkalı gider açma, kanalizasyon ve sanitasyon hizmetleri. 7/24 profesyonel hizmet.';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <!-- SEO Meta Tags -->
    <meta name="keywords" content="vidanjör, tıkalı gider açma, kanalizasyon, sanitasyon, bursa, osmangazi, foseptik çekimi, kameralı sistem">
    <meta name="author" content="Karatas Vidanjör">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://karatasvidanjor.com">
    <meta property="og:site_name" content="Karatas Vidanjör">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>
<body>

<!-- Header -->
<header class="sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <!-- Logo alanı -->
            <a class="navbar-brand" href="index.php">
                <div class="logo-area">
                    <img src="images/logo.png" alt="Karatas Vidanjör" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div class="logo-placeholder" style="display: none;">
                        <strong>KARATAS VIDANJÖR</strong>
                    </div>
                </div>
            </a>
            
            <!-- Mobile menu button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Menü -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index') ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home me-1"></i>Anasayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'about') ? 'active' : ''; ?>" href="about.php">
                            <i class="fas fa-info-circle me-1"></i>Hakkımızda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'services') ? 'active' : ''; ?>" href="services.php">
                            <i class="fas fa-tools me-1"></i>Hizmetler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'contact') ? 'active' : ''; ?>" href="contact.php">
                            <i class="fas fa-envelope me-1"></i>İletişim
                        </a>
                    </li>
                </ul>
                
                <!-- İletişim bilgileri -->
                <div class="d-none d-lg-flex align-items-center ms-3">
                    <a href="tel:+905383660325" class="btn btn-outline-primary btn-sm me-2">
                        <i class="fas fa-phone me-1"></i>+90 538 366 03 25
                    </a>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- WhatsApp Sabit Butonu -->
<a href="https://wa.me/905383660325" class="whatsapp-btn" target="_blank" title="WhatsApp ile İletişim">
    <i class="fab fa-whatsapp"></i>
</a>