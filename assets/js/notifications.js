// Détection automatique de baseUrl
const baseUrl = window.baseUrl || (() => {
    const path = window.location.pathname;
    const match = path.match(/^(\/[^\/]+\/)/);
    return match ? match[1] : '/';
})();

class NotificationManager {
    constructor() {
        this.bell = document.getElementById('notification-bell');
        this.dropdown = document.getElementById('notification-dropdown');
        this.badge = document.getElementById('notification-count');
        this.list = document.getElementById('notification-list');
        
        if (this.bell) {
            this.init();
        }
    }
    
    init() {
        this.bell.addEventListener('click', () => this.toggleDropdown());
        document.addEventListener('click', (e) => this.handleOutsideClick(e));
        this.loadNotifications();
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
            const response = await fetch(baseUrl + 'admin/notifications/getUserNotifications');
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.notifications);
                this.updateBadge();
            }
        } catch (error) {
            console.error('Erreur chargement notifications:', error);
        }
    }
    
    renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            this.list.innerHTML = '<p class="empty-state">Aucune notification</p>';
            return;
        }
        
        this.list.innerHTML = notifications.slice(0, 10).map(notif => `
            <div class="notification-item ${notif.est_lu ? 'read' : 'unread'}" 
                 onclick="notificationManager.markAsRead(${notif.id}, '${notif.lien || ''}')">
                <div class="notification-content">
                    <h5>${this.escapeHtml(notif.titre)}</h5>
                    <p>${this.escapeHtml(notif.message)}</p>
                    <span class="notification-time">${this.formatTime(notif.date_creation)}</span>
                </div>
                ${notif.priorite === 'urgente' ? '<span class="priority-badge urgent">!</span>' : ''}
            </div>
        `).join('');
    }
    
    async updateBadge() {
        try {
            const response = await fetch(baseUrl + 'admin/notifications/getUnreadCount');
            const data = await response.json();
            
            if (data.success && data.count > 0) {
                this.badge.textContent = data.count > 99 ? '99+' : data.count;
                this.badge.style.display = 'block';
            } else {
                this.badge.style.display = 'none';
            }
        } catch (error) {
            console.error('Erreur badge:', error);
        }
    }
    
    async markAsRead(id, lien) {
        try {
            await fetch(baseUrl + `admin/notifications/markAsRead/${id}`);
            this.updateBadge();
            this.loadNotifications();
            
            if (lien) {
                window.location.href = baseUrl + lien.replace(/^\//, '');
            }
        } catch (error) {
            console.error('Erreur marquage lu:', error);
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
const notificationManager = new NotificationManager();

async function markAllAsRead() {
    try {
        await fetch(baseUrl + 'admin/notifications/markAllAsRead');
        notificationManager.updateBadge();
        notificationManager.loadNotifications();
    } catch (error) {
        console.error('Erreur:', error);
    }
}