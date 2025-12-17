/**
 * Filtrage côté client pour la page membres visiteur
 * Gère correctement tous les postes, équipes et grades
 * À placer dans : assets/js/visitor/membres-handler.js
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const searchInput = document.getElementById('search-input');
    const filterPoste = document.getElementById('filter-poste');
    const filterEquipe = document.getElementById('filter-equipe');
    const filterGrade = document.getElementById('filter-grade');
    const sortBy = document.getElementById('sort-by');
    const membresContainer = document.getElementById('membres-container');
    const filteredCount = document.getElementById('filtered-count');
    const noResults = document.getElementById('no-results');

    // Vérifier que les éléments existent
    if (!membresContainer) {
        console.warn('Conteneur de membres introuvable');
        return;
    }

    // DEBUG: Afficher les cartes au chargement
    const allCards = membresContainer.querySelectorAll('.membre-card');
    console.log('Membres détectés au chargement:', allCards.length);
    
    allCards.forEach((card, index) => {
        console.log(`Membre ${index + 1}:`, {
            nom: card.querySelector('.membre-name a')?.textContent?.trim(),
            'data-poste': card.dataset.poste,
            'data-equipe': card.dataset.equipe,
            'data-grade': card.dataset.grade
        });
    });

    // Initialiser
    sortMembres();
    filterMembres();
    
    console.log('Gestionnaire de membres visiteur initialisé');

    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterMembres, 300));
    }

    if (filterPoste) {
        filterPoste.addEventListener('change', function() {
            console.log('Changement de filtre poste:', this.value);
            filterMembres();
        });
    }

    if (filterEquipe) {
        filterEquipe.addEventListener('change', function() {
            console.log('Changement de filtre équipe:', this.value);
            filterMembres();
        });
    }

    if (filterGrade) {
        filterGrade.addEventListener('change', function() {
            console.log('Changement de filtre grade:', this.value);
            filterMembres();
        });
    }

    if (sortBy) {
        sortBy.addEventListener('change', sortMembres);
    }

    /**
     * Filtrer les membres - LOGIQUE CORRIGÉE AVEC DEBUG
     */
    function filterMembres() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const poste = filterPoste ? filterPoste.value : '';
        const equipe = filterEquipe ? filterEquipe.value : '';
        const grade = filterGrade ? filterGrade.value : '';
        
        const cards = membresContainer.querySelectorAll('.membre-card');
        let visibleCount = 0;
        
        // Debug: afficher les valeurs de filtrage
        console.log('Filtres actifs:', { 
            searchTerm: searchTerm || '(aucun)', 
            poste: poste || '(tous)', 
            equipe: equipe || '(toutes)',
            grade: grade || '(tous)'
        });
        
        cards.forEach((card, index) => {
            const cardName = (card.dataset.name || '').toLowerCase();
            const cardPoste = card.dataset.poste || '';
            const cardEquipe = card.dataset.equipe || '';
            const cardGrade = card.dataset.grade || '';
            
            // Vérifier les correspondances
            const matchesSearch = !searchTerm || cardName.includes(searchTerm);
            const matchesPoste = !poste || cardPoste === poste;
            const matchesEquipe = !equipe || cardEquipe === equipe;
            const matchesGrade = !grade || cardGrade === grade;
            
            // Debug détaillé pour les 3 premières cartes
            if ((poste || equipe || grade) && index < 3) {
                console.log(`  Membre ${index + 1}:`, {
                    nom: card.querySelector('.membre-name a')?.textContent?.trim().substring(0, 30) + '...',
                    cardPoste: `"${cardPoste}"`,
                    filterPoste: `"${poste}"`,
                    matchPoste: matchesPoste,
                    cardEquipe: `"${cardEquipe}"`,
                    filterEquipe: `"${equipe}"`,
                    matchEquipe: matchesEquipe,
                    cardGrade: `"${cardGrade}"`,
                    filterGrade: `"${grade}"`,
                    matchGrade: matchesGrade
                });
            }
            
            // Afficher ou masquer la carte
            const shouldShow = matchesSearch && matchesPoste && matchesEquipe && matchesGrade;
            
            if (shouldShow) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Debug: afficher le nombre de résultats
        console.log(`Membres visibles: ${visibleCount} / ${cards.length}`);
        
        // Mettre à jour le compteur
        if (filteredCount) {
            filteredCount.textContent = visibleCount;
        }
        
        // Afficher/masquer le message "aucun résultat"
        if (visibleCount === 0) {
            if (noResults) noResults.style.display = 'block';
            membresContainer.style.display = 'none';
        } else {
            if (noResults) noResults.style.display = 'none';
            membresContainer.style.display = 'grid';
        }
    }

    /**
     * Trier les membres
     */
    function sortMembres() {
        if (!sortBy) return;

        const sortValue = sortBy.value;
        const cards = Array.from(membresContainer.querySelectorAll('.membre-card'));
        
        console.log('Tri par:', sortValue);
        
        cards.sort((a, b) => {
            switch(sortValue) {
                case 'name':
                    return a.dataset.name.localeCompare(b.dataset.name);
                    
                case 'poste':
                    return a.dataset.poste.localeCompare(b.dataset.poste);
                    
                case 'equipe':
                    return (a.dataset.equipe || '').localeCompare(b.dataset.equipe || '');
                    
                default:
                    return a.dataset.name.localeCompare(b.dataset.name);
            }
        });
        
        // Réorganiser les cartes dans le DOM
        cards.forEach(card => membresContainer.appendChild(card));
        
        // Appliquer les filtres après le tri
        filterMembres();
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
    const filterPoste = document.getElementById('filter-poste');
    const filterEquipe = document.getElementById('filter-equipe');
    const filterGrade = document.getElementById('filter-grade');
    const sortBy = document.getElementById('sort-by');
    
    if (searchInput) searchInput.value = '';
    if (filterPoste) filterPoste.value = '';
    if (filterEquipe) filterEquipe.value = '';
    if (filterGrade) filterGrade.value = '';
    if (sortBy) sortBy.value = 'name';
    
    // Déclencher les événements pour actualiser l'affichage
    const event = new Event('change');
    if (filterPoste) filterPoste.dispatchEvent(event);
    
    console.log('Filtres réinitialisés');
}

/**
 * Fonction de diagnostic globale
 */
window.diagnosticMembres = function() {
    console.log('\n=== DIAGNOSTIC COMPLET MEMBRES ===\n');
    
    const cards = document.querySelectorAll('.membre-card');
    console.log(`Nombre total de membres: ${cards.length}\n`);

    cards.forEach((card, index) => {
        const nom = card.querySelector('.membre-name a')?.textContent?.trim();
        const badge = card.querySelector('.membre-status-badge')?.textContent?.trim();
        
        console.log(`Membre ${index + 1}: "${nom}"`);
        console.log(`  - Badge visible: "${badge}"`);
        console.log(`  - data-poste: "${card.dataset.poste}"`);
        console.log(`  - data-equipe: "${card.dataset.equipe}"`);
        console.log(`  - data-grade: "${card.dataset.grade}"`);
        console.log(`  - data-name: "${card.dataset.name}"`);
        console.log('');
    });
    
    const filterPoste = document.getElementById('filter-poste');
    if (filterPoste) {
        console.log('Options du filtre de poste:');
        Array.from(filterPoste.options).forEach(option => {
            console.log(`  - "${option.value}" → ${option.text}`);
        });
    }
    
    const filterEquipe = document.getElementById('filter-equipe');
    if (filterEquipe) {
        console.log('\nOptions du filtre d\'équipe:');
        Array.from(filterEquipe.options).forEach(option => {
            console.log(`  - "${option.value}" → ${option.text}`);
        });
    }
    
    const filterGrade = document.getElementById('filter-grade');
    if (filterGrade) {
        console.log('\nOptions du filtre de grade:');
        Array.from(filterGrade.options).forEach(option => {
            console.log(`  - "${option.value}" → ${option.text}`);
        });
    }
    
};
