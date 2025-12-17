/**
 * Filtrage côté client pour la page équipements visiteur
 * Gère correctement tous les types, états et localisations
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const searchInput = document.getElementById('search-input');
    const filterType = document.getElementById('filter-type');
    const filterEtat = document.getElementById('filter-etat');
    const filterLocalisation = document.getElementById('filter-localisation');
    const sortBy = document.getElementById('sort-by');
    const equipementsContainer = document.getElementById('equipements-container');
    const availableCount = document.getElementById('available-count');
    const noResults = document.getElementById('no-results');

    // Vérifier que les éléments existent
    if (!equipementsContainer) {
        console.warn('Conteneur d\'équipements introuvable');
        return;
    }

    // DEBUG: Afficher les cartes au chargement
    const allCards = equipementsContainer.querySelectorAll('.equipement-card');
    console.log('DEBUG - Équipements détectés au chargement:', allCards.length);
    
    allCards.forEach((card, index) => {
        console.log(`Équipement ${index + 1}:`, {
            nom: card.querySelector('.equipement-title a')?.textContent?.trim(),
            'data-type': card.dataset.type,
            'data-etat': card.dataset.etat,
            'data-localisation': card.dataset.localisation
        });
    });

    // Initialiser
    sortEquipements();
    filterEquipements();
    
    console.log('Gestionnaire d\'équipements visiteur initialisé');

    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterEquipements, 300));
    }

    if (filterType) {
        filterType.addEventListener('change', function() {
            console.log('Changement de filtre type:', this.value);
            filterEquipements();
        });
    }

    if (filterEtat) {
        filterEtat.addEventListener('change', function() {
            console.log('Changement de filtre état:', this.value);
            filterEquipements();
        });
    }

    if (filterLocalisation) {
        filterLocalisation.addEventListener('change', function() {
            console.log('Changement de filtre localisation:', this.value);
            filterEquipements();
        });
    }

    if (sortBy) {
        sortBy.addEventListener('change', sortEquipements);
    }

    /**
     * Filtrer les équipements
     */
    function filterEquipements() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const type = filterType ? filterType.value : '';
        const etat = filterEtat ? filterEtat.value : '';
        const localisation = filterLocalisation ? filterLocalisation.value : '';
        
        const cards = equipementsContainer.querySelectorAll('.equipement-card');
        let visibleCount = 0;
        let availableVisibleCount = 0;
        
        // Debug: afficher les valeurs de filtrage
        console.log('Filtres actifs:', { 
            searchTerm: searchTerm || '(aucun)', 
            type: type || '(tous)', 
            etat: etat || '(tous)',
            localisation: localisation || '(toutes)'
        });
        
        cards.forEach((card, index) => {
            const cardNom = (card.dataset.nom || '').toLowerCase();
            const cardType = card.dataset.type || '';
            const cardEtat = card.dataset.etat || '';
            const cardLocalisation = card.dataset.localisation || '';
            
            // Vérifier les correspondances
            const matchesSearch = !searchTerm || cardNom.includes(searchTerm);
            const matchesType = !type || cardType === type;
            const matchesEtat = !etat || cardEtat === etat;
            const matchesLocalisation = !localisation || cardLocalisation === localisation;
            
            // Debug détaillé pour les 3 premières cartes
            if ((type || etat || localisation) && index < 3) {
                console.log(`  Équipement ${index + 1}:`, {
                    nom: card.querySelector('.equipement-title a')?.textContent?.trim().substring(0, 30) + '...',
                    cardType: `"${cardType}"`,
                    filterType: `"${type}"`,
                    matchType: matchesType,
                    cardEtat: `"${cardEtat}"`,
                    filterEtat: `"${etat}"`,
                    matchEtat: matchesEtat
                });
            }
            
            // Afficher ou masquer la carte
            const shouldShow = matchesSearch && matchesType && matchesEtat && matchesLocalisation;
            
            if (shouldShow) {
                card.style.display = 'flex';
                visibleCount++;
                if (cardEtat === 'libre') {
                    availableVisibleCount++;
                }
            } else {
                card.style.display = 'none';
            }
        });
        
        // Debug: afficher le nombre de résultats
        console.log(`Équipements visibles: ${visibleCount} / ${cards.length}`);
        console.log(`Équipements disponibles: ${availableVisibleCount}`);
        
        // Mettre à jour le compteur de disponibles
        if (availableCount) {
            availableCount.textContent = availableVisibleCount;
        }
        
        // Afficher/masquer le message "aucun résultat"
        if (visibleCount === 0) {
            if (noResults) noResults.style.display = 'block';
            equipementsContainer.style.display = 'none';
        } else {
            if (noResults) noResults.style.display = 'none';
            equipementsContainer.style.display = 'grid';
        }
    }

    /**
     * Trier les équipements
     */
    function sortEquipements() {
        if (!sortBy) return;

        const sortValue = sortBy.value;
        const cards = Array.from(equipementsContainer.querySelectorAll('.equipement-card'));
        
        console.log('Tri par:', sortValue);
        
        cards.sort((a, b) => {
            switch(sortValue) {
                case 'nom':
                    return a.dataset.nom.localeCompare(b.dataset.nom);
                    
                case 'type':
                    return a.dataset.type.localeCompare(b.dataset.type);
                    
                case 'etat':
                    const etatOrder = { 'libre': 1, 'reserve': 2, 'en_maintenance': 3, 'hors_service': 4 };
                    return (etatOrder[a.dataset.etat] || 9) - (etatOrder[b.dataset.etat] || 9);
                    
                default:
                    return a.dataset.nom.localeCompare(b.dataset.nom);
            }
        });
        
        // Réorganiser les cartes dans le DOM
        cards.forEach(card => equipementsContainer.appendChild(card));
        
        // Appliquer les filtres après le tri
        filterEquipements();
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
    const filterEtat = document.getElementById('filter-etat');
    const filterLocalisation = document.getElementById('filter-localisation');
    const sortBy = document.getElementById('sort-by');
    
    if (searchInput) searchInput.value = '';
    if (filterType) filterType.value = '';
    if (filterEtat) filterEtat.value = '';
    if (filterLocalisation) filterLocalisation.value = '';
    if (sortBy) sortBy.value = 'nom';
    
    // Déclencher les événements pour actualiser l'affichage
    const event = new Event('change');
    if (filterType) filterType.dispatchEvent(event);
    
    console.log('Filtres réinitialisés');
}

console.log('Gestionnaire d\'équipements chargé');