<?php
/**
 * ModalComponent.php - Composant pour la gestion des modales
 */

require_once __DIR__ . '/../LabHelpers.php';

class ModalComponent {
    
    /**
     * Génère une modale
     * 
     * @param array $config Configuration de la modale
     *   - id: string - ID unique de la modale
     *   - title: string - Titre de la modale
     *   - content: string - Contenu HTML
     *   - footer: string|null - Contenu du footer
     *   - size: string - Taille (small, medium, large, fullscreen)
     *   - showCloseButton: bool - Afficher le bouton de fermeture
     */
    public static function render($config) {
        $id = $config['id'] ?? 'modal';
        $title = $config['title'] ?? '';
        $content = $config['content'] ?? '';
        $footer = $config['footer'] ?? null;
        $size = $config['size'] ?? 'medium';
        $showCloseButton = $config['showCloseButton'] ?? true;
        ?>
        <div id="<?= htmlspecialchars($id) ?>" class="modal" style="display: none;">
            <div class="modal-overlay"></div>
            <div class="modal-content modal-<?= htmlspecialchars($size) ?>">
                <div class="modal-header">
                    <h2><?= htmlspecialchars($title) ?></h2>
                    <?php if ($showCloseButton): ?>
                        <button class="modal-close" data-modal-close="<?= htmlspecialchars($id) ?>">
                            &times;
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="modal-body">
                    <?= $content ?>
                </div>
                
                <?php if ($footer): ?>
                    <div class="modal-footer">
                        <?= $footer ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php self::renderModalScript($id); ?>
        <?php
    }
    
    /**
     * Génère une modale de confirmation
     */
    public static function renderConfirmModal($config) {
        $id = $config['id'] ?? 'confirm-modal';
        $title = $config['title'] ?? 'Confirmation';
        $message = $config['message'] ?? 'Êtes-vous sûr ?';
        $confirmText = $config['confirmText'] ?? 'Confirmer';
        $cancelText = $config['cancelText'] ?? 'Annuler';
        $confirmClass = $config['confirmClass'] ?? 'btn-danger';
        
        $footer = '
            <button type="button" class="btn-secondary" data-modal-close="' . htmlspecialchars($id) . '">
                ' . htmlspecialchars($cancelText) . '
            </button>
            <button type="button" class="' . htmlspecialchars($confirmClass) . '" id="' . htmlspecialchars($id) . '-confirm">
                ' . htmlspecialchars($confirmText) . '
            </button>
        ';
        
        self::render([
            'id' => $id,
            'title' => $title,
            'content' => '<p>' . htmlspecialchars($message) . '</p>',
            'footer' => $footer,
            'size' => 'small'
        ]);
    }
    
    /**
     * Génère une modale avec formulaire
     */
    public static function renderFormModal($config) {
        $id = $config['id'] ?? 'form-modal';
        $title = $config['title'] ?? '';
        $formConfig = $config['form'] ?? [];
        $size = $config['size'] ?? 'medium';
        
        // Capturer le formulaire dans un buffer
        ob_start();
        FormComponent::render($formConfig);
        $formHtml = ob_get_clean();
        
        self::render([
            'id' => $id,
            'title' => $title,
            'content' => $formHtml,
            'size' => $size,
            'footer' => null // Le formulaire a ses propres boutons
        ]);
    }
    
    /**
     * Génère le script JavaScript pour gérer la modale
     */
    private static function renderModalScript($id) {
        ?>
        <script>
        (function() {
            const modalId = '<?= $id ?>';
            const modal = document.getElementById(modalId);
            
            if (!modal) return;
            
            // Fonction pour ouvrir la modale
            window.openModal = function(id) {
                const targetModal = document.getElementById(id);
                if (targetModal) {
                    targetModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            };
            
            // Fonction pour fermer la modale
            window.closeModal = function(id) {
                const targetModal = document.getElementById(id);
                if (targetModal) {
                    targetModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            };
            
            // Fermer avec le bouton close
            const closeButtons = modal.querySelectorAll('[data-modal-close="' + modalId + '"]');
            closeButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    closeModal(modalId);
                });
            });
            
            // Fermer en cliquant sur l'overlay
            const overlay = modal.querySelector('.modal-overlay');
            if (overlay) {
                overlay.addEventListener('click', function() {
                    closeModal(modalId);
                });
            }
            
            // Fermer avec la touche Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display !== 'none') {
                    closeModal(modalId);
                }
            });
        })();
        </script>
        
        <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            position: relative;
            background: white;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .modal-small { width: 400px; }
        .modal-medium { width: 600px; }
        .modal-large { width: 800px; }
        .modal-fullscreen { width: 95vw; height: 95vh; }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            line-height: 1;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background: #f3f4f6;
            color: #111827;
        }
        
        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        @media (max-width: 768px) {
            .modal-content {
                width: 95% !important;
                max-height: 95vh;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Génère un bouton pour ouvrir une modale
     */
    public static function renderTrigger($modalId, $text = 'Ouvrir', $class = 'btn-primary') {
        ?>
        <button type="button" 
                class="<?= htmlspecialchars($class) ?>" 
                onclick="openModal('<?= htmlspecialchars($modalId) ?>')">
            <?= htmlspecialchars($text) ?>
        </button>
        <?php
    }
    
    /**
     * Génère un lien pour ouvrir une modale
     */
    public static function renderTriggerLink($modalId, $text = 'Ouvrir', $class = '') {
        ?>
        <a href="javascript:void(0)" 
           class="<?= htmlspecialchars($class) ?>" 
           onclick="openModal('<?= htmlspecialchars($modalId) ?>')">
            <?= htmlspecialchars($text) ?>
        </a>
        <?php
    }
}
?>