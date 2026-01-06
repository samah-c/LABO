<?php
/**
 * FormComponent.php - Composant pour la gestion des formulaires
 * À placer dans : /TDW_project/lib/components/FormComponent.php
 */

require_once __DIR__ . '/../LabHelpers.php';

class FormComponent {
    
    /**
     * Génère un formulaire avec validation
     * 
     * @param array $config Configuration du formulaire
     *   - action: string - URL de soumission
     *   - method: string - Méthode HTTP (POST/GET)
     *   - fields: array - Champs du formulaire
     *   - submitText: string - Texte du bouton submit
     *   - cancelUrl: string|null - URL d'annulation
     *   - formClass: string - Classes CSS additionnelles
     */
    public static function render($config) {
        $action = $config['action'] ?? '';
        $method = $config['method'] ?? 'POST';
        $fields = $config['fields'] ?? [];
        $submitText = $config['submitText'] ?? 'Enregistrer';
        $cancelUrl = $config['cancelUrl'] ?? null;
        $formClass = $config['formClass'] ?? 'dynamic-form';
        ?>
        <form action="<?= htmlspecialchars($action) ?>" 
              method="<?= htmlspecialchars($method) ?>" 
              class="<?= htmlspecialchars($formClass) ?>"
              <?= isset($config['enctype']) ? 'enctype="' . htmlspecialchars($config['enctype']) . '"' : '' ?>>
            <?= csrf_field() ?>
            
            <?php foreach ($fields as $field): ?>
                <?php self::renderField($field); ?>
            <?php endforeach; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <?= htmlspecialchars($submitText) ?>
                </button>
                
                <?php if ($cancelUrl): ?>
                    <a href="<?= htmlspecialchars($cancelUrl) ?>" class="btn-secondary">
                        Annuler
                    </a>
                <?php endif; ?>
            </div>
        </form>
        <?php
    }
    
    /**
     * Génère un champ de formulaire
     * 
     * @param array $field Configuration du champ
     *   - type: string - Type de champ (text, select, textarea, etc.)
     *   - name: string - Nom du champ
     *   - label: string - Label du champ
     *   - value: mixed - Valeur par défaut
     *   - required: bool - Champ obligatoire
     *   - options: array - Options pour select
     *   - placeholder: string - Placeholder
     *   - attributes: array - Attributs HTML additionnels
     */
    public static function renderField($field) {
        $type = $field['type'] ?? 'text';
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';
        $value = $field['value'] ?? old($name);
        $required = $field['required'] ?? false;
        $options = $field['options'] ?? [];
        $placeholder = $field['placeholder'] ?? '';
        $attributes = $field['attributes'] ?? [];
        
        ?>
        <div class="form-group">
            <?php if ($label): ?>
                <label for="<?= htmlspecialchars($name) ?>">
                    <?= htmlspecialchars($label) ?>
                    <?php if ($required): ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <?php
            switch ($type) {
                case 'select':
                    self::renderSelect($name, $options, $value, $required, $placeholder, $attributes);
                    break;
                    
                case 'textarea':
                    self::renderTextarea($name, $value, $required, $placeholder, $attributes);
                    break;
                    
                case 'checkbox':
                    self::renderCheckbox($name, $value, $label, $attributes);
                    break;
                    
                case 'radio':
                    self::renderRadio($name, $options, $value, $attributes);
                    break;
                    
                case 'file':
                    self::renderFile($name, $required, $attributes);
                    break;
                    
                default:
                    self::renderInput($type, $name, $value, $required, $placeholder, $attributes);
                    break;
            }
            ?>
            
            <?= show_error($name) ?>
        </div>
        <?php
    }
    
    /**
     * Génère un champ input
     */
    private static function renderInput($type, $name, $value, $required, $placeholder, $attributes) {
        $attrs = self::buildAttributes($attributes);
        ?>
        <input type="<?= htmlspecialchars($type) ?>" 
               name="<?= htmlspecialchars($name) ?>" 
               id="<?= htmlspecialchars($name) ?>"
               value="<?= htmlspecialchars($value) ?>"
               placeholder="<?= htmlspecialchars($placeholder) ?>"
               <?= $required ? 'required' : '' ?>
               <?= $attrs ?>>
        <?php
    }
    
