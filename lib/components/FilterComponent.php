<?php
/**
 * FilterComponent.php - Composant pour la gestion des filtres et recherche
 */

require_once __DIR__ . '/../LabHelpers.php';

class FilterComponent {
    
    /**
     * Génère une barre de filtres complète
     * 
     * @param array $config Configuration des filtres
     *   - filters: array - Liste des filtres
     *   - searchPlaceholder: string - Placeholder de la recherche
     *   - showSearch: bool - Afficher la recherche
     *   - action: string - URL de soumission
     *   - method: string - Méthode HTTP (GET par défaut)
     */
    public static function render($config) {
        $filters = $config['filters'] ?? [];
        $searchPlaceholder = $config['searchPlaceholder'] ?? 'Rechercher...';
        $showSearch = $config['showSearch'] ?? true;
        $action = $config['action'] ?? '';
        $method = $config['method'] ?? 'GET';
        ?>
        <form method="<?= htmlspecialchars($method) ?>" 
              action="<?= htmlspecialchars($action) ?>" 
              class="filters-bar">
            
            <?php if ($showSearch): ?>
                <?php self::renderSearchBox($searchPlaceholder); ?>
            <?php endif; ?>
            
            <div class="filters">
                <?php foreach ($filters as $filter): ?>
                    <?php self::renderFilterItem($filter); ?>
                <?php endforeach; ?>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn-primary">
                    Filtrer
                </button>
            </div>
        </form>
        <?php
    }
    
    /**
     * Génère la boîte de recherche
     */
    private static function renderSearchBox($placeholder) {
        $searchValue = get('search', '');
        ?>
        <div class="search-box">
            <input type="text" 
                   name="search"
                   id="search-input" 
                   placeholder="<?= htmlspecialchars($placeholder) ?>"
                   value="<?= htmlspecialchars($searchValue) ?>">
        </div>
        <?php
    }
    
