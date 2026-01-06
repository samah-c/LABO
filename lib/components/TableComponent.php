<?php
/**
 * TableComponent.php - Composant pour la gestion des tableaux
 */

require_once __DIR__ . '/../LabHelpers.php';

class TableComponent {
    
    /**
     * Génère une table générique avec actions
     * 
     * @param array $config Configuration de la table
     *   - data: array - Données à afficher
     *   - columns: array - Configuration des colonnes
     *   - actions: array - Actions disponibles pour chaque ligne
     *   - emptyMessage: string - Message si aucune donnée
     *   - class: string - Classe CSS additionnelle
     */
    public static function render($config) {
        $data = $config['data'] ?? [];
        $columns = $config['columns'] ?? [];
        $actions = $config['actions'] ?? [];
        $emptyMessage = $config['emptyMessage'] ?? 'Aucune donnée disponible';
        $tableClass = $config['class'] ?? 'table';
        
        $isEmpty = empty($data);
        $containerClass = $isEmpty ? 'table-container empty' : 'table-container';
        ?>
        <div class="<?= htmlspecialchars($containerClass) ?>">
            <?php if ($isEmpty): ?>
                <div class="empty-message">
                    <?= htmlspecialchars($emptyMessage) ?>
                </div>
            <?php else: ?>
                <table class="<?= htmlspecialchars($tableClass) ?>">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th><?= htmlspecialchars($col['label']) ?></th>
                            <?php endforeach; ?>
                            
                            <?php if (!empty($actions)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($columns as $col): ?>
                                    <td>
                                        <?php 
                                        $value = $row[$col['key']] ?? '-';
                                        
                                        if (isset($col['formatter'])) {
                                            echo $col['formatter']($value, $row);
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                
                                <?php if (!empty($actions)): ?>
                                    <td class="actions-cell">
                                        <?php 
                                        foreach ($actions as $action) {
                                            echo $action($row);
                                        }
                                        ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Génère une grille de cartes (projets, publications, etc.)
     * 
     * @param array $config Configuration de la grille
     *   - items: array - Éléments à afficher
     *   - cardRenderer: callable - Fonction pour rendre chaque carte
     *   - emptyMessage: string - Message si vide
     *   - gridClass: string - Classe CSS de la grille
     */
    public static function renderCardGrid($config) {
        $items = $config['items'] ?? [];
        $cardRenderer = $config['cardRenderer'] ?? null;
        $emptyMessage = $config['emptyMessage'] ?? 'Aucun élément à afficher';
        $gridClass = $config['gridClass'] ?? 'card-grid';
        ?>
        <div class="<?= htmlspecialchars($gridClass) ?>">
            <?php if (empty($items)): ?>
                <p class="empty-message"><?= htmlspecialchars($emptyMessage) ?></p>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <?php 
                    if (is_callable($cardRenderer)) {
                        echo $cardRenderer($item);
                    } else {
                        self::renderDefaultCard($item);
                    }
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Carte par défaut
     */
    private static function renderDefaultCard($item) {
        ?>
        <div class="card">
            <div class="card-header">
                <h3><?= htmlspecialchars($item['titre'] ?? 'Sans titre') ?></h3>
            </div>
            <div class="card-body">
                <p><?= truncate($item['description'] ?? '', 150) ?></p>
            </div>
            <?php if (isset($item['url'])): ?>
                <div class="card-footer">
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="btn-primary">
                        Voir détails
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Génère des cartes de statistiques
     * 
     * @param array $stats Statistiques à afficher
     */
    public static function renderStatsCards($stats) {
        if (empty($stats)) return;
        ?>
        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <?php if (isset($stat['icon'])): ?>
                        <div class="stat-icon"><?= $stat['icon'] ?></div>
                    <?php endif; ?>
                    
                    <h3><?= htmlspecialchars($stat['label']) ?></h3>
                    <div class="number"><?= htmlspecialchars($stat['value']) ?></div>
                    
                    <?php if (isset($stat['change'])): ?>
                        <div class="stat-change <?= $stat['change'] >= 0 ? 'positive' : 'negative' ?>">
                            <?= $stat['change'] >= 0 ? '↑' : '↓' ?>
                            <?= abs($stat['change']) ?>%
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
?>