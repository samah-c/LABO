<?php
/**
 * Vue d'export PDF du rapport de publications
 */

$vendorPath = __DIR__ . '/../../../vendor/autoload.php';

if (file_exists($vendorPath)) {
    require_once $vendorPath;
}

/**
 * Classe PDF personnalisée avec en-tête et pied de page
 */
class RapportPublicationsPDF extends TCPDF
{
    private $rapportTitre;
    private $rapportSousTitre;

    public function setRapportTitre($titre, $sousTitre = '')
    {
        $this->rapportTitre = $titre;
        $this->rapportSousTitre = $sousTitre;
    }

    public function Header()
    {
        // Logo (si disponible)
        $logoPath = __DIR__ . '/../../../assets/images/logo/laboratory.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 15, 10, 15, 0, 'PNG');
        }

        // Titre
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(59, 130, 246);
        $this->SetY(15);
        $this->Cell(0, 15, 'Rapport Bibliographique', 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Sous-titre
        if ($this->rapportTitre) {
            $this->SetFont('helvetica', '', 12);
            $this->SetTextColor(100, 100, 100);
            $this->Ln(8);
            $this->Cell(0, 10, $this->rapportTitre, 0, false, 'C');
        }

        if ($this->rapportSousTitre) {
            $this->SetFont('helvetica', 'I', 10);
            $this->Ln(6);
            $this->Cell(0, 8, $this->rapportSousTitre, 0, false, 'C');
        }

        $this->Ln(10);

        // Ligne de séparation
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->Ln(5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);

        // Date de génération
        $this->Cell(0, 10, 'Généré le ' . date('d/m/Y à H:i') . ' - Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

/**
 * Classe de gestion de l'export PDF des rapports de publications
 */
class PublicationsPdfExportView
{
    private array $data;
    private string $type;

    public function __construct(array $data, string $type = 'complet')
    {
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Vérifier si TCPDF est disponible
     */
    public static function isTcpdfAvailable(): bool
    {
        return class_exists('TCPDF');
    }

    /**
     * Générer et télécharger le PDF
     */
    public function generate(): void
    {
        if (!self::isTcpdfAvailable()) {
            $this->exportHtmlFallback();
            return;
        }

        // IMPORTANT: Nettoyer le buffer de sortie avant de générer le PDF
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        // Démarrer un nouveau buffer propre
        ob_start();

        try {
            $pdf = $this->initializePdf();
            $pdf->AddPage();

            $this->renderResume($pdf);
            $this->renderStatistiques($pdf);
            $this->renderGraphiques($pdf);
            $this->renderPublications($pdf);

            $this->outputPdf($pdf);
        } catch (Exception $e) {
            // En cas d'erreur, nettoyer et afficher un message
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erreur</title></head><body>';
            echo '<h1>Erreur lors de la génération du PDF</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><a href="javascript:history.back()">Retour</a></p>';
            echo '</body></html>';
            exit;
        }
    }

    /**
     * Initialiser le PDF
     */
    private function initializePdf(): RapportPublicationsPDF
    {
        $pdf = new RapportPublicationsPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Définir les titres
        $sousTitre = '';
        if (isset($this->data['annee'])) {
            $sousTitre = 'Année ' . $this->data['annee'];
        } elseif (isset($this->data['membre'])) {
            $sousTitre = 'Auteur: ' . ($this->data['membre']['username'] ?? '');
        }
        
        $pdf->setRapportTitre($this->data['titre'] ?? 'Rapport de publications', $sousTitre);

        // Métadonnées
        $pdf->SetCreator('Système de Gestion des Publications - Laboratoire TDW');
        $pdf->SetAuthor('Administration');
        $pdf->SetTitle($this->data['titre'] ?? 'Rapport bibliographique');
        $pdf->SetSubject('Rapport détaillé des publications');
        $pdf->SetKeywords('rapport, publications, recherche, bibliographie');

        // Paramètres
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 40, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        return $pdf;
    }

    /**
     * Rendu du résumé du rapport
     */
    private function renderResume($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(51, 51, 51);
        $pdf->Cell(0, 10, 'Résumé du rapport', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetFillColor(249, 250, 251);

        $infos = [
            ['Type de rapport', $this->getTypeLabel()],
            ['Date de génération', date('d/m/Y à H:i')],
            ['Nombre total de publications', (string)($this->data['total'] ?? 0)]
        ];

        if (isset($this->data['annee'])) {
            $infos[] = ['Année ciblée', $this->data['annee']];
        }

        if (isset($this->data['membre'])) {
            $infos[] = ['Auteur', $this->data['membre']['username'] ?? 'N/A'];
            if (!empty($this->data['membre']['grade'])) {
                $infos[] = ['Grade', $this->data['membre']['grade']];
            }
        }

        foreach ($infos as $index => $info) {
            if ($index % 2 == 0) {
                $pdf->SetFillColor(249, 250, 251);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            $pdf->Cell(80, 10, $info[0], 1, 0, 'L', true);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(100, 10, $info[1], 1, 1, 'L', true);
            $pdf->SetFont('helvetica', '', 11);
        }

        $pdf->Ln(10);
    }

    /**
     * Rendu des statistiques
     */
    private function renderStatistiques($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Statistiques', 0, 1, 'L');
        $pdf->Ln(5);

        // Statistiques par type
        if (isset($this->data['par_type']) && !empty($this->data['par_type'])) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Publications par type', 0, 1, 'L');
            $pdf->Ln(3);

            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(249, 250, 251);

            foreach ($this->data['par_type'] as $type => $count) {
                $pdf->Cell(130, 8, $type, 1, 0, 'L', true);
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(50, 8, (string)$count, 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 11);
            }

            $pdf->Ln(8);
        }

        // Statistiques par domaine
        if (isset($this->data['par_domaine']) && !empty($this->data['par_domaine'])) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Publications par domaine', 0, 1, 'L');
            $pdf->Ln(3);

            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(249, 250, 251);

            foreach ($this->data['par_domaine'] as $domaine => $count) {
                $pdf->Cell(130, 8, $domaine, 1, 0, 'L', true);
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(50, 8, (string)$count, 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 11);
            }

            $pdf->Ln(8);
        }

        // Statistiques par année (pour rapport auteur)
        if (isset($this->data['par_annee']) && !empty($this->data['par_annee'])) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Publications par année', 0, 1, 'L');
            $pdf->Ln(3);

            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetFillColor(249, 250, 251);

            foreach ($this->data['par_annee'] as $annee => $count) {
                $pdf->Cell(130, 8, $annee, 1, 0, 'L', true);
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(50, 8, (string)$count, 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 11);
            }

            $pdf->Ln(8);
        }
    }

    /**
     * Rendu des graphiques (représentation textuelle)
     */
    private function renderGraphiques($pdf): void
    {
        // Note: TCPDF ne supporte pas les graphiques Chart.js
        // On peut créer une représentation visuelle simple avec des barres ASCII
        
        if (isset($this->data['par_type']) && !empty($this->data['par_type'])) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Visualisation par type', 0, 1, 'L');
            $pdf->Ln(3);

            $max = max($this->data['par_type']);
            $pdf->SetFont('helvetica', '', 10);
            
            foreach ($this->data['par_type'] as $type => $count) {
                $percentage = ($max > 0) ? ($count / $max) * 100 : 0;
                $barWidth = ($percentage / 100) * 150;
                
                $pdf->Cell(50, 6, substr($type, 0, 20), 0, 0, 'L');
                
                // Barre de progression
                $pdf->SetFillColor(59, 130, 246);
                $pdf->Cell($barWidth, 6, '', 1, 0, 'L', true);
                $pdf->SetFillColor(229, 231, 235);
                $pdf->Cell(150 - $barWidth, 6, '', 1, 0, 'L', true);
                
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(20, 6, ' ' . $count, 0, 1, 'R');
                $pdf->SetFont('helvetica', '', 10);
            }

            $pdf->Ln(8);
        }
    }

    /**
     * Rendu de la liste des publications
     */
    private function renderPublications($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Liste des publications', 0, 1, 'L');
        $pdf->Ln(5);

        if (empty($this->data['publications'])) {
            $pdf->SetFont('helvetica', 'I', 11);
            $pdf->Cell(0, 10, 'Aucune publication trouvée pour ce rapport', 0, 1, 'C');
            return;
        }

        // Grouper par type
        $groupes = [];
        foreach ($this->data['publications'] as $pub) {
            $type = $pub['type_publication'];
            if (!isset($groupes[$type])) {
                $groupes[$type] = [];
            }
            $groupes[$type][] = $pub;
        }

        // Afficher chaque groupe
        foreach ($groupes as $type => $pubs) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetTextColor(59, 130, 246);
            $pdf->Cell(0, 10, $type . ' (' . count($pubs) . ')', 0, 1, 'L');
            $pdf->SetTextColor(51, 51, 51);
            $pdf->Ln(3);

            foreach ($pubs as $index => $pub) {
                // Vérifier si on a besoin d'une nouvelle page
                if ($pdf->GetY() > 250) {
                    $pdf->AddPage();
                }

                // Numéro
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(10, 6, '[' . ($index + 1) . ']', 0, 0, 'L');

                // Titre
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->MultiCell(0, 6, $pub['titre'] ?? 'N/A', 0, 'L');

                // Auteurs
                if (!empty($pub['auteurs_noms'])) {
                    $pdf->SetFont('helvetica', 'I', 10);
                    $pdf->SetTextColor(75, 85, 99);
                    $pdf->Cell(10, 5, '', 0, 0);
                    $pdf->MultiCell(0, 5, '👥 ' . $pub['auteurs_noms'], 0, 'L');
                    $pdf->SetTextColor(51, 51, 51);
                }

                // Métadonnées
                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetTextColor(107, 114, 128);
                $pdf->Cell(10, 5, '', 0, 0);
                
                $meta = [];
                if (!empty($pub['date_publication'])) {
                    $meta[] = date('Y', strtotime($pub['date_publication']));
                }
                if (!empty($pub['domaine'])) {
                    $meta[] = $pub['domaine'];
                }
                if (!empty($pub['doi'])) {
                    $meta[] = 'DOI: ' . $pub['doi'];
                }
                
                $pdf->Cell(0, 5, implode(' • ', $meta), 0, 1, 'L');
                $pdf->SetTextColor(51, 51, 51);

                // Résumé (limité)
                if (!empty($pub['resume'])) {
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->SetTextColor(75, 85, 99);
                    $pdf->Cell(10, 5, '', 0, 0);
                    
                    $resume = $pub['resume'];
                    if (strlen($resume) > 400) {
                        $resume = substr($resume, 0, 400) . '...';
                    }
                    
                    $pdf->MultiCell(0, 5, $resume, 0, 'L');
                    $pdf->SetTextColor(51, 51, 51);
                }

                // Lien
                if (!empty($pub['lien'])) {
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->SetTextColor(59, 130, 246);
                    $pdf->Cell(10, 5, '', 0, 0);
                    $pdf->Write(5, '🔗 ', '', false, 'L');
                    $pdf->Write(5, 'Accéder à la publication', $pub['lien'], false, 'L');
                    $pdf->SetTextColor(51, 51, 51);
                    $pdf->Ln();
                }

                $pdf->Ln(5);

                // Ligne de séparation
                $pdf->SetDrawColor(229, 231, 235);
                $pdf->Line(15, $pdf->GetY(), $pdf->getPageWidth() - 15, $pdf->GetY());
                $pdf->Ln(5);
            }

            $pdf->Ln(5);
        }
    }

    /**
     * Générer et télécharger le PDF
     */
    private function outputPdf($pdf): void
    {
        $filename = 'rapport_publications_';
        
        if (isset($this->data['annee'])) {
            $filename .= $this->data['annee'] . '_';
        } elseif (isset($this->data['membre'])) {
            $filename .= 'auteur_' . ($this->data['membre']['id'] ?? 'inconnu') . '_';
        }
        
        $filename .= date('Y-m-d') . '.pdf';
        
        // Nettoyer tout buffer restant
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        // Output avec téléchargement forcé
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Export HTML de secours si TCPDF n'est pas disponible
     */
    private function exportHtmlFallback(): void
    {
        // Nettoyer le buffer
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title><?= htmlspecialchars($this->data['titre'] ?? 'Rapport de publications') ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 900px;
                    margin: 40px auto;
                    padding: 20px;
                    background: #f5f5f5;
                }
                .container {
                    background: white;
                    padding: 40px;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #3B82F6;
                    text-align: center;
                    margin-bottom: 10px;
                }
                .alert {
                    background: #FEF3C7;
                    border-left: 4px solid #F59E0B;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .actions {
                    text-align: center;
                    margin: 30px 0;
                }
                button {
                    padding: 12px 24px;
                    margin: 0 10px;
                    font-size: 16px;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 500;
                }
                .btn-primary {
                    background: #3B82F6;
                    color: white;
                }
                .btn-secondary {
                    background: #6B7280;
                    color: white;
                }
                button:hover {
                    opacity: 0.9;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                th, td {
                    padding: 10px;
                    border: 1px solid #ddd;
                    text-align: left;
                }
                th {
                    background: #f9fafb;
                    font-weight: 600;
                }
                .publication-item {
                    margin: 20px 0;
                    padding: 15px;
                    background: #F9FAFB;
                    border-left: 3px solid #3B82F6;
                    border-radius: 4px;
                }
                @media print {
                    body { margin: 0; background: white; }
                    .no-print { display: none; }
                    .container { box-shadow: none; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1> <?= htmlspecialchars($this->data['titre'] ?? 'Rapport de publications') ?></h1>
                
                <?php if (isset($this->data['annee'])): ?>
                    <h3 style="text-align: center; color: #666;">Année <?= $this->data['annee'] ?></h3>
                <?php endif; ?>
                
                <?php if (isset($this->data['membre'])): ?>
                    <h3 style="text-align: center; color: #666;">Auteur: <?= htmlspecialchars($this->data['membre']['username'] ?? '') ?></h3>
                <?php endif; ?>

                <div class="alert no-print">
                    <strong>⚠️ TCPDF non installé</strong><br>
                    Pour générer un vrai PDF, installez TCPDF avec Composer:<br>
                    <code>composer require tecnickcom/tcpdf</code><br><br>
                    En attendant, utilisez le bouton "Imprimer" et choisissez "Enregistrer en PDF" dans votre navigateur.
                </div>

                <div class="actions no-print">
                    <button class="btn-primary" onclick="window.print()"> Imprimer / Sauvegarder en PDF</button>
                    <button class="btn-secondary" onclick="history.back()">✕ Retour</button>
                </div>

                <h3>Statistiques</h3>
                <p><strong>Total de publications:</strong> <?= $this->data['total'] ?? 0 ?></p>

                <?php if (!empty($this->data['par_type'])): ?>
                    <h4>Par type:</h4>
                    <table>
                        <?php foreach ($this->data['par_type'] as $type => $count): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($type) ?></strong></td>
                                <td><?= $count ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>

                <?php if (!empty($this->data['publications'])): ?>
                    <h3>Publications</h3>
                    <?php foreach ($this->data['publications'] as $index => $pub): ?>
                        <div class="publication-item">
                            <strong>[<?= $index + 1 ?>] <?= htmlspecialchars($pub['titre'] ?? 'N/A') ?></strong><br>
                            <?php if (!empty($pub['auteurs_noms'])): ?>
                                <em><?= htmlspecialchars($pub['auteurs_noms']) ?></em><br>
                            <?php endif; ?>
                            <small><?= htmlspecialchars($pub['type_publication'] ?? '') ?> - <?= date('Y', strtotime($pub['date_publication'] ?? 'now')) ?></small>
                            <?php if (!empty($pub['resume'])): ?>
                                <p><?= htmlspecialchars(substr($pub['resume'], 0, 200)) ?>...</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <p style="text-align: center; color: #999; font-size: 12px; margin-top: 40px;">
                    Rapport généré le <?= date('d/m/Y à H:i') ?>
                </p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Obtenir le label du type de rapport
     */
    private function getTypeLabel(): string
    {
        if (isset($this->data['annee'])) {
            return 'Rapport annuel';
        } elseif (isset($this->data['membre'])) {
            return 'Rapport par auteur';
        }
        return 'Rapport complet';
    }

    /**
     * Générer un aperçu HTML simple (optionnel)
     */
    public function renderHtmlPreview(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($this->data['titre'] ?? 'Rapport de publications') ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: #f3f4f6;
                    padding: 20px;
                }
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 40px;
                    text-align: center;
                }
                .header h1 {
                    font-size: 32px;
                    margin-bottom: 10px;
                }
                .header .subtitle {
                    font-size: 18px;
                    opacity: 0.9;
                }
                .actions {
                    background: #f9fafb;
                    padding: 20px 40px;
                    display: flex;
                    gap: 15px;
                    justify-content: center;
                    flex-wrap: wrap;
                }
                .btn {
                    padding: 12px 24px;
                    border: none;
                    border-radius: 8px;
                    font-size: 15px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }
                .btn-primary {
                    background: #3B82F6;
                    color: white;
                }
                .btn-primary:hover {
                    background: #2563EB;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
                }
                .btn-secondary {
                    background: #6B7280;
                    color: white;
                }
                .btn-secondary:hover {
                    background: #4B5563;
                }
                .btn-success {
                    background: #10B981;
                    color: white;
                }
                .btn-success:hover {
                    background: #059669;
                }
                .content {
                    padding: 40px;
                }
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin-bottom: 40px;
                }
                .stat-card {
                    background: #f9fafb;
                    padding: 25px;
                    border-radius: 10px;
                    border-left: 4px solid #3B82F6;
                    text-align: center;
                }
                .stat-number {
                    font-size: 36px;
                    font-weight: 700;
                    color: #3B82F6;
                    margin-bottom: 8px;
                }
                .stat-label {
                    font-size: 14px;
                    color: #6B7280;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .section {
                    margin-bottom: 40px;
                }
                .section h2 {
                    font-size: 24px;
                    color: #1F2937;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 3px solid #3B82F6;
                }
                .publication-group {
                    margin-bottom: 30px;
                }
                .publication-group h3 {
                    font-size: 20px;
                    color: #3B82F6;
                    margin-bottom: 15px;
                    padding-left: 15px;
                    border-left: 4px solid #3B82F6;
                }
                .publication-item {
                    background: #f9fafb;
                    padding: 20px;
                    margin-bottom: 15px;
                    border-radius: 8px;
                    border-left: 3px solid #3B82F6;
                    transition: all 0.3s;
                }
                .publication-item:hover {
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                    transform: translateX(5px);
                }
                .pub-title {
                    font-size: 16px;
                    font-weight: 600;
                    color: #1F2937;
                    margin-bottom: 8px;
                }
                .pub-authors {
                    font-size: 14px;
                    color: #4B5563;
                    margin-bottom: 8px;
                }
                .pub-meta {
                    font-size: 13px;
                    color: #6B7280;
                    display: flex;
                    gap: 15px;
                    flex-wrap: wrap;
                    margin-bottom: 10px;
                }
                .pub-resume {
                    font-size: 14px;
                    line-height: 1.6;
                    color: #4B5563;
                    margin-top: 10px;
                    padding: 12px;
                    background: white;
                    border-radius: 6px;
                }
                .badge {
                    display: inline-block;
                    padding: 4px 10px;
                    border-radius: 12px;
                    font-size: 12px;
                    font-weight: 600;
                }
                .badge-blue { background: #DBEAFE; color: #1E40AF; }
                .badge-green { background: #D1FAE5; color: #065F46; }
                .badge-orange { background: #FED7AA; color: #92400E; }
                @media print {
                    .actions { display: none; }
                    body { background: white; padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1> <?= htmlspecialchars($this->data['titre'] ?? 'Rapport de publications') ?></h1>
                    <div class="subtitle">
                        <?php if (isset($this->data['annee'])): ?>
                            Année <?= $this->data['annee'] ?>
                        <?php elseif (isset($this->data['membre'])): ?>
                            Auteur: <?= htmlspecialchars($this->data['membre']['username'] ?? '') ?>
                        <?php else: ?>
                            Rapport complet
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 15px; font-size: 14px; opacity: 0.8;">
                        Généré le <?= date('d/m/Y à H:i') ?>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn btn-success" onclick="downloadPDF()">
                        Télécharger PDF
                    </button>
                    <button class="btn btn-primary" onclick="window.print()">
                        Imprimer
                    </button>
                    <button class="btn btn-secondary" onclick="downloadCSV()">
                        Exporter CSV
                    </button>
                    <a href="<?= base_url('admin/publications/publications') ?>" class="btn btn-secondary">
                        ← Retour
                    </a>
                </div>

                <div class="content">
                    <!-- Statistiques -->
                    <div class="section">
                        <h2>📊 Statistiques</h2>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?= $this->data['total'] ?? 0 ?></div>
                                <div class="stat-label">Total Publications</div>
                            </div>
                            
                            <?php if (!empty($this->data['par_type'])): ?>
                                <?php foreach (array_slice($this->data['par_type'], 0, 4) as $type => $count): ?>
                                    <div class="stat-card">
                                        <div class="stat-number"><?= $count ?></div>
                                        <div class="stat-label"><?= htmlspecialchars($type) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Publications -->
                    <?php if (!empty($this->data['publications'])): ?>
                        <div class="section">
                            <h2>📄 Publications</h2>
                            
                            <?php
                            // Grouper par type
                            $groupes = [];
                            foreach ($this->data['publications'] as $pub) {
                                $type = $pub['type_publication'];
                                if (!isset($groupes[$type])) {
                                    $groupes[$type] = [];
                                }
                                $groupes[$type][] = $pub;
                            }

                            foreach ($groupes as $type => $pubs):
                            ?>
                                <div class="publication-group">
                                    <h3><?= htmlspecialchars($type) ?> (<?= count($pubs) ?>)</h3>
                                    
                                    <?php foreach ($pubs as $index => $pub): ?>
                                        <div class="publication-item">
                                            <div class="pub-title">
                                                [<?= $index + 1 ?>] <?= htmlspecialchars($pub['titre']) ?>
                                            </div>
                                            
                                            <?php if (!empty($pub['auteurs_noms'])): ?>
                                                <div class="pub-authors">
                                                    👥 <?= htmlspecialchars($pub['auteurs_noms']) ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="pub-meta">
                                                <?php if (!empty($pub['date_publication'])): ?>
                                                    <span class="badge badge-blue">
                                                        📅 <?= date('Y', strtotime($pub['date_publication'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($pub['domaine'])): ?>
                                                    <span class="badge badge-green">
                                                        🏷️ <?= htmlspecialchars($pub['domaine']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($pub['doi'])): ?>
                                                    <span class="badge badge-orange">
                                                        🔗 DOI: <?= htmlspecialchars($pub['doi']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if (!empty($pub['resume'])): ?>
                                                <div class="pub-resume">
                                                    <?= nl2br(htmlspecialchars($pub['resume'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 60px; color: #9CA3AF;">
                            <p style="font-size: 18px;">Aucune publication trouvée</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                function downloadPDF() {
                    const params = new URLSearchParams(window.location.search);
                    params.set('format', 'pdf');
                    window.location.href = '<?= base_url('admin/publications/publications/rapport') ?>?' + params.toString();
                }

                function downloadCSV() {
                    const params = new URLSearchParams(window.location.search);
                    params.set('format', 'csv');
                    window.location.href = '<?= base_url('admin/publications/publications/rapport') ?>?' + params.toString();
                }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}