    /**
     * Génère un champ select
     */
    private static function renderSelect($name, $options, $value, $required, $placeholder, $attributes) {
        $attrs = self::buildAttributes($attributes);
        ?>
        <select name="<?= htmlspecialchars($name) ?>" 
                id="<?= htmlspecialchars($name) ?>"
                <?= $required ? 'required' : '' ?>
                <?= $attrs ?>>
            <?= select_options($options, $value, $placeholder) ?>
        </select>
        <?php
    }
    
    /**
     * Génère un champ textarea
     */
    private static function renderTextarea($name, $value, $required, $placeholder, $attributes) {
        $attrs = self::buildAttributes($attributes);
        $rows = $attributes['rows'] ?? 4;
        ?>
        <textarea name="<?= htmlspecialchars($name) ?>" 
                  id="<?= htmlspecialchars($name) ?>"
                  placeholder="<?= htmlspecialchars($placeholder) ?>"
                  rows="<?= htmlspecialchars($rows) ?>"
                  <?= $required ? 'required' : '' ?>
                  <?= $attrs ?>><?= htmlspecialchars($value) ?></textarea>
        <?php
    }
    
    /**
     * Génère un champ checkbox
     */
    private static function renderCheckbox($name, $value, $label, $attributes) {
        $attrs = self::buildAttributes($attributes);
        $checked = $value ? 'checked' : '';
        ?>
        <div class="checkbox-wrapper">
            <input type="checkbox" 
                   name="<?= htmlspecialchars($name) ?>" 
                   id="<?= htmlspecialchars($name) ?>"
                   value="1"
                   <?= $checked ?>
                   <?= $attrs ?>>
            <?php if ($label): ?>
                <label for="<?= htmlspecialchars($name) ?>" class="checkbox-label">
                    <?= htmlspecialchars($label) ?>
                </label>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Génère des boutons radio
     */
    private static function renderRadio($name, $options, $value, $attributes) {
        $attrs = self::buildAttributes($attributes);
        ?>
        <div class="radio-group">
            <?php foreach ($options as $optValue => $optLabel): ?>
                <div class="radio-wrapper">
                    <input type="radio" 
                           name="<?= htmlspecialchars($name) ?>" 
                           id="<?= htmlspecialchars($name . '_' . $optValue) ?>"
                           value="<?= htmlspecialchars($optValue) ?>"
                           <?= $value == $optValue ? 'checked' : '' ?>
                           <?= $attrs ?>>
                    <label for="<?= htmlspecialchars($name . '_' . $optValue) ?>" class="radio-label">
                        <?= htmlspecialchars($optLabel) ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Génère un champ file
     */
    private static function renderFile($name, $required, $attributes) {
        $attrs = self::buildAttributes($attributes);
        $accept = $attributes['accept'] ?? '';
        ?>
        <input type="file" 
               name="<?= htmlspecialchars($name) ?>" 
               id="<?= htmlspecialchars($name) ?>"
               <?= $required ? 'required' : '' ?>
               <?= $accept ? 'accept="' . htmlspecialchars($accept) . '"' : '' ?>
               <?= $attrs ?>>
        <?php
    }
    
    /**
     * Construit la chaîne d'attributs HTML
     */
    private static function buildAttributes($attributes) {
        $exclude = ['rows', 'accept', 'class'];
        $attrs = [];
        
        foreach ($attributes as $key => $val) {
            if (!in_array($key, $exclude)) {
                $attrs[] = htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
            }
        }
        
        return implode(' ', $attrs);
    }
    
    /**
     * Génère un groupe de champs (pour organiser le formulaire)
     */
    public static function renderFieldGroup($title, $fields) {
        ?>
        <fieldset class="form-fieldset">
            <?php if ($title): ?>
                <legend><?= htmlspecialchars($title) ?></legend>
            <?php endif; ?>
            
            <?php foreach ($fields as $field): ?>
                <?php self::renderField($field); ?>
            <?php endforeach; ?>
        </fieldset>
        <?php
    }
}
?>