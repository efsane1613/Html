// Smooth anchor navigation
(function(){
  const links = document.querySelectorAll('a[href^="#"]');
  for (const link of links) {
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href');
      if (!href || href === '#') return;
      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }
})();

// Year in footer
(function(){
  const yil = document.getElementById('yil');
  if (yil) yil.textContent = new Date().getFullYear();
})();

// Small interaction: open external links in new tab safely
(function(){
  const links = document.querySelectorAll('a[target="_blank"]');
  links.forEach(a => {
    if (!a.rel) a.rel = 'noopener';
  });
})();
