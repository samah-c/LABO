/**
 * diaporama.js - Gestion du diaporama automatique
 * À créer dans : assets/js/diaporama.js
 */

let currentSlide = 0;
let slideInterval;
let isAutoplayActive = true;

// Démarrer le diaporama automatique au chargement
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.slide');
    
    // Ne démarrer que s'il y a plus d'une slide
    if (slides.length > 1) {
        startSlideshow();
        
        // Ajouter les gestionnaires d'événements pour pause au survol
        const slideshowContainer = document.querySelector('.slideshow-container');
        if (slideshowContainer) {
            slideshowContainer.addEventListener('mouseenter', function() {
                stopSlideshow();
                isAutoplayActive = false;
            });
            
            slideshowContainer.addEventListener('mouseleave', function() {
                isAutoplayActive = true;
                startSlideshow();
            });
        }
    }
});

// Démarrer le diaporama automatique (toutes les 5 secondes)
function startSlideshow() {
    // Nettoyer l'intervalle existant pour éviter les doublons
    if (slideInterval) {
        clearInterval(slideInterval);
    }
    
    slideInterval = setInterval(function() {
        changeSlide(1);
    }, 5000); // 5000ms = 5 secondes
}

// Arrêter le diaporama automatique
function stopSlideshow() {
    if (slideInterval) {
        clearInterval(slideInterval);
        slideInterval = null;
    }
}

// Changer de slide
function changeSlide(direction) {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    
    // Vérifier qu'il y a des slides
    if (slides.length === 0) {
        return;
    }
    
    // Retirer la classe active de la slide actuelle
    if (slides[currentSlide]) {
        slides[currentSlide].classList.remove('active');
    }
    if (indicators[currentSlide]) {
        indicators[currentSlide].classList.remove('active');
    }
    
    // Calculer le nouvel index
    currentSlide = currentSlide + direction;
    
    // Boucler si nécessaire
    if (currentSlide >= slides.length) {
        currentSlide = 0;
    } else if (currentSlide < 0) {
        currentSlide = slides.length - 1;
    }
    
    // Ajouter la classe active à la nouvelle slide
    if (slides[currentSlide]) {
        slides[currentSlide].classList.add('active');
    }
    if (indicators[currentSlide]) {
        indicators[currentSlide].classList.add('active');
    }
    
    // Redémarrer le timer seulement si l'autoplay est actif
    if (isAutoplayActive) {
        stopSlideshow();
        startSlideshow();
    }
}

// Aller à une slide spécifique
function goToSlide(index) {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    
    // Vérifier que l'index est valide
    if (index < 0 || index >= slides.length) {
        return;
    }
    
    // Retirer la classe active de la slide actuelle
    if (slides[currentSlide]) {
        slides[currentSlide].classList.remove('active');
    }
    if (indicators[currentSlide]) {
        indicators[currentSlide].classList.remove('active');
    }
    
    // Aller à la slide demandée
    currentSlide = index;
    
    // Ajouter la classe active
    if (slides[currentSlide]) {
        slides[currentSlide].classList.add('active');
    }
    if (indicators[currentSlide]) {
        indicators[currentSlide].classList.add('active');
    }
    
    // Redémarrer le timer seulement si l'autoplay est actif
    if (isAutoplayActive) {
        stopSlideshow();
        startSlideshow();
    }
}

// Rendre les fonctions accessibles globalement pour les attributs onclick
window.changeSlide = changeSlide;
window.goToSlide = goToSlide;