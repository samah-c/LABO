<?php
/**
 * Vue d'export PDF du rapport de projet
 */

$vendorPath = __DIR__ . '/../../../vendor/autoload.php';

if (file_exists($vendorPath)) {
    require_once $vendorPath;
}

/**
 * Classe PDF personnalisée avec en-tête et pied de page
 */
class RapportProjetPDF extends TCPDF
{
    private $projetTitre;

    public function setProjetTitre($titre)
    {
        $this->projetTitre = $titre;
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
        $this->SetTextColor(91, 127, 255);
        $this->SetY(15);
        $this->Cell(0, 15, 'Rapport de Projet', 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Sous-titre avec nom du projet
        if ($this->projetTitre) {
            $this->SetFont('helvetica', '', 12);
            $this->SetTextColor(100, 100, 100);
            $this->Ln(8);
            $this->Cell(0, 10, $this->projetTitre, 0, false, 'C');
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
 * Classe de gestion de l'export PDF
 */
class ProjetPdfExportView
{
    private array $projet;
    private ?array $responsable;
    private array $membres;
    private array $publications;

    public function __construct(
        array $projet,
        ?array $responsable,
        array $membres,
        array $publications
    ) {
        $this->projet = $projet;
        $this->responsable = $responsable;
        $this->membres = $membres;
        $this->publications = $publications;
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

            $this->renderGeneralInfo($pdf);
            $this->renderDescription($pdf);
            $this->renderStatistics($pdf);
            $this->renderTeamMembers($pdf);
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
    private function initializePdf(): RapportProjetPDF
    {
        $pdf = new RapportProjetPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Définir le titre du projet
        $pdf->setProjetTitre($this->projet['titre'] ?? 'Projet');

        // Métadonnées
        $pdf->SetCreator('Système de Gestion des Projets - Laboratoire TDW');
        $pdf->SetAuthor('Administration');
        $pdf->SetTitle('Rapport de projet - ' . ($this->projet['titre'] ?? ''));
        $pdf->SetSubject('Rapport détaillé du projet');
        $pdf->SetKeywords('rapport, projet, recherche, laboratoire');

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
     * Rendu des informations générales
     */
    private function renderGeneralInfo($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(51, 51, 51);
        $pdf->Cell(0, 10, 'Informations Générales', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->SetTextColor(51, 51, 51);

        $infos = [
            ['Titre du projet', $this->projet['titre'] ?? 'N/A'],
            ['Thématique', $this->projet['thematique'] ?? 'N/A'],
            ['Statut', $this->formatStatus($this->projet['status'] ?? 'en_cours')],
            ['Responsable scientifique', $this->responsable['username'] ?? 'Non assigné'],
            ['Date de début', $this->formatDate($this->projet['date_debut'] ?? null)],
            ['Date de fin', $this->formatDate($this->projet['date_fin'] ?? null, 'Non définie')],
        ];

        if (!empty($this->projet['budget'])) {
            $infos[] = ['Budget', number_format($this->projet['budget'], 2, ',', ' ') . ' DZD'];
        }

        if (!empty($this->projet['source_financement'])) {
            $infos[] = ['Source de financement', $this->projet['source_financement']];
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
     * Rendu de la description
     */
    private function renderDescription($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Description du projet', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        
        $description = $this->projet['descriptif'] ?? $this->projet['description'] ?? 'Aucune description disponible';
        $pdf->MultiCell(0, 5, $description, 0, 'L');

        $pdf->Ln(10);
    }

    /**
     * Rendu des statistiques
     */
    private function renderStatistics($pdf): void
    {
        $progression = $this->calculateProjectProgress(
            $this->projet['date_debut'] ?? null, 
            $this->projet['date_fin'] ?? null
        );

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Statistiques', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetFillColor(249, 250, 251);

        $stats = [
            ['Nombre de membres', count($this->membres)],
            ['Nombre de publications', count($this->publications)],
            ['Progression', $progression . '%']
        ];

        foreach ($stats as $stat) {
            $pdf->Cell(100, 10, $stat[0], 1, 0, 'L', true);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(80, 10, (string)$stat[1], 1, 1, 'C', true);
            $pdf->SetFont('helvetica', '', 11);
        }

        $pdf->Ln(10);
    }

    /**
     * Rendu de l'équipe
     */
    private function renderTeamMembers($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Équipe du projet', 0, 1, 'L');
        $pdf->Ln(5);

        if (!empty($this->membres)) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetFillColor(249, 250, 251);
            $pdf->Cell(80, 8, 'Nom', 1, 0, 'L', true);
            $pdf->Cell(50, 8, 'Grade', 1, 0, 'L', true);
            $pdf->Cell(50, 8, 'Rôle', 1, 1, 'L', true);

            foreach ($this->membres as $membre) {
                $pdf->Cell(80, 8, $membre['username'] ?? 'N/A', 1, 0, 'L');
                $pdf->Cell(50, 8, $membre['grade'] ?? '-', 1, 0, 'L');
                $pdf->Cell(50, 8, $membre['role'] ?? 'Participant', 1, 1, 'L');
            }
        } else {
            $pdf->SetFont('helvetica', 'I', 11);
            $pdf->Cell(0, 10, 'Aucun membre assigné', 0, 1, 'C');
        }

        $pdf->Ln(10);
    }

    /**
     * Rendu des publications
     */
    private function renderPublications($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Publications liées', 0, 1, 'L');
        $pdf->Ln(5);

        if (!empty($this->publications)) {
            foreach ($this->publications as $index => $pub) {
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(10, 8, '[' . ($index + 1) . ']', 0, 0, 'L');
                
                $pdf->SetFont('helvetica', '', 11);
                $titre = $pub['titre'] ?? 'N/A';
                $pdf->MultiCell(0, 8, $titre, 0, 'L');
                
                $pdf->SetFont('helvetica', 'I', 9);
                $pdf->SetTextColor(100, 100, 100);
                
                $meta = [];
                if (!empty($pub['type_publication'])) {
                    $meta[] = $pub['type_publication'];
                }
                if (!empty($pub['date_publication'])) {
                    $meta[] = $this->formatDate($pub['date_publication']);
                }
                if (!empty($pub['doi'])) {
                    $meta[] = 'DOI: ' . $pub['doi'];
                }
                
                $pdf->Cell(0, 6, implode(' • ', $meta), 0, 1, 'L');
                $pdf->SetTextColor(51, 51, 51);
                $pdf->Ln(3);
            }
        } else {
            $pdf->SetFont('helvetica', 'I', 11);
            $pdf->Cell(0, 10, 'Aucune publication liée à ce projet', 0, 1, 'C');
        }

        $pdf->Ln(10);
    }

    /**
     * Générer et télécharger le PDF
     */
    private function outputPdf($pdf): void
    {
        $filename = 'rapport_projet_' . ($this->projet['id'] ?? 'projet') . '_' . date('Y-m-d') . '.pdf';
        
        // Nettoyer tout buffer restant
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        // Output avec téléchargement forcé
        $pdf->Output($filename, 'D');
        exit; // Important: arrêter l'exécution après l'output
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
            <title>Rapport de projet - <?= htmlspecialchars($this->projet['titre'] ?? 'Projet') ?></title>
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
                    color: #5B7FFF;
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
                    background: #5B7FFF;
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
                @media print {
                    body { margin: 0; background: white; }
                    .no-print { display: none; }
                    .container { box-shadow: none; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>📄 Rapport de Projet</h1>
                <h2 style="text-align: center; color: #666; margin-bottom: 30px;">
                    <?= htmlspecialchars($this->projet['titre'] ?? 'Projet') ?>
                </h2>

                <div class="alert no-print">
                    <strong>⚠️ TCPDF non installé</strong><br>
                    Pour générer un vrai PDF, installez TCPDF avec Composer:<br>
                    <code>composer require tecnickcom/tcpdf</code><br><br>
                    En attendant, utilisez le bouton "Imprimer" et choisissez "Enregistrer en PDF" dans votre navigateur.
                </div>

                <div class="actions no-print">
                    <button class="btn-primary" onclick="window.print()">🖨️ Imprimer / Sauvegarder en PDF</button>
                    <button class="btn-secondary" onclick="history.back()">✕ Retour</button>
                </div>

                <h3>Informations Générales</h3>
                <table>
                    <tr>
                        <td><strong>Titre</strong></td>
                        <td><?= htmlspecialchars($this->projet['titre'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Thématique</strong></td>
                        <td><?= htmlspecialchars($this->projet['thematique'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Statut</strong></td>
                        <td><?= htmlspecialchars($this->formatStatus($this->projet['status'] ?? 'en_cours')) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Responsable</strong></td>
                        <td><?= htmlspecialchars($this->responsable['username'] ?? 'Non assigné') ?></td>
                    </tr>
                </table>

                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($this->projet['descriptif'] ?? $this->projet['description'] ?? 'Aucune description')) ?></p>

                <?php if (!empty($this->membres)): ?>
                <h3>Équipe</h3>
                <ul>
                    <?php foreach ($this->membres as $membre): ?>
                        <li><?= htmlspecialchars($membre['username'] ?? 'N/A') ?> - <?= htmlspecialchars($membre['role'] ?? 'Participant') ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (!empty($this->publications)): ?>
                <h3>Publications</h3>
                <ol>
                    <?php foreach ($this->publications as $pub): ?>
                        <li><?= htmlspecialchars($pub['titre'] ?? 'N/A') ?></li>
                    <?php endforeach; ?>
                </ol>
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
     * Formater une date
     */
    private function formatDate($date, $default = '-'): string
    {
        if (empty($date)) {
            return $default;
        }
        
        try {
            return date('d/m/Y', strtotime($date));
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Formater le statut
     */
    private function formatStatus($status): string
    {
        $statuses = [
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'soumis' => 'Soumis',
            'approuvé' => 'Approuvé',
            'rejeté' => 'Rejeté'
        ];
        
        return $statuses[$status] ?? ucfirst($status);
    }

    /**
     * Calculer la progression du projet
     */
    private function calculateProjectProgress($dateDebut, $dateFin): int
    {
        if (empty($dateDebut)) {
            return 0;
        }

        $debut = strtotime($dateDebut);
        $now = time();

        if (empty($dateFin)) {
            return 50; // Pas de date de fin, on considère 50%
        }

        $fin = strtotime($dateFin);

        if ($now >= $fin) {
            return 100;
        }

        if ($now <= $debut) {
            return 0;
        }

        $total = $fin - $debut;
        $ecoule = $now - $debut;

        return min(100, max(0, round(($ecoule / $total) * 100)));
    }
}