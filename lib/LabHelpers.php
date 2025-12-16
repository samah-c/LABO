<?php
/**
 * LabHelpers.php - Fonctions utilitaires spécifiques au laboratoire
 */

require_once __DIR__ . '/Utils.php';

class LabHelpers {
    
    // ========================================
    // GESTION DES MEMBRES
    // ========================================
    
    /**
     * Formater le nom complet d'un membre
     */
    public static function formatMembreName($membre) {
        $parts = [];
        
        if (!empty($membre['grade'])) {
            $parts[] = $membre['grade'];
        }
        
        if (!empty($membre['prenom'])) {
            $parts[] = $membre['prenom'];
        }
        
        if (!empty($membre['nom'])) {
            $parts[] = strtoupper($membre['nom']);
        }
        
        return implode(' ', $parts);
    }
    
    /**
     * Obtenir le badge de grade
     */
    public static function getGradeBadge($grade) {
        $badges = [
            'Professeur' => '<span class="badge badge-purple">Professeur</span>',
            'Maître de conférences A' => '<span class="badge badge-blue">MCA</span>',
            'Maître de conférences B' => '<span class="badge badge-blue">MCB</span>',
            'Maître assistant A' => '<span class="badge badge-green">MAA</span>',
            'Maître assistant B' => '<span class="badge badge-green">MAB</span>',
            'Doctorant' => '<span class="badge badge-orange">Doctorant</span>',
            'Étudiant' => '<span class="badge badge-gray">Étudiant</span>'
        ];
        
        return $badges[$grade] ?? '<span class="badge badge-gray">' . htmlspecialchars($grade) . '</span>';
    }
    
    /**
     * Obtenir le badge de poste
     */
    public static function getPosteBadge($poste) {
        $badges = [
            'Directeur' => '<span class="badge badge-red">Directeur</span>',
            'Directeur adjoint' => '<span class="badge badge-red">Dir. Adjoint</span>',
            'Chef d\'équipe' => '<span class="badge badge-purple">Chef d\'équipe</span>',
            'Responsable scientifique' => '<span class="badge badge-blue">Resp. Scientifique</span>',
            'Chercheur' => '<span class="badge badge-green">Chercheur</span>'
        ];
        
        return $badges[$poste] ?? '<span class="badge badge-gray">' . htmlspecialchars($poste) . '</span>';
    }
    
    // ========================================
    // GESTION DES PROJETS
    // ========================================
    
    /**
     * Obtenir le badge de status de projet
     */
    public static function getProjetStatusBadge($status) {
    $badges = [
    'en_cours' => '<span class="badge badge-success">En cours</span>',
    'termine' => '<span class="badge badge-secondary">Terminé</span>',
    'soumis' => '<span class="badge badge-warning">Soumis</span>',
    'approuvé' => '<span class="badge badge-info">Approuvé</span>',
    'rejeté' => '<span class="badge badge-danger">Rejeté</span>'
];
        
        return $badges[$status] ?? '<span class="badge badge-gray">' . htmlspecialchars($status) . '</span>';
    }
    
    /**
     * Calculer la progression d'un projet
     */
   public static function calculateProjectProgress($dateDebut, $dateFin)
{
    if (empty($dateDebut)) {
        return 0;
    }

    // Convert start date
    $start = strtotime($dateDebut);

    // If end date is null or empty → use current date
    if (empty($dateFin)) {
        $end = time();  // maintenant
    } else {
        $end = strtotime($dateFin);
    }

    if ($end <= $start) {
        return 0;
    }

    $total = $end - $start;
    $elapsed = time() - $start;

    $progress = ($elapsed / $total) * 100;

    // Clamp between 0 and 100
    return max(0, min(100, round($progress)));
}

    
    // ========================================
    // GESTION DES PUBLICATIONS
    // ========================================
    
