<?php
/**
 * PublicationController.php - Contrôleur dédié pour la gestion des publications
 * À créer dans : controllers/member/PublicationController.php
 */

require_once __DIR__ . '/../../models/PublicationModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/ProjetModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../auth/AuthController.php';

class PublicationController {
    private $publicationModel;
    private $membreModel;
    private $projetModel;
    private $membreId;
    private $membre;
    
    public function __construct() {
        AuthController::checkSessionTimeout();
        
        $this->publicationModel = new PublicationModel();
        $this->membreModel = new MembreModel();
        $this->projetModel = new ProjetModel();
        
        // Récupérer l'ID du membre connecté
        $userId = session('user_id');
        $this->membre = $this->membreModel->getByUserId($userId);
        $this->membreId = $this->membre['id'] ?? null;
    }
    
    /**
     * Créer une nouvelle publication
     */
    public function createPublication() {
        AuthController::requireMembre();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        error_log("=== CREATE PUBLICATION START ===");
        error_log("POST data: " . print_r($_POST, true));
        
        try {
            // Récupérer et valider les données
            $data = $this->validatePublicationData();
            
            if (!$data['valid']) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $data['error']
                ], 400);
                return;
            }
            
            $publicationData = $data['data'];
            
            // Insérer la publication dans la base de données
            $publicationId = $this->insertPublication($publicationData);
            
            if (!$publicationId) {
                throw new Exception("Erreur lors de l'insertion de la publication");
            }
            
            // Ajouter le membre actuel comme auteur principal
            $this->publicationModel->addAuteur($publicationId, $this->membreId, 1);
            
            // Ajouter les co-auteurs si présents
            if (!empty($publicationData['co_auteurs'])) {
                $this->addCoAuteurs($publicationId, $publicationData['co_auteurs']);
            }
            
            error_log("=== CREATE PUBLICATION SUCCESS ===");
            error_log("Publication ID: " . $publicationId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Publication créée avec succès. En attente de validation.',
                'publication_id' => $publicationId
            ]);
            
        } catch (Exception $e) {
            error_log("=== CREATE PUBLICATION ERROR ===");
            error_log("Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $this->jsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Valider les données de la publication
     */
    private function validatePublicationData() {
        $titre = trim(post('titre', ''));
        $typePublication = trim(post('type_publication', ''));
        $resume = trim(post('resume', ''));
        $datePublication = trim(post('date_publication', ''));
        $domaine = trim(post('domaine', ''));
        
        // Champs optionnels
        $doi = trim(post('doi', ''));
        $lien = trim(post('lien', ''));
        $lienTelechargement = trim(post('lien_telechargement', ''));
        $projetId = post('projet_id', null);
        $coAuteurs = post('co_auteurs', []);
        
        // Validation des champs obligatoires
        if (empty($titre)) {
            return ['valid' => false, 'error' => 'Le titre est obligatoire'];
        }
        
        if (strlen($titre) < 10) {
            return ['valid' => false, 'error' => 'Le titre doit contenir au moins 10 caractères'];
        }
        
        if (empty($typePublication)) {
            return ['valid' => false, 'error' => 'Le type de publication est obligatoire'];
        }
        
        $typesValides = ['article', 'rapport', 'these', 'communication', 'poster', 'autre'];
        if (!in_array($typePublication, $typesValides)) {
            return ['valid' => false, 'error' => 'Type de publication invalide'];
        }
        
        if (empty($resume)) {
            return ['valid' => false, 'error' => 'Le résumé est obligatoire'];
        }
        
        if (strlen($resume) < 50) {
            return ['valid' => false, 'error' => 'Le résumé doit contenir au moins 50 caractères'];
        }
        
        if (empty($datePublication)) {
            return ['valid' => false, 'error' => 'La date de publication est obligatoire'];
        }
        
        // Valider la date
        $date = DateTime::createFromFormat('Y-m-d', $datePublication);
        if (!$date) {
            return ['valid' => false, 'error' => 'Format de date invalide'];
        }
        
        // Vérifier que la date n'est pas dans le futur
        if ($date > new DateTime()) {
            return ['valid' => false, 'error' => 'La date de publication ne peut pas être dans le futur'];
        }
        
        if (empty($domaine)) {
            return ['valid' => false, 'error' => 'Le domaine est obligatoire'];
        }
        
        // Valider le lien si présent
        if (!empty($lien) && !filter_var($lien, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Lien invalide'];
        }
        
        // Valider le lien de téléchargement si présent
        if (!empty($lienTelechargement) && !filter_var($lienTelechargement, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Lien de téléchargement invalide'];
        }
        
        // Valider le DOI si présent
        if (!empty($doi) && !preg_match('/^10\.\d{4,}\/\S+$/', $doi)) {
            return ['valid' => false, 'error' => 'Format DOI invalide (doit commencer par 10.)'];
        }
        
        return [
            'valid' => true,
            'data' => [
                'titre' => $titre,
                'type_publication' => $typePublication,
                'resume' => $resume,
                'date_publication' => $datePublication,
                'domaine' => $domaine,
                'doi' => $doi,
                'lien' => $lien,
                'lien_telechargement' => $lienTelechargement,
                'projet_id' => $projetId ?: null,
                'co_auteurs' => is_array($coAuteurs) ? $coAuteurs : []
            ]
        ];
    }
    
    /**
     * Insérer la publication dans la base de données
     */
    private function insertPublication($data) {
        $db = $this->publicationModel->getConnection();
        
        $sql = "INSERT INTO Publication (
            titre, 
            type_publication, 
            resume, 
            date_publication, 
            domaine, 
            doi, 
            lien, 
            lien_telechargement,
            projet_id, 
            statut_validation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')";
        
        $stmt = $db->prepare($sql);
        
        $result = $stmt->execute([
            $data['titre'],
            $data['type_publication'],
            $data['resume'],
            $data['date_publication'],
            $data['domaine'],
            $data['doi'] ?: null,
            $data['lien'] ?: null,
            $data['lien_telechargement'] ?: null,
            $data['projet_id']
        ]);
        
        if (!$result) {
            error_log("SQL Error: " . print_r($stmt->errorInfo(), true));
            return false;
        }
        
        return $db->lastInsertId();
    }
    
    /**
     * Ajouter les co-auteurs
     */
    private function addCoAuteurs($publicationId, $coAuteurs) {
        $ordre = 2; // L'auteur principal est à l'ordre 1
        
        foreach ($coAuteurs as $membreId) {
            if (!empty($membreId) && is_numeric($membreId)) {
                $this->publicationModel->addAuteur($publicationId, $membreId, $ordre);
                $ordre++;
            }
        }
    }
    
    /**
     * Mettre à jour une publication
     */
    public function updatePublication($id) {
        AuthController::requireMembre();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        try {
            // Vérifier que la publication existe et appartient au membre
            $publication = $this->publicationModel->getById($id);
            
            if (!$publication) {
                $this->jsonResponse(['success' => false, 'message' => 'Publication non trouvée'], 404);
                return;
            }
            
            // Vérifier que le membre est un auteur de la publication
            $auteurs = $this->publicationModel->getAuteurs($id);
            $isAuteur = false;
            foreach ($auteurs as $auteur) {
                if ($auteur['id'] == $this->membreId) {
                    $isAuteur = true;
                    break;
                }
            }
            
            if (!$isAuteur) {
                $this->jsonResponse(['success' => false, 'message' => 'Non autorisé'], 403);
                return;
            }
            
            // Valider les données
            $data = $this->validatePublicationData();
            
            if (!$data['valid']) {
                $this->jsonResponse(['success' => false, 'message' => $data['error']], 400);
                return;
            }
            
            $publicationData = $data['data'];
            unset($publicationData['co_auteurs']); // Ne pas mettre à jour les auteurs ici
            
            // Mettre à jour la publication
            $db = $this->publicationModel->getConnection();
            
            $sql = "UPDATE Publication SET 
                titre = ?, 
                type_publication = ?, 
                resume = ?, 
                date_publication = ?, 
                domaine = ?, 
                doi = ?, 
                lien = ?, 
                lien_telechargement = ?,
                projet_id = ?
                WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            
            $result = $stmt->execute([
                $publicationData['titre'],
                $publicationData['type_publication'],
                $publicationData['resume'],
                $publicationData['date_publication'],
                $publicationData['domaine'],
                $publicationData['doi'] ?: null,
                $publicationData['lien'] ?: null,
                $publicationData['lien_telechargement'] ?: null,
                $publicationData['projet_id'],
                $id
            ]);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Publication mise à jour avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de la mise à jour");
            }
            
        } catch (Exception $e) {
            error_log("Update publication error: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
/**
 * Supprimer une publication (soft delete)
 */
public function deletePublication($id) {
    // Prevent any output before JSON
    ob_clean();
    
    AuthController::requireMembre();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
        return;
    }
    
    try {
        // Récupérer la publication
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication) {
            $this->jsonResponse(['success' => false, 'message' => 'Publication non trouvée'], 404);
            return;
        }
        
        // Vérifier que le membre est auteur
        $auteurs = $this->publicationModel->getAuteurs($id);
        $isAuteur = false;
        $isPrimaryAuthor = false;
        
        foreach ($auteurs as $auteur) {
            if ($auteur['id'] == $this->membreId) {
                $isAuteur = true;
                if (isset($auteur['ordre_auteur']) && $auteur['ordre_auteur'] == 1) {
                    $isPrimaryAuthor = true;
                }
                break;
            }
        }
        
        if (!$isAuteur) {
            $this->jsonResponse(['success' => false, 'message' => 'Non autorisé à supprimer cette publication'], 403);
            return;
        }
        
        // Seul l'auteur principal ou un admin peut supprimer
        if (!$isPrimaryAuthor && session('role') !== 'admin') {
            $this->jsonResponse(['success' => false, 'message' => 'Seul l\'auteur principal peut supprimer cette publication'], 403);
            return;
        }
        
        // Supprimer la publication (hard delete)
        $db = $this->publicationModel->getConnection();
        
        // D'abord supprimer les relations dans Publication_Auteur
        $stmt = $db->prepare("DELETE FROM Publication_Auteur WHERE publication_id = ?");
        $stmt->execute([$id]);
        
        // Ensuite supprimer la publication
        $stmt = $db->prepare("DELETE FROM Publication WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Publication supprimée avec succès'
            ]);
        } else {
            throw new Exception("Erreur lors de la suppression");
        }
        
    } catch (Exception $e) {
        error_log("Delete publication error: " . $e->getMessage());
        $this->jsonResponse([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la suppression'
        ], 500);
    }
}

/**
 * Envoyer une réponse JSON
 */
private function jsonResponse($data, $statusCode = 200) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
    
    /**
     * Récupérer les membres pour le formulaire
     */
    public function getMembres() {
        AuthController::requireMembre();
        
        try {
            $membres = $this->membreModel->getAll();
            
            // Filtrer le membre actuel
            $membres = array_filter($membres, function($m) {
                return $m['id'] != $this->membreId;
            });
            
            $this->jsonResponse([
                'success' => true,
                'membres' => array_values($membres)
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des membres'
            ], 500);
        }
    }
    
    /**
     * Récupérer les projets pour le formulaire
     */
    public function getProjets() {
        AuthController::requireMembre();
        
        try {
            $projets = $this->projetModel->getByMembre($this->membreId);
            
            $this->jsonResponse([
                'success' => true,
                'projets' => $projets
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des projets'
            ], 500);
        }
    }

}
?>