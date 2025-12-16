/**
 * diaporama.js - Gestion du diaporama automatique
 * À créer dans : assets/js/diaporama.js
 */

let currentSlide = 0;
let slideInterval;

// Démarrer le diaporama automatique au chargement
document.addEventListener('DOMContentLoaded', function() {
    startSlideshow();
});

// Démarrer le diaporama automatique (toutes les 5 secondes)
function startSlideshow() {
    slideInterval = setInterval(function() {
        changeSlide(1);
    }, 5000);
}

// Arrêter le diaporama automatique
function stopSlideshow() {
    clearInterval(slideInterval);
}

// Changer de slide
function changeSlide(direction) {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    
    if (slides.length === 0) return;
    
    // Retirer la classe active de la slide actuelle
    slides[currentSlide].classList.remove('active');
    indicators[currentSlide].classList.remove('active');
    
    // Calculer le nouvel index
    currentSlide = currentSlide + direction;
    
    // Boucler si nécessaire
    if (currentSlide >= slides.length) {
        currentSlide = 0;
    } else if (currentSlide < 0) {
        currentSlide = slides.length - 1;
    }
    
    // Ajouter la classe active à la nouvelle slide
    slides[currentSlide].classList.add('active');
    indicators[currentSlide].classList.add('active');
    
    // Redémarrer le timer
    stopSlideshow();
    startSlideshow();
}

// Aller à une slide spécifique
function goToSlide(index) {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    
    if (index < 0 || index >= slides.length) return;
    
    // Retirer la classe active de la slide actuelle
    slides[currentSlide].classList.remove('active');
    indicators[currentSlide].classList.remove('active');
    
    // Aller à la slide demandée
    currentSlide = index;
    
    // Ajouter la classe active
    slides[currentSlide].classList.add('active');
    indicators[currentSlide].classList.add('active');
    
    // Redémarrer le timer
    stopSlideshow();
    startSlideshow();
}

// Arrêter le diaporama quand on survole avec la souris
document.addEventListener('DOMContentLoaded', function() {
    const slideshow = document.querySelector('.slideshow-container');
    if (slideshow) {
        slideshow.addEventListener('mouseenter', stopSlideshow);
        slideshow.addEventListener('mouseleave', startSlideshow);
    }
});