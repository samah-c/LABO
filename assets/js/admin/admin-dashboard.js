/**
 * Dashboard Admin avec AJAX
 */

class AdminDashboard {
    constructor() {
        this.statsCards = document.querySelectorAll('.stat-card');
        this.refreshInterval = 30000; // 30 secondes
        this.init();
    }
    
    init() {
        // Charger les statistiques au chargement
        this.loadStats();
        
        // Actualiser périodiquement
        setInterval(() => this.loadStats(), this.refreshInterval);
        
        // Ajouter les animations
        this.animateStats();
        
        // Recherche en temps réel
        this.initSearch();
        
        // Notifications en temps réel
        this.initNotifications();
    }
    
    /**
     * Charger les statistiques via AJAX
     */
    async loadStats() {
        try {
            const response = await fetch('/TDW_project/api/admin/stats', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) throw new Error('Erreur réseau');
            
            const data = await response.json();
            this.updateStats(data);
            
        } catch (error) {
            console.error('Erreur lors du chargement des stats:', error);
        }
    }
    
    /**
     * Mettre à jour les statistiques
     */
    updateStats(stats) {
        const mappings = {
            'total_users': stats.total_users,
            'total_membres': stats.total_membres,
            'total_projets': stats.total_projets,
            'total_publications': stats.total_publications
        };
        
        this.statsCards.forEach(card => {
            const numberElement = card.querySelector('.number');
            const currentValue = parseInt(numberElement.textContent);
            
            // Déterminer quelle stat mettre à jour
            const statKey = this.getStatKeyFromCard(card);
            const newValue = mappings[statKey];
            
            if (newValue !== undefined && newValue !== currentValue) {
                this.animateValue(numberElement, currentValue, newValue, 1000);
                
                // Effet visuel de mise à jour
                card.classList.add('updated');
                setTimeout(() => card.classList.remove('updated'), 2000);
            }
        });
    }
    
    /**
     * Animation de compteur
     */
    animateValue(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            
            if ((increment > 0 && current >= end) || 
                (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            
            element.textContent = Math.round(current);
        }, 16);
    }
    
    /**
     * Obtenir la clé de stat depuis la carte
     */
    getStatKeyFromCard(card) {
        const label = card.querySelector('h3').textContent.toLowerCase();
        
        if (label.includes('utilisateur')) return 'total_users';
        if (label.includes('membre')) return 'total_membres';
        if (label.includes('projet')) return 'total_projets';
        if (label.includes('publication')) return 'total_publications';
        
        return null;
    }
    
    /**
     * Animer les stats au chargement
     */
    animateStats() {
        this.statsCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
    
    /**
     * Recherche en temps réel
     */
    initSearch() {
        const searchInput = document.querySelector('#search-input');
        if (!searchInput) return;
        
        let searchTimeout;
        
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
        });
    }
    
    /**
     * Effectuer une recherche AJAX
     */
    async performSearch(query) {
        if (!query.trim()) {
            this.clearSearchResults();
            return;
        }
        
        try {
            const response = await fetch('/TDW_project/api/admin/search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ query })
            });
            
            const results = await response.json();
            this.displaySearchResults(results);
            
        } catch (error) {
            console.error('Erreur de recherche:', error);
        }
    }
    
    /**
     * Afficher les résultats de recherche
     */
    displaySearchResults(results) {
        const container = document.querySelector('#search-results');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (results.length === 0) {
            container.innerHTML = '<p class="no-results">Aucun résultat trouvé</p>';
            return;
        }
        
        results.forEach(result => {
            const item = document.createElement('div');
            item.className = 'search-result-item';
            item.innerHTML = `
                <h4>${this.escapeHtml(result.title)}</h4>
                <p>${this.escapeHtml(result.description)}</p>
                <a href="${this.escapeHtml(result.url)}">Voir</a>
            `;
            container.appendChild(item);
        });
    }
    
    /**
     * Nettoyer les résultats de recherche
     */
    clearSearchResults() {
        const container = document.querySelector('#search-results');
        if (container) container.innerHTML = '';
    }
    
    /**
     * Système de notifications en temps réel
     */
    initNotifications() {
        // Vérifier les nouvelles notifications toutes les 60 secondes
        setInterval(() => this.checkNotifications(), 60000);
    }
    
    /**
     * Vérifier les nouvelles notifications
     */
    async checkNotifications() {
        try {
            const response = await fetch('/TDW_project/api/admin/notifications', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const notifications = await response.json();
            
            if (notifications.length > 0) {
                this.displayNotifications(notifications);
            }
            
        } catch (error) {
            console.error('Erreur notifications:', error);
        }
    }
    
    /**
     * Afficher les notifications
     */
    displayNotifications(notifications) {
        notifications.forEach(notif => {
            this.showToast(notif.message, notif.type);
        });
    }
    
    /**
     * Afficher une notification toast
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Animation d'entrée
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Suppression après 5 secondes
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    /**
     * Échapper le HTML pour prévenir XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialiser le dashboard
document.addEventListener('DOMContentLoaded', () => {
    new AdminDashboard();
});