/**
 * Améliorations JavaScript pour les tableaux
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // GESTION DES TABLEAUX VIDES
    // ========================================
    
    /**
     * Vérifier si un tableau est vide et ajuster l'affichage
     */
    function checkEmptyTables() {
        const tables = document.querySelectorAll('.table, .data-table');
        
        tables.forEach(table => {
            const tbody = table.querySelector('tbody');
            const rows = tbody?.querySelectorAll('tr');
            const container = table.closest('.table-container');
            
            // Si pas de lignes ou seulement message vide
            if (!rows || rows.length === 0 || 
                (rows.length === 1 && rows[0].querySelector('.empty-cell'))) {
                container?.classList.add('empty');
            } else {
                container?.classList.remove('empty');
            }
        });
    }
    
    // Vérifier au chargement
    checkEmptyTables();
    
    
    // ========================================
    // TRI DES COLONNES
    // ========================================
    
    /**
     * Ajouter le tri sur les colonnes
     */
    function initTableSort() {
        const tables = document.querySelectorAll('.table, .data-table');
        
        tables.forEach(table => {
            const headers = table.querySelectorAll('th');
            
            headers.forEach((header, index) => {
                // Ne pas trier la colonne Actions
                if (header.textContent.toLowerCase().includes('action')) {
                    return;
                }
                
                // Ajouter curseur pointer
                header.style.cursor = 'pointer';
                header.title = 'Cliquer pour trier';
                
                // Ajouter icône de tri
                const sortIcon = document.createElement('span');
                sortIcon.className = 'sort-icon';
                sortIcon.innerHTML = ' ↕️';
                sortIcon.style.opacity = '0.3';
                sortIcon.style.fontSize = '12px';
                header.appendChild(sortIcon);
                
                // Gérer le clic
                header.addEventListener('click', function() {
                    sortTable(table, index);
                });
            });
        });
    }
    
    /**
     * Fonction de tri
     */
    function sortTable(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Déterminer l'ordre actuel
        const header = table.querySelectorAll('th')[columnIndex];
        const currentOrder = header.dataset.order || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        
        // Réinitialiser tous les headers
        table.querySelectorAll('th').forEach(th => {
            delete th.dataset.order;
            const icon = th.querySelector('.sort-icon');
            if (icon) {
                icon.innerHTML = ' ↕️';
                icon.style.opacity = '0.3';
            }
        });
        
        // Mettre à jour le header actuel
        header.dataset.order = newOrder;
        const icon = header.querySelector('.sort-icon');
        if (icon) {
            icon.innerHTML = newOrder === 'asc' ? ' ↑' : ' ↓';
            icon.style.opacity = '1';
        }
        
        // Trier les lignes
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex]?.textContent.trim() || '';
            const bValue = b.cells[columnIndex]?.textContent.trim() || '';
            
            // Essayer de comparer comme nombres
            const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
            const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return newOrder === 'asc' ? aNum - bNum : bNum - aNum;
            }
            
            // Sinon comparer comme texte
            return newOrder === 'asc' 
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        });
        
        // Réorganiser le DOM
        rows.forEach(row => tbody.appendChild(row));
    }
    
    
    // ========================================
    // CONFIRMATION DE SUPPRESSION
    // ========================================
    
    /**
     * Ajouter confirmation avant suppression
     */
    window.confirmDelete = function(id, entityName = 'cet élément') {
        return confirm(`Êtes-vous sûr de vouloir supprimer ${entityName} ?`);
    };
    
    
    // ========================================
    // HIGHLIGHT DES LIGNES
    // ========================================
    
    /**
     * Mettre en évidence une ligne récemment ajoutée/modifiée
     */
    window.highlightRow = function(rowId) {
        const row = document.querySelector(`tr[data-id="${rowId}"]`);
        if (row) {
            row.style.backgroundColor = '#D1FAE5';
            setTimeout(() => {
                row.style.transition = 'background-color 1s ease';
                row.style.backgroundColor = '';
            }, 500);
        }
    };
    
    
    // ========================================
    // RECHERCHE DANS LE TABLEAU
    // ========================================
    
    /**
     * Filtrer les lignes du tableau en temps réel
     */
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const tables = document.querySelectorAll('.table tbody, .data-table tbody');
            
            tables.forEach(tbody => {
                const rows = tbody.querySelectorAll('tr');
                let visibleCount = 0;
                
                rows.forEach(row => {
                    // Ne pas filtrer les lignes de message vide
                    if (row.querySelector('.empty-cell')) {
                        return;
                    }
                    
                    const text = row.textContent.toLowerCase();
                    const shouldShow = text.includes(searchTerm);
                    
                    row.style.display = shouldShow ? '' : 'none';
                    if (shouldShow) visibleCount++;
                });
                
                // Afficher message si aucun résultat
                const container = tbody.closest('.table-container');
                if (visibleCount === 0 && searchTerm) {
                    if (!tbody.querySelector('.no-results')) {
                        const noResults = document.createElement('tr');
                        noResults.className = 'no-results';
                        noResults.innerHTML = `
                            <td colspan="100" class="empty-cell">
                                Aucun résultat pour "${searchTerm}"
                            </td>
                        `;
                        tbody.appendChild(noResults);
                    }
                } else {
                    tbody.querySelector('.no-results')?.remove();
                }
            });
        });
    }
    
    
    // ========================================
    // EXPORT DU TABLEAU
    // ========================================
    
    /**
     * Exporter le tableau en CSV
     */
    window.exportTableToCSV = function(tableId = null, filename = 'export.csv') {
        const table = tableId 
            ? document.getElementById(tableId) 
            : document.querySelector('.table, .data-table');
            
        if (!table) return;
        
        const rows = [];
        
        // En-têtes
        const headers = Array.from(table.querySelectorAll('th'))
            .map(th => th.textContent.trim())
            .filter(h => h !== 'Actions'); // Exclure colonne Actions
        rows.push(headers);
        
        // Données
        table.querySelectorAll('tbody tr').forEach(tr => {
            if (tr.querySelector('.empty-cell, .no-results')) return;
            
            const cells = Array.from(tr.querySelectorAll('td'))
                .slice(0, -1) // Exclure dernière colonne (Actions)
                .map(td => {
                    // Nettoyer le texte des badges et boutons
                    const text = td.textContent.trim();
                    return `"${text.replace(/"/g, '""')}"`;
                });
            rows.push(cells);
        });
        
        // Créer et télécharger le fichier
        const csv = rows.map(r => r.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    };
    
    
    // ========================================
    // INITIALISATION
    // ========================================
    
    // Activer le tri si souhaité
    // initTableSort();
    
    console.log('✅ Améliorations des tableaux chargées');
});


// ========================================
// UTILITAIRES GLOBAUX
// ========================================

/**
 * Rafraîchir un tableau via AJAX
 */
window.refreshTable = async function(url, containerId) {
    try {
        const response = await fetch(url);
        const html = await response.text();
        
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = html;
            // Revérifier les tableaux vides
            const event = new Event('DOMContentLoaded');
            document.dispatchEvent(event);
        }
    } catch (error) {
        console.error('Erreur lors du rafraîchissement:', error);
    }
};

/**
 * Afficher une notification temporaire
 */
window.showNotification = function(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.animation = 'table-slideIn 0.3s ease';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'table-slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
};

// ========================================
// ANIMATIONS
// ========================================

if (!document.getElementById('table-enhancements-styles')) {
    const tableStyle = document.createElement('style');
    tableStyle.id = 'table-enhancements-styles';
    tableStyle.textContent = `
        @keyframes table-slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes table-slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(tableStyle);
}