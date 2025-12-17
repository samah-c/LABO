
class LoginValidator {
    constructor(formSelector) {
        this.form = document.querySelector(formSelector);
        this.usernameInput = this.form.querySelector('#username');
        this.passwordInput = this.form.querySelector('#password');
        
        this.init();
    }
    
    init() {
        // Validation en temps réel sur chaque champ
        this.usernameInput.addEventListener('blur', () => this.validateUsername());
        this.passwordInput.addEventListener('blur', () => this.validatePassword());
        
        // Validation à la soumission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Afficher/masquer le mot de passe
        this.addPasswordToggle();
    }
    
    validateUsername() {
        const username = this.usernameInput.value.trim();
        const errorDiv = this.getOrCreateErrorDiv(this.usernameInput);
        
        if (username === '') {
            this.showError(errorDiv, "Le nom d'utilisateur est requis");
            return false;
        }
        
        if (username.length < 3) {
            this.showError(errorDiv, "Le nom d'utilisateur doit contenir au moins 3 caractères");
            return false;
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            this.showError(errorDiv, "Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores");
            return false;
        }
        
        this.hideError(errorDiv);
        return true;
    }
    
    validatePassword() {
        const password = this.passwordInput.value;
        const errorDiv = this.getOrCreateErrorDiv(this.passwordInput);
        
        if (password === '') {
            this.showError(errorDiv, "Le mot de passe est requis");
            return false;
        }
        
        if (password.length < 3) {
            this.showError(errorDiv, "Le mot de passe doit contenir au moins 3 caractères");
            return false;
        }
        
        this.hideError(errorDiv);
        return true;
    }
    
    handleSubmit(e) {
        e.preventDefault();
        
        const isUsernameValid = this.validateUsername();
        const isPasswordValid = this.validatePassword();
        
        if (isUsernameValid && isPasswordValid) {
            // Désactiver le bouton pour éviter les doubles soumissions
            const submitBtn = this.form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Connexion en cours...';
            
            // Soumettre le formulaire
            this.form.submit();
        }
    }
    
    getOrCreateErrorDiv(input) {
        const formGroup = input.closest('.form-group');
        let errorDiv = formGroup.querySelector('.field-error');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            formGroup.appendChild(errorDiv);
        }
        
        return errorDiv;
    }
    
    showError(errorDiv, message) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.parentElement.classList.add('has-error');
    }
    
    hideError(errorDiv) {
        errorDiv.textContent = '';
        errorDiv.style.display = 'none';
        errorDiv.parentElement.classList.remove('has-error');
    }
    
    addPasswordToggle() {
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle';
        toggleBtn.setAttribute('aria-label', 'Afficher le mot de passe');
        
        const passwordGroup = this.passwordInput.closest('.form-group');
        passwordGroup.style.position = 'relative';
        passwordGroup.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', () => {
            const type = this.passwordInput.type === 'password' ? 'text' : 'password';
            this.passwordInput.type = type;
            toggleBtn.innerHTML = type === 'password'? 'text' : 'password';
        });
    }
}

// Initialiser la validation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    new LoginValidator('form');
});