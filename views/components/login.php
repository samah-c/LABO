<?php
// views/auth/Login.php

class Login {
    private $title;
    private $subtitle;
    private $action;
    private $method;
    private $showCredentialsHint;
    private $backLink;
    
    /**
     * Constructeur
     */
    public function __construct($config = []) {
        $this->title = $config['title'] ?? 'Laboratoire TDW';
        $this->subtitle = $config['subtitle'] ?? 'Authentification';
        $this->action = $config['action'] ?? '/TDW_project/auth/login';
        $this->method = $config['method'] ?? 'POST';
        $this->showCredentialsHint = $config['showCredentialsHint'] ?? false;
        $this->backLink = $config['backLink'] ?? '/TDW_project/';
    }
    
    /**
     * Générer l'en-tête HTML
     */
   /**
 * Méthode renderHead() mise à jour avec JavaScript et CSS
 * Remplace la méthode existante dans views/components/Login.php
 */

public function renderHead() {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connexion - <?php echo htmlspecialchars($this->title); ?></title>
        
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- CSS -->
        <link rel="stylesheet" href="/TDW_project/assets/css/styles.css">
        <link rel="stylesheet" href="/TDW_project/assets/css/login-ajax.css">
        <script src="/TDW_project/assets/js/ajax-login.js" defer></script>
        <style>
            /* Styles inline pour compatibilité immédiate */
            .form-group { position: relative; }
            .field-error { 
                color: #e74c3c; 
                font-size: 0.85em; 
                margin-top: 5px; 
                display: none; 
            }
            .has-error input { border-color: #e74c3c; }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-10px); }
                75% { transform: translateX(10px); }
            }
            .shake { animation: shake 0.5s; }
        </style>
    </head>
    <?php
}
    
    /**
     * Générer le logo/titre
     */
    public function renderLogo() {
        ?>
        <div class="logo">
            <h1><?php echo htmlspecialchars($this->title); ?></h1>
            <p><?php echo htmlspecialchars($this->subtitle); ?></p>
        </div>
        <?php
    }
    
    /**
     * Générer une alerte d'erreur
     */
    public function renderAlert($message, $type = 'error') {
        if (empty($message)) return;
        
        $alertClass = 'alert-' . $type;
        ?>
        <div class="alert <?php echo $alertClass; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php
    }
    
    /**
     * Générer les alertes depuis la session
     */
    public function renderSessionAlerts() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Alerte d'erreur
        if (isset($_SESSION['error'])) {
            $this->renderAlert($_SESSION['error'], 'error');
            unset($_SESSION['error']);
        }
        
        // Alerte d'erreurs multiples
        if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $error) {
                $this->renderAlert($error, 'error');
            }
            unset($_SESSION['errors']);
        }
        
        // Alerte de succès
        if (isset($_SESSION['success'])) {
            $this->renderAlert($_SESSION['success'], 'success');
            unset($_SESSION['success']);
        }
        
        // Alerte d'info
        if (isset($_SESSION['info'])) {
            $this->renderAlert($_SESSION['info'], 'info');
            unset($_SESSION['info']);
        }
        
        // Timeout de session
        if (isset($_GET['timeout'])) {
            $this->renderAlert('Session expirée. Veuillez vous reconnecter.', 'info');
        }
        
        // Déconnexion réussie
        if (isset($_GET['logout'])) {
            $this->renderAlert('Vous avez été déconnecté avec succès.', 'info');
        }
    }
    

    /**
     * Générer un champ de formulaire
     */
    public function renderFormField($config) {
        $type = $config['type'] ?? 'text';
        $name = $config['name'] ?? '';
        $id = $config['id'] ?? $name;
        $label = $config['label'] ?? '';
        $required = $config['required'] ?? true;
        $autofocus = $config['autofocus'] ?? false;
        $value = $config['value'] ?? '';
        $placeholder = $config['placeholder'] ?? '';
        ?>
        <div class="form-group">
            <?php if ($label): ?>
                <label for="<?php echo htmlspecialchars($id); ?>">
                    <?php echo htmlspecialchars($label); ?>
                </label>
            <?php endif; ?>
            <input 
                type="<?php echo htmlspecialchars($type); ?>" 
                id="<?php echo htmlspecialchars($id); ?>" 
                name="<?php echo htmlspecialchars($name); ?>"
                value="<?php echo htmlspecialchars($value); ?>"
                <?php if ($placeholder): ?>placeholder="<?php echo htmlspecialchars($placeholder); ?>"<?php endif; ?>
                <?php if ($required): ?>required<?php endif; ?>
                <?php if ($autofocus): ?>autofocus<?php endif; ?>
            >
        </div>
        <?php
    }
    
    /**
     * Générer le bouton de soumission
     */
    public function renderSubmitButton($text = 'Se connecter', $class = 'btn-login') {
        ?>
        <button type="submit" class="<?php echo htmlspecialchars($class); ?>">
            <?php echo htmlspecialchars($text); ?>
        </button>
        <?php
    }
    
    /**
     * Générer le lien de retour
     */
    public function renderBackLink($text = null, $url = null) {
        $linkText = $text ?? '← Retour à l\'accueil';
        $linkUrl = $url ?? $this->backLink;
        ?>
        <div class="back-link">
            <a href="<?php echo htmlspecialchars($linkUrl); ?>">
                <?php echo htmlspecialchars($linkText); ?>
            </a>
        </div>
        <?php
    }
    
    /**
     * Générer le formulaire complet de connexion
     */
    public function renderLoginForm($options = []) {
        $showUsername = $options['showUsername'] ?? true;
        $showPassword = $options['showPassword'] ?? true;
        $showEmail = $options['showEmail'] ?? false;
        $submitText = $options['submitText'] ?? 'Se connecter';
        $credentials = $options['credentials'] ?? [];
        ?>
        <form action="<?php echo htmlspecialchars($this->action); ?>" method="<?php echo htmlspecialchars($this->method); ?>">
            <?php
            if ($showUsername) {
                $this->renderFormField([
                    'type' => 'text',
                    'name' => 'username',
                    'id' => 'username',
                    'label' => 'Nom d\'utilisateur',
                    'required' => true,
                    'autofocus' => true
                ]);
            }
            
            if ($showEmail) {
                $this->renderFormField([
                    'type' => 'email',
                    'name' => 'email',
                    'id' => 'email',
                    'label' => 'Adresse email',
                    'required' => true
                ]);
            }
            
            if ($showPassword) {
                $this->renderFormField([
                    'type' => 'password',
                    'name' => 'password',
                    'id' => 'password',
                    'label' => 'Mot de passe',
                    'required' => true
                ]);
            }
            
            $this->renderSubmitButton($submitText);
            ?>
        </form>
        <?php
        
    }
    
    /**
     * Générer la page complète de connexion
     */
    public function render($options = []) {
        $this->renderHead();
        ?>
        <body class="login-body">
            <div class="login-container">
                <?php 
                $this->renderLogo();
                $this->renderSessionAlerts();
                $this->renderLoginForm($options);
                $this->renderBackLink();
                ?>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Générer uniquement le conteneur de connexion (sans html/body)
     */
    public function renderContainer($options = []) {
        ?>
        <div class="login-container">
            <?php 
            $this->renderLogo();
            $this->renderSessionAlerts();
            $this->renderLoginForm($options);
            $this->renderBackLink();
            ?>
        </div>
        <?php
    }
    
    /**
     * Méthode statique pour créer une instance rapidement
     */
    public static function create($config = []) {
        return new self($config);
    }
}