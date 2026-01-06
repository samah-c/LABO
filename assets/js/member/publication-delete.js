/**
 * publication-delete.js - Gestion de la suppression des publications
 * À inclure dans : assets/js/publication-delete.js
 */

/**
 * Supprimer une publication
 */
async function deletePublication(publicationId, titre) {
    // Confirmation
    const confirmMessage = `Êtes-vous sûr de vouloir supprimer cette publication ?\n\n"${titre}"\n\nCette action est irréversible.`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    console.log('🗑️ Deleting publication:', publicationId);
    
    try {
        const response = await fetch(`${BASE_URL}/membre/publications/delete/${publicationId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        console.log('Delete response:', result);
        
        if (result.success) {
            // Show success message
            showSuccessMessage(result.message || 'Publication supprimée avec succès');
            
            // Remove the publication card with animation
            const card = document.querySelector(`[data-publication-id="${publicationId}"]`);
            if (card) {
                card.style.transition = 'all 0.3s ease-out';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    card.remove();
                    
                    // Check if list is empty
                    const publicationsList = document.querySelector('.publications-list');
                    if (publicationsList && publicationsList.children.length === 0) {
                        showEmptyState();
                    }
                }, 300);
            }
        } else {
            showErrorMessage(result.message || 'Erreur lors de la suppression');
        }
        
    } catch (error) {
        console.error('Error deleting publication:', error);
        showErrorMessage('Une erreur est survenue lors de la suppression');
    }
}

/**
 * Show empty state when no publications
 */
function showEmptyState() {
    const container = document.querySelector('.publications-list');
    if (!container) return;
    
    container.innerHTML = `
        <div class="empty-state" style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e0; margin-bottom: 16px;"></i>
            <h3 style="margin: 0 0 8px 0; color: #2d3748; font-size: 18px;">Aucune publication trouvée</h3>
            <p style="color: #718096; margin: 0 0 24px 0;">Commencez par soumettre votre première publication.</p>
            <button class="btn-primary" onclick="openModal('publication-modal')">
                <i class="fas fa-plus"></i>
                Soumettre une publication
            </button>
        </div>
    `;
}

/**
 * Show success message
 */
function showSuccessMessage(message) {
    const container = document.querySelector('.container');
    if (!container) return;
    
    // Remove existing alerts
    const existingAlerts = container.querySelectorAll('.alert-success, .alert-error');
    existingAlerts.forEach(alert => alert.remove());
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${message}</span>
    `;
    alert.style.cssText = `
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 20px;
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
        border-radius: 8px;
        margin-bottom: 24px;
        animation: slideDown 0.3s ease-out;
    `;
    
    container.insertBefore(alert, container.firstChild);
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    setTimeout(() => {
        alert.style.transition = 'all 0.3s ease-out';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

/**
 * Show error message
 */
function showErrorMessage(message) {
    const container = document.querySelector('.container');
    if (!container) return;
    
    // Remove existing alerts
    const existingAlerts = container.querySelectorAll('.alert-success, .alert-error');
    existingAlerts.forEach(alert => alert.remove());
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;
    alert.style.cssText = `
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 20px;
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
        border-radius: 8px;
        margin-bottom: 24px;
        animation: slideDown 0.3s ease-out;
    `;
    
    container.insertBefore(alert, container.firstChild);
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    setTimeout(() => {
        alert.style.transition = 'all 0.3s ease-out';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
    }, 8000);
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);

console.log('✓ publication-delete.js loaded');