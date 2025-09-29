/**
 * Karataş Vidanjör - Ana JavaScript Dosyası
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                const headerHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = targetElement.offsetTop - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Navbar background change on scroll
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    });
    
    // Fade in animation for elements
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animatedElements = document.querySelectorAll('.service-card, .contact-card, .section-title');
    animatedElements.forEach(element => {
        observer.observe(element);
    });
    
    // Form validation and submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<span class="loading"></span> Gönderiliyor...';
            submitBtn.disabled = true;
            
            // Validate form
            if (validateContactForm(this)) {
                // Send form data
                fetch('contact_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Mesajınız başarıyla gönderildi!', 'success');
                        contactForm.reset();
                    } else {
                        showMessage(data.message || 'Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            } else {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }
    
    // WhatsApp button click tracking
    const whatsappBtn = document.querySelector('.whatsapp-btn');
    if (whatsappBtn) {
        whatsappBtn.addEventListener('click', function() {
            // Analytics tracking could be added here
            console.log('WhatsApp button clicked');
        });
    }
    
    // Phone number links enhancement
    const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
    phoneLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (!this.querySelector('.fas')) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-phone me-1';
                this.insertBefore(icon, this.firstChild);
            }
        });
    });
    
    // Email links enhancement
    const emailLinks = document.querySelectorAll('a[href^="mailto:"]');
    emailLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (!this.querySelector('.fas')) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-envelope me-1';
                this.insertBefore(icon, this.firstChild);
            }
        });
    });
    
    // Back to top button
    createBackToTopButton();
    
    // Initialize Google Maps if present
    if (typeof google !== 'undefined' && document.getElementById('googleMap')) {
        initGoogleMap();
    }
});

// Contact form validation
function validateContactForm(form) {
    let isValid = true;
    const name = form.querySelector('input[name="name"]');
    const email = form.querySelector('input[name="email"]');
    const phone = form.querySelector('input[name="phone"]');
    const message = form.querySelector('textarea[name="message"]');
    
    // Clear previous error states
    clearFormErrors(form);
    
    // Validate name
    if (!name.value.trim()) {
        showFieldError(name, 'Ad Soyad alanı zorunludur.');
        isValid = false;
    }
    
    // Validate email
    if (!email.value.trim()) {
        showFieldError(email, 'E-mail alanı zorunludur.');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        showFieldError(email, 'Geçerli bir e-mail adresi girin.');
        isValid = false;
    }
    
    // Validate phone
    if (!phone.value.trim()) {
        showFieldError(phone, 'Telefon alanı zorunludur.');
        isValid = false;
    } else if (!isValidPhone(phone.value)) {
        showFieldError(phone, 'Geçerli bir telefon numarası girin.');
        isValid = false;
    }
    
    // Validate message
    if (!message.value.trim()) {
        showFieldError(message, 'Mesaj alanı zorunludur.');
        isValid = false;
    }
    
    return isValid;
}

// Email validation
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Phone validation
function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

// Show field error
function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    let errorDiv = field.parentElement.querySelector('.invalid-feedback');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        field.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

// Clear form errors
function clearFormErrors(form) {
    const invalidFields = form.querySelectorAll('.is-invalid');
    const errorMessages = form.querySelectorAll('.invalid-feedback');
    
    invalidFields.forEach(field => field.classList.remove('is-invalid'));
    errorMessages.forEach(msg => msg.remove());
}

// Show message
function showMessage(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Create back to top button
function createBackToTopButton() {
    const backToTopBtn = document.createElement('button');
    backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.title = 'Yukarı Çık';
    
    // Styles
    Object.assign(backToTopBtn.style, {
        position: 'fixed',
        bottom: '80px',
        right: '20px',
        width: '50px',
        height: '50px',
        borderRadius: '50%',
        backgroundColor: 'var(--primary-color)',
        color: 'white',
        border: 'none',
        fontSize: '18px',
        cursor: 'pointer',
        display: 'none',
        zIndex: '999',
        transition: 'all 0.3s ease',
        boxShadow: '0 4px 15px rgba(0,0,0,0.2)'
    });
    
    document.body.appendChild(backToTopBtn);
    
    // Show/hide on scroll
    window.addEventListener('scroll', function() {
        if (window.scrollY > 500) {
            backToTopBtn.style.display = 'block';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });
    
    // Scroll to top on click
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Hover effects
    backToTopBtn.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
        this.style.backgroundColor = 'var(--secondary-color)';
    });
    
    backToTopBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.backgroundColor = 'var(--primary-color)';
    });
}

// Initialize Google Map
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
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                    <circle cx="16" cy="16" r="12" fill="#1e3a8a" stroke="#fff" stroke-width="2"/>
                    <path d="M12 16l4 4 8-8" stroke="#fff" stroke-width="2" stroke-linecap="round" fill="none"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(32, 32),
            anchor: new google.maps.Point(16, 16)
        }
    });
    
    // Add info window
    const infoWindow = new google.maps.InfoWindow({
        content: `
            <div style="padding: 10px;">
                <h6 style="margin: 0 0 5px 0; color: #1e3a8a;">Karatas Vidanjör</h6>
                <p style="margin: 0; font-size: 12px;">Bağlarbaşı Mahallesi 1. Ömer Oğlu Sokak No:2<br>Osmangazi/Bursa</p>
            </div>
        `
    });
    
    marker.addListener('click', function() {
        infoWindow.open(map, marker);
    });
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Performance optimization
const debouncedScroll = debounce(function() {
    // Scroll-based animations and effects
}, 16);

window.addEventListener('scroll', debouncedScroll);