    /**
     * Formater la référence bibliographique
     */
    public static function formatCitation($publication) {
        $parts = [];
        
        // Auteurs
        if (!empty($publication['auteurs'])) {
            $auteurs = is_array($publication['auteurs']) 
                ? $publication['auteurs'] 
                : [$publication['auteurs']];
            $parts[] = implode(', ', $auteurs);
        }
        
        // Année
        if (!empty($publication['annee'])) {
            $parts[] = "({$publication['annee']})";
        }
        
        // Titre
        if (!empty($publication['titre'])) {
            $parts[] = "<em>{$publication['titre']}</em>";
        }
        
        // Journal/Conférence
        if (!empty($publication['journal'])) {
            $parts[] = $publication['journal'];
        }
        
        // DOI
        if (!empty($publication['doi'])) {
            $parts[] = "DOI: {$publication['doi']}";
        }
        
        return implode('. ', $parts) . '.';
    }
    
    /**
     * Obtenir le badge de type de publication
     */
    public static function getPublicationTypeBadge($type) {
        $badges = [
            'Article' => '<span class="badge badge-blue">Article</span>',
            'Conférence' => '<span class="badge badge-green">Conférence</span>',
            'Thèse' => '<span class="badge badge-purple">Thèse</span>',
            'Rapport' => '<span class="badge badge-orange">Rapport</span>',
            'Poster' => '<span class="badge badge-yellow">Poster</span>',
            'Chapitre' => '<span class="badge badge-teal">Chapitre</span>'
        ];
        
        return $badges[$type] ?? '<span class="badge badge-gray">' . htmlspecialchars($type) . '</span>';
    }
    
    // ========================================
    // GESTION DES ÉQUIPEMENTS
    // ========================================
    
    /**
     * Obtenir le badge d'état d'équipement
     */
    public static function getEquipementEtatBadge($etat) {
        $badges = [
            'libre' => '<span class="badge badge-success">✓ Libre</span>',
            'réservé' => '<span class="badge badge-warning">⏰ Réservé</span>',
            'en maintenance' => '<span class="badge badge-danger">🔧 Maintenance</span>',
            'hors service' => '<span class="badge badge-dark">✗ Hors service</span>'
        ];
        
        return $badges[$etat] ?? '<span class="badge badge-gray">' . htmlspecialchars($etat) . '</span>';
    }
    
    /**
     * Calculer le taux d'utilisation
     */
    public static function calculateUsageRate($reservations, $totalHours) {
        if ($totalHours == 0) return 0;
        
        $usedHours = 0;
        foreach ($reservations as $reservation) {
            $start = strtotime($reservation['date_debut']);
            $end = strtotime($reservation['date_fin']);
            $usedHours += ($end - $start) / 3600;
        }
        
        return round(($usedHours / $totalHours) * 100);
    }
    
    // ========================================
    // GESTION DES ÉVÉNEMENTS
    // ========================================
    
    /**
     * Obtenir le badge de type d'événement
     */
    public static function getEvenementTypeBadge($type) {
        $badges = [
            'Conférence' => '<span class="badge badge-blue">🎤 Conférence</span>',
            'Atelier' => '<span class="badge badge-green">🛠️ Atelier</span>',
            'Séminaire' => '<span class="badge badge-purple">📚 Séminaire</span>',
            'Soutenance' => '<span class="badge badge-orange">🎓 Soutenance</span>',
            'Journée d\'étude' => '<span class="badge badge-teal">📖 Journée d\'étude</span>'
        ];
        
        return $badges[$type] ?? '<span class="badge badge-gray">' . htmlspecialchars($type) . '</span>';
    }
    
    /**
     * Vérifier si un événement est à venir
     */
    public static function isUpcoming($dateEvenement) {
        return strtotime($dateEvenement) > time();
    }
    
    /**
     * Obtenir le temps restant avant un événement
     */
    public static function getTimeUntilEvent($dateEvenement) {
        $timestamp = strtotime($dateEvenement);
        $diff = $timestamp - time();
        
        if ($diff < 0) {
            return 'Événement passé';
        }
        
        $days = floor($diff / 86400);
        $hours = floor(($diff % 86400) / 3600);
        
        if ($days > 0) {
            return "Dans $days jour" . ($days > 1 ? 's' : '');
        } elseif ($hours > 0) {
            return "Dans $hours heure" . ($hours > 1 ? 's' : '');
        } else {
            return "Aujourd'hui";
        }
    }
    
