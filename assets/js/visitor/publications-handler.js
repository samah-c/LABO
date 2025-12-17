/**
 * Filtrage côté client pour la page publications visiteur
 * Gère correctement tous les types et domaines
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const searchInput = document.getElementById('search-input');
    const filterType = document.getElementById('filter-type');
    const filterDomaine = document.getElementById('filter-domaine');
    const filterAnnee = document.getElementById('filter-annee');
    const sortBy = document.getElementById('sort-by');
    const publicationsContainer = document.getElementById('publications-container');
    const filteredCount = document.getElementById('filtered-count');
    const noResults = document.getElementById('no-results');

    // Vérifier que les éléments existent
    if (!publicationsContainer) {
        console.warn('Conteneur de publications introuvable');
        return;
    }

    // DEBUG: Afficher les cartes au chargement
    const allCards = publicationsContainer.querySelectorAll('.publication-card');
    console.log('🔍 DEBUG - Publications détectées au chargement:', allCards.length);
    
    allCards.forEach((card, index) => {
        console.log(`Publication ${index + 1}:`, {
            titre: card.querySelector('.publication-title a')?.textContent?.trim(),
            'data-type': card.dataset.type,
            'data-domaine': card.dataset.domaine,
            'data-annee': card.dataset.annee
        });
    });

    // Initialiser
    sortPublications();
    filterPublications();
    
    console.log('✅ Gestionnaire de publications visiteur initialisé');

    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterPublications, 300));
    }

    if (filterType) {
        filterType.addEventListener('change', function() {
            console.log('🎯 Changement de filtre type:', this.value);
            filterPublications();
        });
    }

    if (filterDomaine) {
        filterDomaine.addEventListener('change', function() {
            console.log('🎯 Changement de filtre domaine:', this.value);
            filterPublications();
        });
    }

    if (filterAnnee) {
        filterAnnee.addEventListener('change', function() {
            console.log('🎯 Changement de filtre année:', this.value);
            filterPublications();
        });
    }

    if (sortBy) {
        sortBy.addEventListener('change', sortPublications);
    }

    /**
     * Filtrer les publications - LOGIQUE CORRIGÉE AVEC DEBUG
     */
    function filterPublications() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const type = filterType ? filterType.value : '';
        const domaine = filterDomaine ? filterDomaine.value : '';
        const annee = filterAnnee ? filterAnnee.value : '';
        
        const cards = publicationsContainer.querySelectorAll('.publication-card');
        let visibleCount = 0;
        
        // Debug: afficher les valeurs de filtrage
        console.log('🔍 Filtres actifs:', { 
            searchTerm: searchTerm || '(aucun)', 
            type: type || '(tous)', 
            domaine: domaine || '(tous)',
            annee: annee || '(toutes)'
        });
        
        cards.forEach((card, index) => {
            const cardTitle = (card.dataset.title || '').toLowerCase();
            const cardType = card.dataset.type || '';
            const cardDomaine = card.dataset.domaine || '';
            const cardAnnee = card.dataset.annee || '';
            
            // Vérifier les correspondances
            const matchesSearch = !searchTerm || cardTitle.includes(searchTerm);
            const matchesType = !type || cardType === type;
            const matchesDomaine = !domaine || cardDomaine === domaine;
            const matchesAnnee = !annee || cardAnnee === annee;
            
            // Debug détaillé pour les 3 premières cartes
            if ((type || domaine || annee) && index < 3) {
                console.log(`  📋 Publication ${index + 1}:`, {
                    titre: card.querySelector('.publication-title a')?.textContent?.trim().substring(0, 40) + '...',
                    cardType: `"${cardType}"`,
                    filterType: `"${type}"`,
                    matchType: matchesType,
                    cardDomaine: `"${cardDomaine}"`,
                    filterDomaine: `"${domaine}"`,
                    matchDomaine: matchesDomaine,
                    cardAnnee: `"${cardAnnee}"`,
                    filterAnnee: `"${annee}"`,
                    matchAnnee: matchesAnnee
                });
            }
            
            // Afficher ou masquer la carte
            const shouldShow = matchesSearch && matchesType && matchesDomaine && matchesAnnee;
            
            if (shouldShow) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Debug: afficher le nombre de résultats
        console.log(`✅ Publications visibles: ${visibleCount} / ${cards.length}`);
        
        // Mettre à jour le compteur
        if (filteredCount) {
            filteredCount.textContent = visibleCount;
        }
        
        // Afficher/masquer le message "aucun résultat"
        if (visibleCount === 0) {
            if (noResults) noResults.style.display = 'block';
            publicationsContainer.style.display = 'none';
        } else {
            if (noResults) noResults.style.display = 'none';
            publicationsContainer.style.display = 'grid';
        }
    }

    /**
     * Trier les publications
     */
    function sortPublications() {
        if (!sortBy) return;

        const sortValue = sortBy.value;
        const cards = Array.from(publicationsContainer.querySelectorAll('.publication-card'));
        
        console.log('🔀 Tri par:', sortValue);
        
        cards.sort((a, b) => {
            switch(sortValue) {
                case 'title':
                    return a.dataset.title.localeCompare(b.dataset.title);
                    
                case 'type':
                    return a.dataset.type.localeCompare(b.dataset.type);
                    
                case 'recent':
                default:
                    return parseInt(b.dataset.date || 0) - parseInt(a.dataset.date || 0);
            }
        });
        
        // Réorganiser les cartes dans le DOM
        cards.forEach(card => publicationsContainer.appendChild(card));
        
        // Appliquer les filtres après le tri
        filterPublications();
    }

    /**
     * Fonction debounce pour optimiser la recherche
     */
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
});

