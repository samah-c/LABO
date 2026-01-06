<?php
/**
 * Vue d'export PDF du rapport d'utilisation des équipements
 */


$vendorPath = __DIR__ . '/../../../vendor/autoload.php';

if (file_exists($vendorPath)) {
    require_once $vendorPath;
} else {
    // Si Composer n'est pas installé, utiliser le fallback HTML
    // Ne pas inclure TCPDF
}
require_once __DIR__ . '/../../../lib/components/FormComponent.php';
/**
 * Classe PDF personnalisée avec en-tête et pied de page
 */
class RapportEquipementsPDF extends TCPDF
{
    private $periodeDebut;
    private $periodeFin;

    public function setPeriode($debut, $fin)
    {
        $this->periodeDebut = $debut;
        $this->periodeFin = $fin;
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
        $this->Cell(0, 15, 'Rapport d\'utilisation des équipements', 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Sous-titre avec période
        if ($this->periodeDebut && $this->periodeFin) {
            $this->SetFont('helvetica', '', 12);
            $this->SetTextColor(100, 100, 100);
            $this->Ln(8);
            $this->Cell(0, 10, 'Période: ' . date('d/m/Y', strtotime($this->periodeDebut)) . ' - ' . date('d/m/Y', strtotime($this->periodeFin)), 0, false, 'C');
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
class EquipementsPdfExportView
{
    private string $dateDebut;
    private string $dateFin;
    private array $stats;
    private array $typesEquipements;
    private array $topEquipements;
    private array $statsParMembre;
    private array $tauxOccupation;

    public function __construct(
        string $dateDebut,
        string $dateFin,
        array $stats,
        array $typesEquipements,
        array $topEquipements,
        array $statsParMembre = [],
        array $tauxOccupation = []
    ) {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->stats = $stats;
        $this->typesEquipements = $typesEquipements;
        $this->topEquipements = $topEquipements;
        $this->statsParMembre = $statsParMembre;
        $this->tauxOccupation = $tauxOccupation;
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

            $this->renderGlobalStats($pdf);
            $this->renderTypeDistribution($pdf);
            $this->renderTopEquipements($pdf);
            $this->renderOccupationRate($pdf);
            $this->renderTopUsers($pdf);
            $this->renderNotes($pdf);

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
    private function initializePdf(): RapportEquipementsPDF
    {
        $pdf = new RapportEquipementsPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Définir la période
        $pdf->setPeriode($this->dateDebut, $this->dateFin);

        // Métadonnées
        $pdf->SetCreator('Système de Gestion des Équipements - Laboratoire TDW');
        $pdf->SetAuthor('Administration');
        $pdf->SetTitle('Rapport d\'utilisation des équipements');
        $pdf->SetSubject('Rapport période du ' . $this->dateDebut . ' au ' . $this->dateFin);
        $pdf->SetKeywords('rapport, équipements, utilisation, statistiques');

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
     * Rendu des statistiques globales
     */
    private function renderGlobalStats($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(51, 51, 51);
        $pdf->Cell(0, 10, 'Statistiques globales', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->SetTextColor(51, 51, 51);

        foreach ($this->stats as $index => $stat) {
            if ($index % 2 == 0) {
                $pdf->SetFillColor(249, 250, 251);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            $pdf->Cell(100, 10, $stat['label'], 1, 0, 'L', true);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(80, 10, (string)$stat['value'], 1, 1, 'R', true);
            $pdf->SetFont('helvetica', '', 11);
        }

        $pdf->Ln(10);
    }

    /**
     * Rendu de la répartition par type
     */
    private function renderTypeDistribution($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Répartition par type', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Cell(120, 8, 'Type d\'équipement', 1, 0, 'L', true);
        $pdf->Cell(60, 8, 'Quantité', 1, 1, 'C', true);

        foreach ($this->typesEquipements as $type) {
            $typeNom = $type['type_equipement'] ?? $type['type'] ?? 'N/A';
            $typeCount = $type['nombre'] ?? $type['count'] ?? 0;
            
            $pdf->Cell(120, 8, $typeNom, 1, 0, 'L');
            $pdf->Cell(60, 8, (string)$typeCount, 1, 1, 'C');
        }

        $pdf->Ln(10);
    }

    /**
     * Rendu du top équipements
     */
    private function renderTopEquipements($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Top 10 des équipements', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Cell(90, 8, 'Équipement', 1, 0, 'L', true);
        $pdf->Cell(45, 8, 'Réservations', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'Heures', 1, 1, 'C', true);

        $topList = array_slice($this->topEquipements, 0, 10);
        foreach ($topList as $equip) {
            $nom = $equip['nom'] ?? 'N/A';
            $reservations = $equip['nb_reservations'] ?? $equip['reservations'] ?? 0;
            $heures = $equip['heures_totales'] ?? $equip['heures'] ?? 0;
            
            $pdf->Cell(90, 8, $nom, 1, 0, 'L');
            $pdf->Cell(45, 8, (string)$reservations, 1, 0, 'C');
            $pdf->Cell(45, 8, number_format($heures, 1) . ' h', 1, 1, 'C');
        }

        $pdf->Ln(10);
    }

    /**
     * Rendu du taux d'occupation
     */
    private function renderOccupationRate($pdf): void
    {
        if (empty($this->tauxOccupation)) {
            return;
        }

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Taux d\'occupation', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Cell(120, 8, 'Équipement', 1, 0, 'L', true);
        $pdf->Cell(60, 8, 'Taux', 1, 1, 'C', true);

        // Trier par taux décroissant
        $tauxSorted = $this->tauxOccupation;
        usort($tauxSorted, function($a, $b) {
            return ($b['taux'] ?? 0) <=> ($a['taux'] ?? 0);
        });

        $topOccupation = array_slice($tauxSorted, 0, 15);
        foreach ($topOccupation as $item) {
            $taux = $item['taux'] ?? 0;
            $pdf->Cell(120, 8, $item['nom'] ?? 'N/A', 1, 0, 'L');
            $pdf->Cell(60, 8, number_format($taux, 1) . '%', 1, 1, 'C');
        }

        $pdf->Ln(10);
    }

    /**
     * Rendu du top utilisateurs
     */
    private function renderTopUsers($pdf): void
    {
        if (empty($this->statsParMembre)) {
            return;
        }

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Top utilisateurs', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Cell(60, 8, 'Utilisateur', 1, 0, 'L', true);
        $pdf->Cell(40, 8, 'Réservations', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Heures', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Moyenne', 1, 1, 'C', true);

        $top = array_slice($this->statsParMembre, 0, 10);
        foreach ($top as $stat) {
            $nbRes = $stat['nb_reservations'] ?? 0;
            $heures = $stat['heures_totales'] ?? 0;
            $moyenne = $nbRes > 0 ? ($heures / $nbRes) : 0;

            $pdf->Cell(60, 8, $stat['username'] ?? 'N/A', 1, 0, 'L');
            $pdf->Cell(40, 8, (string)$nbRes, 1, 0, 'C');
            $pdf->Cell(40, 8, number_format($heures, 1) . 'h', 1, 0, 'C');
            $pdf->Cell(40, 8, number_format($moyenne, 1) . 'h', 1, 1, 'C');
        }

        $pdf->Ln(10);
    }

    /**
     * Rendu des notes
     */
    private function renderNotes($pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Notes', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 5,
            'Ce rapport présente l\'utilisation des équipements du laboratoire pour la période sélectionnée. ' .
            'Les données sont extraites du système de gestion des réservations et reflètent l\'activité réelle du laboratoire.',
            0, 'L'
        );
    }

    /**
     * Générer et télécharger le PDF
     */
    private function outputPdf($pdf): void
    {
        $filename = 'rapport_equipements_' . $this->dateDebut . '_' . $this->dateFin . '.pdf';
        
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
            <title>Rapport d'utilisation - <?= htmlspecialchars($this->dateDebut) ?> / <?= htmlspecialchars($this->dateFin) ?></title>
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
                .period {
                    text-align: center;
                    color: #666;
                    margin-bottom: 30px;
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
                @media print {
                    body { margin: 0; background: white; }
                    .no-print { display: none; }
                    .container { box-shadow: none; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>📄 Rapport d'utilisation des équipements</h1>
                <p class="period">
                    Période: <?= htmlspecialchars($this->dateDebut) ?> - <?= htmlspecialchars($this->dateFin) ?>
                </p>

                <div class="alert no-print">
                    <strong>⚠️ TCPDF non installé</strong><br>
                    Pour générer un vrai PDF, installez TCPDF avec Composer:<br>
                    <code>composer require tecnickcom/tcpdf</code><br><br>
                    En attendant, utilisez le bouton "Imprimer" et choisissez "Enregistrer en PDF" dans votre navigateur.
                </div>

                <div class="actions no-print">
                    <button class="btn-primary" onclick="window.print()">🖨️ Imprimer / Sauvegarder en PDF</button>
                    <button class="btn-secondary" onclick="window.close()">✕ Fermer</button>
                </div>

                <!-- Contenu du rapport ici -->
                <h2>Statistiques globales</h2>
                <table border="1" cellpadding="10" style="width:100%; border-collapse: collapse;">
                    <?php foreach ($this->stats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['label']) ?></td>
                            <td style="text-align:right"><strong><?= htmlspecialchars($stat['value']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <!-- Ajoutez le reste du contenu ici -->
                
                <p style="text-align: center; color: #999; font-size: 12px; margin-top: 40px;">
                    Rapport généré le <?= date('d/m/Y à H:i') ?>
                </p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}