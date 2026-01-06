<?php
/**
 * PageHeaderComponent.php - Composant pour l'en-tête de page avec actions
 */

require_once __DIR__ . '/../LabHelpers.php';

class PageHeaderComponent {
    
    /**
     * Génère l'en-tête de page avec titre et actions
     * 
     * @param array $config Configuration de l'en-tête
     *   - title: string - Titre principal (obligatoire)
     *   - subtitle: string - Sous-titre ou description
     *   - actions: array - Liste des boutons d'action
     *   - titleHtml: string - HTML personnalisé pour le titre (remplace title)
     */
    public static function render($config) {
        $title = $config['title'] ?? '';
        $subtitle = $config['subtitle'] ?? '';
        $actions = $config['actions'] ?? [];
        $titleHtml = $config['titleHtml'] ?? null;
        
        ?>
        <div class="page-header">
            <div class="page-header-content">
                <?php if ($titleHtml): ?>
                    <?= $titleHtml ?>
                <?php else: ?>
                    <h1><?= htmlspecialchars($title) ?></h1>
                    <?php if ($subtitle): ?>
                        <p class="page-subtitle"><?= $subtitle ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($actions)): ?>
                <div class="page-actions">
                    <?php foreach ($actions as $action): ?>
                        <?php self::renderAction($action); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            gap: 20px;
        }
        
        .page-header-content {
            flex: 1;
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }
        
        .page-subtitle {
            color: #6B7280;
            margin-top: 8px;
            margin-bottom: 0;
        }
        
        .page-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .page-actions {
                width: 100%;
            }
            
            .page-actions button,
            .page-actions a {
                flex: 1;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Génère un bouton d'action
     * 
     * @param array $action Configuration du bouton
     *   - type: string - 'button', 'link', 'modal' (défaut: button)
     *   - label: string - Texte du bouton
     *   - url: string - URL pour les boutons link
     *   - onclick: string - Code JavaScript pour onclick
     *   - class: string - Classes CSS (défaut: btn-secondary)
     *   - modalId: string - ID de la modale à ouvrir (pour type='modal')
     */
    private static function renderAction($action) {
        $type = $action['type'] ?? 'button';
        $label = $action['label'] ?? '';
        $class = $action['class'] ?? 'btn-secondary';
        
        switch ($type) {
            case 'link':
                $url = $action['url'] ?? '#';
                ?>
                <button class="<?= htmlspecialchars($class) ?>" 
                        onclick="window.location.href='<?= htmlspecialchars($url) ?>'">
                    <?= htmlspecialchars($label) ?>
                </button>
                <?php
                break;
                
            case 'modal':
                $modalId = $action['modalId'] ?? '';
                if ($modalId) {
                    require_once __DIR__ . '/ModalComponent.php';
                    ModalComponent::renderTrigger($modalId, $label, $class);
                }
                break;
                
            case 'button':
            default:
                $onclick = $action['onclick'] ?? '';
                ?>
                <button class="<?= htmlspecialchars($class) ?>" 
                        <?= $onclick ? 'onclick="' . htmlspecialchars($onclick) . '"' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </button>
                <?php
                break;
        }
    }
    
    /**
     * Génère un en-tête simple avec juste un titre
     * 
     * @param string $title Titre de la page
     * @param string $subtitle Sous-titre optionnel
     */
    public static function renderSimple($title, $subtitle = '') {
        self::render([
            'title' => $title,
            'subtitle' => $subtitle,
            'actions' => []
        ]);
    }
}
?>