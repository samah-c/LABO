<?php
require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Détails - ' . ($publication['titre'] ?? 'Publication'),
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/admin/publications-handler.js')
    ]
]);
?>

<div class="container">

<?php ViewComponents::renderBreadcrumbs([
    ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
    ['label' => 'Publications', 'url' => base_url('admin/publications/publications')],
    ['label' => 'Détails']
]); ?>

<!-- HEADER -->
<div class="detail-header">
    <div class="title-row">
        <h1><?= e($publication['titre']) ?></h1>
        <button class="btn-delete" onclick="deleteItem(<?= $publication['id'] ?>)">
            Supprimer
        </button>
    </div>

    <div class="detail-actions">
        <a href="<?= base_url('admin/publications/publications') ?>" class="btn-secondary">Retour</a>
        
        <?php if (($publication['statut_validation'] ?? 'en_attente') === 'en_attente'): ?>
            <button class="btn-success" onclick="validerPublication(<?= $publication['id'] ?>)">
                Valider
            </button>
            <button class="btn-warning" onclick="rejeterPublication(<?= $publication['id'] ?>)">
                Rejeter
            </button>
        <?php endif; ?>
        
        <button class="btn-primary" onclick="editItem(<?= $publication['id'] ?>)">Modifier</button>
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
                <span class="badge blue"><?= e($publication['type_publication']) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Date</span>
                <span><?= date('d/m/Y', strtotime($publication['date_publication'])) ?></span>
            </div>

            <?php if (!empty($publication['domaine'])): ?>
            <div class="info-row">
                <span class="info-label">Domaine</span>
                <span class="badge gray"><?= e($publication['domaine']) ?></span>
            </div>
            <?php endif; ?>

            <div class="info-row">
                <span class="info-label">Statut</span>
                <?php
                $statut = $publication['statut_validation'] ?? 'en_attente';
                $badgeClass = $statut === 'valide' ? 'success' : ($statut === 'rejete' ? 'danger' : 'orange');
                $statutText = $statut === 'valide' ? 'Validé' : ($statut === 'rejete' ? 'Rejeté' : 'En attente');
                ?>
                <span class="badge <?= $badgeClass ?>"><?= $statutText ?></span>
            </div>
        </div>
    </div>

    <!-- FULL WIDTH ROW 2: Résumé -->
    <div class="detail-card">
        <h2>Résumé</h2>
        <div class="resume-content">
            <?= nl2br(e($publication['resume'])) ?>
        </div>
    </div>

    <!-- TWO COLUMN ROW: Auteurs & Projet -->
    <div class="two-column-grid">
        <div class="detail-card">
            <h2>Auteurs</h2>

            <?php foreach ($auteurs as $auteur): ?>
            <div class="author-item">
                <div class="author-avatar">
                    <?= strtoupper($auteur['username'][0]) ?>
                </div>
                <div>
                    <div class="author-name"><?= e($auteur['username']) ?></div>
                    <div class="author-team"><?= e($auteur['equipe_nom']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($projet): ?>
        <div class="detail-card">
            <h2>Projet associé</h2>
            <strong><?= e($projet['titre']) ?></strong>
            <div class="badge gray" style="margin-top:10px;">
                <?= e($projet['thematique']) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>
</div>

<!-- Modale d'édition -->
<?php ViewComponents::renderModal([
    'id' => 'publication-modal',
    'title' => 'Modifier la publication',
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

.btn-success {
    background:#10B981;
    color:white;
    border:none;
    padding:10px 20px;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
}

.btn-success:hover {
    background:#059669;
}

.btn-warning {
    background:#F59E0B;
    color:white;
    border:none;
    padding:10px 20px;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
}

.btn-warning:hover {
    background:#D97706;
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

.author-item {
    display:flex;
    align-items:center;
    gap:12px;
    padding:10px;
    background:#f9fafb;
    border-radius:8px;
    margin-bottom:10px;
}

.author-avatar {
    width:40px;
    height:40px;
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:white;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
}

.author-name {
    font-weight:600;
}

.author-team {
    font-size:13px;
    color:#6b7280;
}

.resume-content {
    line-height:1.7;
    color:#374151;
}

.badge {
    padding:4px 12px;
    border-radius:12px;
    font-size:13px;
    color:white;
    display:inline-block;
}

.blue { background:#3b82f6; }
.gray { background:#6b7280; }
.orange { background:#f59e0b; }
.success { background:#10B981; }
.danger { background:#EF4444; }

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
// Attendre que le handler soit chargé
function editItem(id){
    console.log('editItem appelé avec ID:', id);
    console.log('window.publications existe?', !!window.publications);
    
    if (window.publications) {
        window.publications.edit(id);
    } else {
        console.error('Handler publications non chargé, attente...');
        // Réessayer après un court délai
        setTimeout(() => {
            if (window.publications) {
                window.publications.edit(id);
            } else {
                console.error('Handler toujours non chargé, redirection...');
                location.href = "<?= base_url('admin/publications/publications') ?>";
            }
        }, 100);
    }
}

function deleteItem(id){
    if (window.publications) {
        window.publications.delete(id);
    } else {
        alert("Erreur: Handler non initialisé");
    }
}

function validerPublication(id){
    if (window.publications) {
        window.publications.valider(id);
    } else {
        alert("Erreur: Handler non initialisé");
    }
}

function rejeterPublication(id){
    if (window.publications) {
        window.publications.rejeter(id);
    } else {
        alert("Erreur: Handler non initialisé");
    }
}

// Vérifier que le script est bien chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page détail chargée');
    console.log('Handler publications:', window.publications);
});
</script>

<?php ViewComponents::renderFooter(); ?>