/**
 * Gestionnaire de notifications pour l'espace membre
 */

// Détection automatique de baseUrl
const baseUrl = window.baseUrl || (() => {
    const path = window.location.pathname;
    const match = path.match(/^(\/[^\/]+\/)/);
    return match ? match[1] : '/';
})();

class MembreNotificationManager {
    constructor() {
        this.bell = document.getElementById('membre-notification-bell');
        this.dropdown = document.getElementById('membre-notification-dropdown');
        this.badge = document.getElementById('membre-notification-count');
        this.list = document.getElementById('membre-notification-list');
        
        if (this.bell) {
            this.init();
        }
    }
    
    init() {
        // Event listeners
        this.bell.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        document.addEventListener('click', (e) => this.handleOutsideClick(e));
        
        // Charger les notifications au démarrage
        this.loadNotifications();
        this.updateBadge();
        
        // Polling toutes les 30 secondes
        this.startPolling();
    }
    
    toggleDropdown() {
        const isVisible = this.dropdown.style.display === 'block';
        this.dropdown.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            this.loadNotifications();
        }
    }
    
    handleOutsideClick(e) {
        if (!this.bell.contains(e.target) && !this.dropdown.contains(e.target)) {
            this.dropdown.style.display = 'none';
        }
    }
    
    async loadNotifications() {
        try {
            // S'assurer que baseUrl se termine par un /
            const url = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
            const response = await fetch(url + 'membre/notifications/getUserNotifications');
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.notifications);
            }
        } catch (error) {
            console.error('Erreur chargement notifications:', error);
        }
    }
    
    renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            this.list.innerHTML = `
                <div class="notification-empty">
                    <p>Aucune notification</p>
                </div>
            `;
            return;
        }
        
        this.list.innerHTML = notifications.slice(0, 10).map(notif => `
            <div class="notification-item ${notif.est_lu ? 'read' : 'unread'}" 
                 onclick="membreNotificationManager.markAsRead(${notif.id}, '${notif.lien || ''}')">
                <div class="notification-content">
                    <div class="notification-header">
                        <strong>${this.escapeHtml(notif.titre)}</strong>
                        ${notif.priorite === 'urgente' ? '<span class="priority-badge">!</span>' : ''}
                    </div>
                    <p>${this.escapeHtml(notif.message)}</p>
                    <span class="notification-time">${this.formatTime(notif.date_creation)}</span>
                </div>
            </div>
        `).join('');
    }
    
    async updateBadge() {
        try {
            // S'assurer que baseUrl se termine par un /
            const url = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
            const response = await fetch(url + 'membre/notifications/getUnreadCount');
            const data = await response.json();
            
            if (data.success && data.count > 0) {
                this.badge.textContent = data.count > 99 ? '99+' : data.count;
                this.badge.style.display = 'flex';
            } else {
                this.badge.style.display = 'none';
            }
        } catch (error) {
            console.error('Erreur badge:', error);
        }
    }
    
    async markAsRead(id, lien) {
        try {
            // S'assurer que baseUrl se termine par un /
            const url = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
            await fetch(url + `membre/notifications/markAsRead/${id}`);
            this.updateBadge();
            this.loadNotifications();
            
            if (lien) {
                window.location.href = url + lien.replace(/^\//, '');
            }
        } catch (error) {
            console.error('Erreur marquage lu:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            // S'assurer que baseUrl se termine par un /
            const url = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
            await fetch(url + 'membre/notifications/markAllAsRead');
            this.updateBadge();
            this.loadNotifications();
        } catch (error) {
            console.error('Erreur:', error);
        }
    }
    
    startPolling() {
        setInterval(() => this.updateBadge(), 30000); // Toutes les 30 secondes
    }
    
    formatTime(date) {
        const now = new Date();
        const notifDate = new Date(date);
        const diff = Math.floor((now - notifDate) / 1000);
        
        if (diff < 60) return 'À l\'instant';
        if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
        if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
        return `Il y a ${Math.floor(diff / 86400)} j`;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialiser
const membreNotificationManager = new MembreNotificationManager();

// Fonction globale pour marquer toutes comme lues
function markAllAsRead() {
    membreNotificationManager.markAllAsRead();
}