    /**
     * Génère un item de filtre
     */
    private static function renderFilterItem($filter) {
        $type = $filter['type'] ?? 'select';
        $name = $filter['name'] ?? '';
        $label = $filter['label'] ?? '';
        $options = $filter['options'] ?? [];
        $defaultLabel = $filter['defaultLabel'] ?? 'Tous';
        $currentValue = get($name, '');
        
        ?>
        <div class="filter-item">
            <?php if ($label): ?>
                <label for="filter-<?= htmlspecialchars($name) ?>">
                    <?= htmlspecialchars($label) ?>
                </label>
            <?php endif; ?>
            
            <?php if ($type === 'select'): ?>
                <select name="<?= htmlspecialchars($name) ?>" 
                        id="filter-<?= htmlspecialchars($name) ?>"
                        class="filter-select">
                    <?= select_options($options, $currentValue, $defaultLabel) ?>
                </select>
                
            <?php elseif ($type === 'date'): ?>
                <input type="date" 
                       name="<?= htmlspecialchars($name) ?>"
                       id="filter-<?= htmlspecialchars($name) ?>"
                       value="<?= htmlspecialchars($currentValue) ?>"
                       class="filter-date">
                       
            <?php elseif ($type === 'daterange'): ?>
                <?php self::renderDateRange($name, $filter); ?>
                
            <?php elseif ($type === 'checkbox'): ?>
                <?php self::renderCheckboxFilter($name, $label, $currentValue); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Génère un filtre de plage de dates
     */
    private static function renderDateRange($name, $filter) {
        $startValue = get($name . '_start', '');
        $endValue = get($name . '_end', '');
        ?>
        <div class="date-range">
            <input type="date" 
                   name="<?= htmlspecialchars($name) ?>_start"
                   placeholder="Date début"
                   value="<?= htmlspecialchars($startValue) ?>"
                   class="filter-date">
            <span class="date-separator">-</span>
            <input type="date" 
                   name="<?= htmlspecialchars($name) ?>_end"
                   placeholder="Date fin"
                   value="<?= htmlspecialchars($endValue) ?>"
                   class="filter-date">
        </div>
        <?php
    }
    
    /**
     * Génère un filtre checkbox
     */
    private static function renderCheckboxFilter($name, $label, $currentValue) {
        $checked = $currentValue ? 'checked' : '';
        ?>
        <div class="checkbox-filter">
            <input type="checkbox" 
                   name="<?= htmlspecialchars($name) ?>"
                   id="filter-<?= htmlspecialchars($name) ?>"
                   value="1"
                   <?= $checked ?>>
            <label for="filter-<?= htmlspecialchars($name) ?>">
                <?= htmlspecialchars($label) ?>
            </label>
        </div>
        <?php
    }
    
    /**
     * Génère une barre de recherche simple (sans filtres)
     */
    public static function renderSearchOnly($config = []) {
        $placeholder = $config['placeholder'] ?? 'Rechercher...';
        $action = $config['action'] ?? '';
        $name = $config['name'] ?? 'search';
        $showButton = $config['showButton'] ?? true;
        $currentValue = get($name, '');
        ?>
        <form method="GET" action="<?= htmlspecialchars($action) ?>" class="search-form">
            <div class="search-box-simple">
                <input type="text" 
                       name="<?= htmlspecialchars($name) ?>"
                       placeholder="<?= htmlspecialchars($placeholder) ?>"
                       value="<?= htmlspecialchars($currentValue) ?>"
                       class="search-input">
                
                <?php if ($showButton): ?>
                    <button type="submit" class="btn-search">
                        Rechercher
                    </button>
                <?php endif; ?>
            </div>
        </form>
        <?php
    }
    
    /**
     * Affiche les filtres actifs (tags)
     */
    public static function renderActiveFilters($filters = []) {
        $activeFilters = self::getActiveFilters($filters);
        
        if (empty($activeFilters)) {
            return;
        }
        ?>
        <div class="active-filters">
            <span class="active-filters-label">Filtres actifs :</span>
            <?php foreach ($activeFilters as $filter): ?>
                <span class="filter-tag">
                    <?= htmlspecialchars($filter['label']) ?>: 
                    <strong><?= htmlspecialchars($filter['value']) ?></strong>
                    <a href="<?= htmlspecialchars($filter['removeUrl']) ?>" 
                       class="remove-filter"
                       title="Supprimer ce filtre">×</a>
                </span>
            <?php endforeach; ?>
            
            <a href="<?= self::getClearAllUrl() ?>" class="clear-all-filters">
                Tout effacer
            </a>
        </div>
        <?php
    }
    
    /**
     * Vérifie si des filtres sont actifs
     */
    private static function hasActiveFilters() {
        $queryParams = $_GET ?? [];
        unset($queryParams['page']); // Ignorer la pagination
        
        return !empty($queryParams);
    }
    
    /**
     * Récupère les filtres actifs
     */
    private static function getActiveFilters($filterConfigs) {
        $active = [];
        
        foreach ($filterConfigs as $filter) {
            $name = $filter['name'] ?? '';
            $value = get($name, '');
            
            if ($value !== '' && $value !== null) {
                $label = $filter['label'] ?? ucfirst($name);
                
                // Récupérer le label de la valeur si c'est un select
                if (isset($filter['options'][$value])) {
                    $displayValue = $filter['options'][$value];
                } else {
                    $displayValue = $value;
                }
                
                $active[] = [
                    'name' => $name,
                    'label' => $label,
                    'value' => $displayValue,
                    'removeUrl' => self::getRemoveFilterUrl($name)
                ];
            }
        }
        
        return $active;
    }
    
    /**
     * Génère l'URL pour supprimer un filtre spécifique
     */
    private static function getRemoveFilterUrl($filterName) {
        $params = $_GET ?? [];
        unset($params[$filterName]);
        
        $queryString = http_build_query($params);
        $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        return $currentUrl . ($queryString ? '?' . $queryString : '');
    }
    
    /**
     * Génère l'URL pour effacer tous les filtres
     */
    private static function getClearAllUrl() {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    
    /**
     * Génère un tri de colonne (pour les tableaux)
     */
    public static function renderSortableHeader($column, $label, $currentSort = '', $currentOrder = 'asc') {
        $isActive = $currentSort === $column;
        $newOrder = ($isActive && $currentOrder === 'asc') ? 'desc' : 'asc';
        
        $params = $_GET ?? [];
        $params['sort'] = $column;
        $params['order'] = $newOrder;
        
        $url = '?' . http_build_query($params);
        $icon = '';
        
        if ($isActive) {
            $icon = $currentOrder === 'asc' ? '↑' : '↓';
        }
        ?>
        <a href="<?= htmlspecialchars($url) ?>" 
           class="sortable-header <?= $isActive ? 'active' : '' ?>">
            <?= htmlspecialchars($label) ?>
            <?php if ($icon): ?>
                <span class="sort-icon"><?= $icon ?></span>
            <?php endif; ?>
        </a>
        <?php
    }
}
?>