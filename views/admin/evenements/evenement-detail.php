<?php
require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Détails - ' . ($evenement['titre'] ?? 'Événement'),
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/admin/evenements-handler.js')
    ]
]);
?>

<div class="container">

<?php ViewComponents::renderBreadcrumbs([
    ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
    ['label' => 'Événements', 'url' => base_url('admin/evenements/evenements')],
    ['label' => 'Détails']
]); ?>

<!-- HEADER -->
<div class="detail-header">
    <div class="title-row">
        <h1><?= e($evenement['titre']) ?></h1>
        <button class="btn-delete" onclick="deleteItem(<?= $evenement['id'] ?>)">
            Supprimer
        </button>
    </div>

    <div class="detail-actions">
        <a href="<?= base_url('admin/evenements/evenements') ?>" class="btn-secondary">Retour</a>
        <button class="btn-primary" onclick="editItem(<?= $evenement['id'] ?>)">Modifier</button>
    </div>
</div>

<!-- CONTENT -->
<div class="detail-layout">

    <!-- FULL WIDTH ROW 1: Informations -->
    <div class="detail-card">
        <h2>Informations</h2>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Type</span>
                <?php
                $types = [
                    'conference' => 'Conférence',
                    'atelier' => 'Atelier',
                    'seminaire' => 'Séminaire',
                    'soutenance' => 'Soutenance',
                    'autre' => 'Autre'
                ];
                $typeLabel = $types[$evenement['type_evenement']] ?? $evenement['type_evenement'];
                ?>
                <span class="badge blue"><?= e($typeLabel) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Date</span>
                <span><?= date('d/m/Y à H:i', strtotime($evenement['date_evenement'])) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Lieu</span>
                <span><?= e($evenement['lieu']) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Statut</span>
                <?php
                $isUpcoming = strtotime($evenement['date_evenement']) > time();
                $badgeClass = $isUpcoming ? 'info' : 'secondary';
                $statutText = $isUpcoming ? 'À venir' : 'Terminé';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= $statutText ?></span>
            </div>
        </div>
    </div>

    <!-- FULL WIDTH ROW 2: Description -->
    <div class="detail-card">
        <h2>Description</h2>
        <div class="description-content">
            <?= nl2br(e($evenement['description'])) ?>
        </div>
    </div>

    <!-- TWO COLUMN ROW: Organisateur & Inscription -->
    <div class="two-column-grid">
        <?php if ($organisateur): ?>
        <div class="detail-card">
            <h2>Organisateur</h2>
            <div class="organisateur-info">
                <div class="organisateur-avatar">
                    <?= strtoupper($organisateur['username'][0]) ?>
                </div>
                <div>
                    <div class="organisateur-name"><?= e($organisateur['username']) ?></div>
                    <?php if ($organisateur['grade']): ?>
                        <div class="organisateur-grade"><?= e($organisateur['grade']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($evenement['lien_inscription'])): ?>
        <div class="detail-card">
            <h2>Inscription</h2>
            <a href="<?= e($evenement['lien_inscription']) ?>" 
               target="_blank" 
               class="inscription-link">
                Lien d'inscription
            </a>
        </div>
        <?php endif; ?>
    </div>

</div>
</div>

<!-- Modale d'édition -->
<?php ViewComponents::renderModal([
    'id' => 'evenement-modal',
    'title' => 'Modifier l\'événement',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<style>
.detail-header {
    background:white;
    padding:28px;
    border-radius:12px;
    margin-bottom:30px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.title-row {
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.btn-delete {
    background:#dc2626;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
}

.detail-actions {
    margin-top:16px;
    display:flex;
    gap:12px;
}

.detail-layout {
    display:flex;
    flex-direction:column;
    gap:24px;
}

.two-column-grid {
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:24px;
}

.detail-card {
    background:white;
    padding:24px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.detail-card h2 {
    margin-bottom:16px;
    border-bottom:2px solid #eee;
    padding-bottom:8px;
}

.info-grid {
    display:grid;
    grid-template-columns: repeat(4, 1fr);
    gap:24px;
}

.info-row {
    display:flex;
    flex-direction:column;
    gap:8px;
}

.info-label {
    color:#6b7280;
    font-weight:600;
}

.organisateur-info {
    display:flex;
    align-items:center;
    gap:12px;
    padding:10px;
    background:#f9fafb;
    border-radius:8px;
}

.organisateur-avatar {
    width:50px;
    height:50px;
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:white;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    font-size:20px;
}

.organisateur-name {
    font-weight:600;
    font-size:16px;
}

.organisateur-grade {
    font-size:13px;
    color:#6b7280;
}

.description-content {
    line-height:1.7;
    color:#374151;
}

.inscription-link {
    display:inline-block;
    padding:12px 24px;
    background:#3B82F6;
    color:white;
    text-decoration:none;
    border-radius:8px;
    font-weight:600;
    transition:background 0.3s;
}

.inscription-link:hover {
    background:#2563EB;
}

.badge {
    padding:4px 12px;
    border-radius:12px;
    font-size:13px;
    color:white;
    display:inline-block;
}

.blue { background:#3b82f6; }
.info { background:#3b82f6; }
.secondary { background:#6b7280; }

@media(max-width:768px){
    .two-column-grid {
        grid-template-columns:1fr;
    }
    
    .info-grid {
        grid-template-columns:repeat(2, 1fr);
        gap:16px;
    }
}

@media(max-width:480px){
    .info-grid {
        grid-template-columns:1fr;
    }
}
</style>

<script>
function editItem(id){
    if (window.evenements) {
        window.evenements.edit(id);
    } else {
        location.href = "<?= base_url('admin/evenements/evenements') ?>";
    }
}

function deleteItem(id){
    if (window.evenements) {
        window.evenements.delete(id);
    } else {
        alert("Erreur: Handler non initialisé");
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page détail événement chargée');
    console.log('Handler evenements:', window.evenements);
});
</script>

<?php ViewComponents::renderFooter(['role' => 'admin']); ?>