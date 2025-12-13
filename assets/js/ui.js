/**
 * Modern UI - Animations style Figma
 * Animations subtiles et performantes
 */

// ========================================
// SMOOTH SCROLL REVEAL
// ========================================

const observerOptions = {
    threshold: 0.15,
    rootMargin: '0px 0px -100px 0px'
};

const uiObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.addEventListener('DOMContentLoaded', () => {
    const animatedElements = document.querySelectorAll('.stat-card, .menu-card, .table-container');
    
    animatedElements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        el.style.transitionDelay = `${index * 0.05}s`;
        uiObserver.observe(el);
    });
});

// ========================================
// STAT CARDS - Animation compteur
// ========================================

function updateStat(cardElement, newValue) {
    const numberElement = cardElement.querySelector('.number');
    const currentValue = parseInt(numberElement.textContent) || 0;
    
    const duration = 800;
    const steps = 30;
    const increment = (newValue - currentValue) / steps;
    let current = currentValue;
    let step = 0;
    
    const timer = setInterval(() => {
        current += increment;
        numberElement.textContent = Math.round(current);
        step++;
        
        if (step >= steps) {
            numberElement.textContent = newValue;
            clearInterval(timer);
        }
    }, duration / steps);
}

// ========================================
// TOAST SYSTEM - Style Figma
// ========================================

class ModernToast {
    constructor() {
        this.container = this.createContainer();
    }
    
    createContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
            document.body.appendChild(container);
        }
        return container;
    }
    
    show(message, type = 'info', duration = 3500) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        
        toast.innerHTML = `
            <span style="font-size: 16px; font-weight: 600;">${icons[type]}</span>
            <span>${message}</span>
        `;
        
        toast.style.cssText = `
            background: white;
            padding: 12px 18px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 300px;
            font-weight: 500;
            font-size: 14px;
            border: 1px solid;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        `;
        
        // Couleurs selon le type
        const colors = {
            success: { border: '#10B981', color: '#10B981' },
            error: { border: '#EF4444', color: '#EF4444' },
            warning: { border: '#F59E0B', color: '#F59E0B' },
            info: { border: '#3B82F6', color: '#3B82F6' }
        };
        
        toast.style.borderColor = colors[type].border;
        toast.style.color = colors[type].color;
        
        this.container.appendChild(toast);
        
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
            toast.style.opacity = '1';
        });
        
        setTimeout(() => {
            toast.style.transform = 'translateX(400px)';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}

window.toast = new ModernToast();

// ========================================
// MODAL SYSTEM
// ========================================

class ModernModal {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        if (this.modal) {
            this.setupEventListeners();
        }
    }
    
    setupEventListeners() {
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });
        
        const closeBtn = this.modal.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.style.display === 'flex') {
                this.close();
            }
        });
    }
    
    open() {
        this.modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        const content = this.modal.querySelector('.modal-content');
        if (content) {
            content.style.opacity = '0';
            content.style.transform = 'scale(0.95)';
            requestAnimationFrame(() => {
                content.style.transition = 'all 0.2s ease';
                content.style.opacity = '1';
                content.style.transform = 'scale(1)';
            });
        }
    }
    
    close() {
        const content = this.modal.querySelector('.modal-content');
        if (content) {
            content.style.opacity = '0';
            content.style.transform = 'scale(0.95)';
        }
        
        setTimeout(() => {
            this.modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 200);
    }
}

// ========================================
// DEBOUNCE pour recherche
// ========================================

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

// ========================================
// SEARCH avec loading
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        const debouncedSearch = debounce((value) => {
            if (value.length > 2) {
                // Ajoutez ici votre logique de recherche
                console.log('Recherche:', value);
            }
        }, 400);
        
        searchInput.addEventListener('input', (e) => {
            debouncedSearch(e.target.value);
        });
    }
});

// ========================================
// TABLE ROWS animation
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    const tableRows = document.querySelectorAll('.table tbody tr, .data-table tbody tr');
    
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-10px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, index * 30);
    });
});

// ========================================
// FORM VALIDATION
// ========================================

class FormValidator {
    constructor(formElement) {
        this.form = formElement;
        this.setupValidation();
    }
    
    setupValidation() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });
        
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
            }
        });
    }
    
    validateField(input) {
        const value = input.value.trim();
        const isRequired = input.hasAttribute('required');
        
        if (isRequired && !value) {
            this.showError(input, 'Ce champ est requis');
            return false;
        }
        
        if (input.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.showError(input, 'Email invalide');
                return false;
            }
        }
        
        this.clearError(input);
        return true;
    }
    
    showError(input, message) {
        this.clearError(input);
        
        input.style.borderColor = '#EF4444';
        input.style.background = 'rgba(239, 68, 68, 0.05)';
        
        const error = document.createElement('div');
        error.className = 'field-error';
        error.textContent = message;
        error.style.cssText = `
            color: #EF4444;
            font-size: 12px;
            margin-top: 4px;
            font-weight: 500;
        `;
        
        input.parentElement.appendChild(error);
    }
    
    clearError(input) {
        input.style.borderColor = '';
        input.style.background = '';
        
        const error = input.parentElement.querySelector('.field-error');
        if (error) {
            error.remove();
        }
    }
    
    validateForm() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
}

// ========================================
// BUTTON LOADING STATE
// ========================================

function setButtonLoading(button, loading = true) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.textContent;
        button.innerHTML = `
            <span style="display: inline-flex; align-items: center; gap: 8px;">
                <span style="
                    width: 14px;
                    height: 14px;
                    border: 2px solid rgba(255,255,255,0.3);
                    border-top-color: white;
                    border-radius: 50%;
                    animation: ui-spin 0.6s linear infinite;
                "></span>
                Chargement...
            </span>
        `;
    } else {
        button.disabled = false;
        button.textContent = button.dataset.originalText || 'Enregistrer';
    }
}

// ========================================
// PAGE FADE IN
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    document.body.style.opacity = '0';
    requestAnimationFrame(() => {
        document.body.style.transition = 'opacity 0.3s ease';
        document.body.style.opacity = '1';
    });
});

// ========================================
// AUTO-GROWING TEXTAREA
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    const textareas = document.querySelectorAll('textarea');
    
    textareas.forEach(textarea => {
        const adjustHeight = () => {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        };
        
        textarea.addEventListener('input', adjustHeight);
        adjustHeight();
    });
});

// ========================================
// STYLES CSS
// ========================================

if (!document.getElementById('ui-styles')) {
    const uiStyle = document.createElement('style');
    uiStyle.id = 'ui-styles';
    uiStyle.textContent = `
        @keyframes ui-spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(uiStyle);
}

// ========================================
// EXPORTS
// ========================================

window.ModernUI = {
    toast: window.toast,
    Modal: ModernModal,
    FormValidator: FormValidator,
    setButtonLoading: setButtonLoading,
    updateStat: updateStat
};

console.log('✨ UI Style Figma chargé');