    // ========================================
    // STATISTIQUES
    // ========================================
    
    /**
     * Calculer des statistiques de base
     */
    public static function calculateStats($data, $key) {
        if (empty($data)) {
            return [
                'total' => 0,
                'moyenne' => 0,
                'min' => 0,
                'max' => 0
            ];
        }
        
        $values = array_column($data, $key);
        
        return [
            'total' => count($values),
            'moyenne' => round(array_sum($values) / count($values), 2),
            'min' => min($values),
            'max' => max($values)
        ];
    }
    
    /**
     * Générer un graphique simple en ASCII
     */
    public static function generateAsciiChart($data, $maxWidth = 50) {
        if (empty($data)) return '';
        
        $max = max($data);
        $chart = '';
        
        foreach ($data as $label => $value) {
            $barWidth = round(($value / $max) * $maxWidth);
            $bar = str_repeat('█', $barWidth);
            $chart .= sprintf("%-20s │ %s %d\n", $label, $bar, $value);
        }
        
        return $chart;
    }
    
    // ========================================
    // NOTIFICATIONS ET ALERTES
    // ========================================
    
    /**
     * Créer une notification dans la session
     */
    public static function notify($message, $type = 'info') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }
        
        $_SESSION['notifications'][] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
    }
    
    /**
     * Récupérer et effacer les notifications
     */
    public static function getNotifications() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $notifications = $_SESSION['notifications'] ?? [];
        unset($_SESSION['notifications']);
        
        return $notifications;
    }
    
    // ========================================
    // RECHERCHE ET FILTRAGE
    // ========================================
    
    /**
     * Recherche dans un tableau multidimensionnel
     */
    public static function searchInArray($array, $searchTerm, $fields = []) {
        $results = [];
        $searchTerm = mb_strtolower($searchTerm);
        
        foreach ($array as $item) {
            foreach ($fields as $field) {
                if (isset($item[$field]) && 
                    mb_strpos(mb_strtolower($item[$field]), $searchTerm) !== false) {
                    $results[] = $item;
                    break;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Appliquer des filtres sur un tableau
     */
    public static function applyFilters($array, $filters) {
        foreach ($filters as $field => $value) {
            if ($value !== '' && $value !== null) {
                $array = array_filter($array, function($item) use ($field, $value) {
                    return isset($item[$field]) && $item[$field] == $value;
                });
            }
        }
        
        return array_values($array);
    }
    
    // ========================================
    // EXPORT ET GÉNÉRATION
    // ========================================
    
    /**
     * Exporter des données en CSV
     */
    public static function exportToCsv($data, $filename, $headers = []) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes
        if (!empty($headers)) {
            fputcsv($output, $headers, ';');
        } elseif (!empty($data)) {
            fputcsv($output, array_keys($data[0]), ';');
        }
        
        // Données
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Générer un QR Code (URL vers API externe)
     */
    public static function generateQrCodeUrl($data, $size = 200) {
        $encoded = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}";
    }

    public static function getStatusBadge($status) {
        $badges = [
            'en_cours' => '<span class="badge badge-success">En cours</span>',
            'terminé' => '<span class="badge badge-secondary">Terminé</span>',
            'termine' => '<span class="badge badge-secondary">Terminé</span>',
            'soumis' => '<span class="badge badge-warning">Soumis</span>',
            'approuvé' => '<span class="badge badge-info">Approuvé</span>',
            'rejeté' => '<span class="badge badge-danger">Rejeté</span>',
            'en_attente' => '<span class="badge badge-warning">En attente</span>',
            'validé' => '<span class="badge badge-success">Validé</span>',
            'confirmée' => '<span class="badge badge-success">Confirmée</span>',
            'annulée' => '<span class="badge badge-danger">Annulée</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge badge-gray">' . htmlspecialchars($status) . '</span>';
    }
}

?>