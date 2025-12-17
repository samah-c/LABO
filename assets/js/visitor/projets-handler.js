/**
 * Filtrage côté client pour la page projets visiteur - VERSION CORRIGÉE
 * Gère correctement tous les statuts incluant "en_cours"
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const searchInput = document.getElementById('search-input');
    const filterThematique = document.getElementById('filter-thematique');
    const filterStatus = document.getElementById('filter-status');
    const sortBy = document.getElementById('sort-by');
    const projectsContainer = document.getElementById('projects-container');
    const filteredCount = document.getElementById('filtered-count');
    const noResults = document.getElementById('no-results');

    // Vérifier que les éléments existent
    if (!projectsContainer) {
        console.warn('Conteneur de projets introuvable');
        return;
    }

    // Initialiser
    sortProjects();
    filterProjects();
    
    console.log('✅ Gestionnaire de projets visiteur initialisé');

    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterProjects, 300));
    }

    if (filterThematique) {
        filterThematique.addEventListener('change', filterProjects);
    }

    if (filterStatus) {
        filterStatus.addEventListener('change', filterProjects);
    }

    if (sortBy) {
        sortBy.addEventListener('change', sortProjects);
    }

    /**
     * Filtrer les projets - LOGIQUE CORRIGÉE
     */
    function filterProjects() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const thematique = filterThematique ? filterThematique.value : '';
        const status = filterStatus ? filterStatus.value : '';
        
        const cards = projectsContainer.querySelectorAll('.project-card');
        let visibleCount = 0;
        
        // Debug: afficher les valeurs de filtrage
        console.log('Filtres actifs:', { searchTerm, thematique, status });
        
        cards.forEach(card => {
            const cardTitle = card.dataset.title || '';
            const cardThematique = card.dataset.thematique || '';
            const cardStatus = card.dataset.status || '';
            
            // Debug: afficher les données de la carte
            if (status && visibleCount === 0) {
                console.log('Carte:', { cardTitle: card.querySelector('.project-title a')?.textContent, cardStatus, expectedStatus: status });
            }
            
            // Vérifier les correspondances
            const matchesSearch = !searchTerm || cardTitle.includes(searchTerm);
            const matchesThematique = !thematique || cardThematique === thematique;
            const matchesStatus = !status || cardStatus === status;
            
            // Afficher ou masquer la carte
            if (matchesSearch && matchesThematique && matchesStatus) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Debug: afficher le nombre de résultats
        console.log('Projets visibles:', visibleCount, '/', cards.length);
        
        // Mettre à jour le compteur
        if (filteredCount) {
            filteredCount.textContent = visibleCount;
        }
        
        // Afficher/masquer le message "aucun résultat"
        if (visibleCount === 0) {
            if (noResults) noResults.style.display = 'block';
            projectsContainer.style.display = 'none';
        } else {
            if (noResults) noResults.style.display = 'none';
            projectsContainer.style.display = 'grid';
        }
    }

    /**
     * Trier les projets
     */
    function sortProjects() {
        if (!sortBy) return;

        const sortValue = sortBy.value;
        const cards = Array.from(projectsContainer.querySelectorAll('.project-card'));
        
        cards.sort((a, b) => {
            switch(sortValue) {
                case 'title':
                    return a.dataset.title.localeCompare(b.dataset.title);
                    
                case 'thematique':
                    return a.dataset.thematique.localeCompare(b.dataset.thematique);
                    
                case 'recent':
                default:
                    return parseInt(b.dataset.date || 0) - parseInt(a.dataset.date || 0);
            }
        });
        
        // Réorganiser les cartes dans le DOM
        cards.forEach(card => projectsContainer.appendChild(card));
        
        // Appliquer les filtres après le tri
        filterProjects();
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
    const filterThematique = document.getElementById('filter-thematique');
    const filterStatus = document.getElementById('filter-status');
    const sortBy = document.getElementById('sort-by');
    
    if (searchInput) searchInput.value = '';
    if (filterThematique) filterThematique.value = '';
    if (filterStatus) filterStatus.value = '';
    if (sortBy) sortBy.value = 'recent';
    
    // Déclencher les événements pour actualiser l'affichage
    const event = new Event('change');
    if (filterThematique) filterThematique.dispatchEvent(event);
    
    console.log('✅ Filtres réinitialisés');
}