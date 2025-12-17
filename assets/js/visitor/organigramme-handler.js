document.addEventListener('DOMContentLoaded', function() {
    // Filtres membres par poste
    const filterPoste = document.getElementById('filter-poste-org');
    const filterGrade = document.getElementById('filter-grade-org');
    const membresContainer = document.getElementById('membres-by-poste');
    
    if (filterPoste && membresContainer) {
        filterPoste.addEventListener('change', filterMembres);
    }
    
    if (filterGrade && membresContainer) {
        filterGrade.addEventListener('change', filterMembres);
    }
    
    function filterMembres() {
        const poste = filterPoste ? filterPoste.value : '';
        const grade = filterGrade ? filterGrade.value : '';
        const cards = membresContainer.querySelectorAll('.membre-card-compact');
        
        let visibleCount = 0;
        
        cards.forEach(card => {
            const cardPoste = card.dataset.poste || '';
            const cardGrade = card.dataset.grade || '';
            
            const matchesPoste = !poste || cardPoste === poste;
            const matchesGrade = !grade || cardGrade === grade;
            
            if (matchesPoste && matchesGrade) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Filtres équipes
    const searchEquipe = document.getElementById('search-equipe');
    const filterDomaine = document.getElementById('filter-domaine');
    const equipesContainer = document.getElementById('equipes-container');
    const noEquipesResults = document.getElementById('no-equipes-results');
    
    if (searchEquipe) {
        searchEquipe.addEventListener('input', debounce(filterEquipes, 300));
    }
    
    if (filterDomaine) {
        filterDomaine.addEventListener('change', filterEquipes);
    }
    
    function filterEquipes() {
        const searchTerm = searchEquipe ? searchEquipe.value.toLowerCase().trim() : '';
        const domaine = filterDomaine ? filterDomaine.value : '';
        const cards = equipesContainer.querySelectorAll('.equipe-card');
        
        let visibleCount = 0;
        
        cards.forEach(card => {
            const cardNom = card.dataset.nom || '';
            const cardDomaine = card.dataset.domaine || '';
            
            const matchesSearch = !searchTerm || cardNom.includes(searchTerm);
            const matchesDomaine = !domaine || cardDomaine === domaine;
            
            if (matchesSearch && matchesDomaine) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        if (visibleCount === 0) {
            if (noEquipesResults) noEquipesResults.style.display = 'block';
            equipesContainer.style.display = 'none';
        } else {
            if (noEquipesResults) noEquipesResults.style.display = 'none';
            equipesContainer.style.display = 'flex';
        }
    }
    
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

function resetEquipesFilters() {
    const searchEquipe = document.getElementById('search-equipe');
    const filterDomaine = document.getElementById('filter-domaine');
    
    if (searchEquipe) searchEquipe.value = '';
    if (filterDomaine) filterDomaine.value = '';
    
    const event = new Event('change');
    if (filterDomaine) filterDomaine.dispatchEvent(event);
}