/**
 * Fonction globale pour réinitialiser les filtres
 */
function resetFilters() {
    const searchInput = document.getElementById('search-input');
    const filterType = document.getElementById('filter-type');
    const filterDomaine = document.getElementById('filter-domaine');
    const filterAnnee = document.getElementById('filter-annee');
    const sortBy = document.getElementById('sort-by');
    
    if (searchInput) searchInput.value = '';
    if (filterType) filterType.value = '';
    if (filterDomaine) filterDomaine.value = '';
    if (filterAnnee) filterAnnee.value = '';
    if (sortBy) sortBy.value = 'recent';
    
    // Déclencher les événements pour actualiser l'affichage
    const event = new Event('change');
    if (filterType) filterType.dispatchEvent(event);
    
    console.log('✅ Filtres réinitialisés');
}

/**
 * Fonction de diagnostic globale
 */
window.diagnosticPublications = function() {
    console.log('\n=== 🔬 DIAGNOSTIC COMPLET PUBLICATIONS ===\n');
    
    const cards = document.querySelectorAll('.publication-card');
    console.log(`📊 Nombre total de publications: ${cards.length}\n`);

    cards.forEach((card, index) => {
        const title = card.querySelector('.publication-title a')?.textContent?.trim();
        const badge = card.querySelector('.publication-type-badge')?.textContent?.trim();
        
        console.log(`Publication ${index + 1}: "${title}"`);
        console.log(`  - Badge visible: "${badge}"`);
        console.log(`  - data-type: "${card.dataset.type}"`);
        console.log(`  - data-domaine: "${card.dataset.domaine}"`);
        console.log(`  - data-annee: "${card.dataset.annee}"`);
        console.log(`  - data-title: "${card.dataset.title}"`);
        console.log('');
    });
    
    const filterType = document.getElementById('filter-type');
    if (filterType) {
        console.log('Options du filtre de type:');
        Array.from(filterType.options).forEach(option => {
            console.log(`  - "${option.value}" → ${option.text}`);
        });
    }
    
    const filterDomaine = document.getElementById('filter-domaine');
    if (filterDomaine) {
        console.log('\nOptions du filtre de domaine:');
        Array.from(filterDomaine.options).forEach(option => {
            console.log(`  - "${option.value}" → ${option.text}`);
        });
    }
    
    console.log('\n=== FIN DU DIAGNOSTIC ===\n');
};

console.log('💡 Tapez diagnosticPublications() dans la console pour un diagnostic complet');