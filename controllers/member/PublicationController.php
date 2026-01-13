<?php

class PublicationController {
    private $publicationModel;
    private $membreModel;
    private $projetModel;
    private $membreId;
    private $membre;
    
    public function __construct() {
        // Désactiver l'affichage des erreurs AVANT tout
        ini_set('display_errors', '0');
        error_reporting(E_ALL);
        
        // Démarrer le buffer de sortie
        if (!ob_get_level()) {
            ob_start();
        }
        
        try {
            AuthController::checkSessionTimeout();
            
            $this->publicationModel = new PublicationModel();
            $this->membreModel = new MembreModel();
            $this->projetModel = new ProjetModel();
            
            $userId = session('user_id');
            
            if ($userId) {
                $this->membre = $this->membreModel->getByUserId($userId);
                $this->membreId = $this->membre['id'] ?? null;
            } else {
                $this->membre = null;
                $this->membreId = null;
            }
            
        } catch (Exception $e) {
            error_log("PublicationController init error: " . $e->getMessage());
            
            if ($this->isAjaxRequest()) {
                $this->cleanJsonResponse([
                    'success' => false,
                    'message' => 'Erreur d\'initialisation'
                ], 500);
            }
        }
    }
    
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    private function cleanJsonResponse($data, $statusCode = 200) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    public function getMembres() {
        ini_set('display_errors', '0');
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        ob_start();
        
        try {
            AuthController::requireMembre();
            
            $membres = $this->membreModel->getAll();
            
            if ($this->membreId) {
                $membres = array_filter($membres, function($m) {
                    return $m['id'] != $this->membreId;
                });
            }
            
            $this->cleanJsonResponse([
                'success' => true,
                'membres' => array_values($membres)
            ]);
            
        } catch (Exception $e) {
            error_log("GET MEMBRES ERROR: " . $e->getMessage());
            
            $this->cleanJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des membres',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getProjets() {
        ini_set('display_errors', '0');
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        ob_start();
        
        try {
            AuthController::requireMembre();
            
            if (!$this->membreId) {
                throw new Exception("Membre ID non trouvé");
            }
            
            $projets = $this->projetModel->getByMembre($this->membreId);
            
            $this->cleanJsonResponse([
                'success' => true,
                'projets' => $projets ?? []
            ]);
            
        } catch (Exception $e) {
            error_log("GET PROJETS ERROR: " . $e->getMessage());
            
            $this->cleanJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des projets',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function createPublication() {
        ini_set('display_errors', '0');
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        ob_start();
        
        AuthController::requireMembre();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->cleanJsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        try {
            $data = $this->validatePublicationData();
            
            if (!$data['valid']) {
                $this->cleanJsonResponse([
                    'success' => false,
                    'message' => $data['error']
                ], 400);
                return;
            }
            
            $publicationData = $data['data'];
            
            $publicationId = $this->insertPublication($publicationData);
            
            if (!$publicationId) {
                throw new Exception("Erreur lors de l'insertion de la publication");
            }
            
            $this->publicationModel->addAuteur($publicationId, $this->membreId, 1);
            
            if (!empty($publicationData['co_auteurs'])) {
                $this->addCoAuteurs($publicationId, $publicationData['co_auteurs']);
            }
            
            $this->cleanJsonResponse([
                'success' => true,
                'message' => 'Publication créée avec succès. En attente de validation.',
                'publication_id' => $publicationId
            ]);
            
        } catch (Exception $e) {
            error_log("CREATE PUBLICATION ERROR: " . $e->getMessage());
            
            $this->cleanJsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🔧 CORRECTION: Récupérer une publication pour modification
     */
    public function getPublication($id) {
        // Désactiver les erreurs
        error_reporting(0);
        ini_set('display_errors', '0');
        
        // Nettoyer TOUS les buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Démarrer un buffer propre
        ob_start();
        
        try {
            AuthController::requireMembre();
            
            // Vérifier que l'ID est valide
            if (!is_numeric($id) || $id <= 0) {
                $this->cleanJsonResponse([
                    'success' => false, 
                    'message' => 'ID de publication invalide'
                ], 400);
                return;
            }
            
            // Récupérer la publication
            $publication = $this->publicationModel->getById($id);
            
            if (!$publication) {
                $this->cleanJsonResponse([
                    'success' => false, 
                    'message' => 'Publication non trouvée'
                ], 404);
                return;
            }
            
            // Récupérer les auteurs
            $auteurs = $this->publicationModel->getAuteurs($id);
            
            // Vérifier que le membre est auteur
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
                $this->cleanJsonResponse([
                    'success' => false, 
                    'message' => 'Non autorisé à consulter cette publication'
                ], 403);
                return;
            }
            
            // Récupérer les co-auteurs (sauf l'auteur principal)
            $coAuteurs = [];
            foreach ($auteurs as $auteur) {
                if ($auteur['ordre_auteur'] != 1) {
                    $coAuteurs[] = (string)$auteur['id'];
                }
            }
            
            $publication['co_auteurs'] = $coAuteurs;
            $publication['is_primary_author'] = $isPrimaryAuthor;
            
            // Log pour debug
            error_log("Publication loaded successfully: ID=$id, Primary Author: " . ($isPrimaryAuthor ? 'Yes' : 'No'));
            
            $this->cleanJsonResponse([
                'success' => true,
                'publication' => $publication
            ]);
            
        } catch (Exception $e) {
            error_log("GET PUBLICATION ERROR: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $this->cleanJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la publication'
            ], 500);
        }
    }
    
    public function updatePublication($id) {
        ini_set('display_errors', '0');
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        ob_start();
        
        AuthController::requireMembre();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->cleanJsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        try {
            $publication = $this->publicationModel->getById($id);
            
            if (!$publication) {
                $this->cleanJsonResponse(['success' => false, 'message' => 'Publication non trouvée'], 404);
                return;
            }
            
            $auteurs = $this->publicationModel->getAuteurs($id);
            $isPrimaryAuthor = false;
            
            foreach ($auteurs as $auteur) {
                if ($auteur['id'] == $this->membreId && isset($auteur['ordre_auteur']) && $auteur['ordre_auteur'] == 1) {
                    $isPrimaryAuthor = true;
                    break;
                }
            }
            
            if (!$isPrimaryAuthor) {
                $this->cleanJsonResponse(['success' => false, 'message' => 'Seul l\'auteur principal peut modifier cette publication'], 403);
                return;
            }
            
            $data = $this->validatePublicationData();
            
            if (!$data['valid']) {
                $this->cleanJsonResponse([
                    'success' => false,
                    'message' => $data['error']
                ], 400);
                return;
            }
            
            $publicationData = $data['data'];
            
            $this->updatePublicationData($id, $publicationData);
            
            $db = $this->publicationModel->getConnection();
            $stmt = $db->prepare("DELETE FROM Publication_Auteur WHERE publication_id = ? AND ordre_auteur != 1");
            $stmt->execute([$id]);
            
            if (!empty($publicationData['co_auteurs'])) {
                $this->addCoAuteurs($id, $publicationData['co_auteurs']);
            }
            
            if ($publication['statut_validation'] === 'rejete') {
                $stmt = $db->prepare("UPDATE Publication SET statut_validation = 'en_attente' WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            $this->cleanJsonResponse([
                'success' => true,
                'message' => 'Publication modifiée avec succès'
            ]);
            
        } catch (Exception $e) {
            error_log("UPDATE PUBLICATION ERROR: " . $e->getMessage());
            
            $this->cleanJsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function deletePublication($id) {
        error_reporting(0);
        ini_set('display_errors', '0');
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        ob_start();
        
        AuthController::requireMembre();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->cleanJsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        try {
            $publication = $this->publicationModel->getById($id);
            
            if (!$publication) {
                $this->cleanJsonResponse(['success' => false, 'message' => 'Publication non trouvée'], 404);
                return;
            }
            
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
                $this->cleanJsonResponse(['success' => false, 'message' => 'Non autorisé à supprimer cette publication'], 403);
                return;
            }
            
            if (!$isPrimaryAuthor) {
                $this->cleanJsonResponse(['success' => false, 'message' => 'Seul l\'auteur principal peut supprimer cette publication'], 403);
                return;
            }
            
            $db = $this->publicationModel->getConnection();
            
            $stmt = $db->prepare("DELETE FROM Publication_Auteur WHERE publication_id = ?");
            $stmt->execute([$id]);
            
            $stmt = $db->prepare("DELETE FROM Publication WHERE id = ?");
            
            if ($stmt->execute([$id])) {
                error_log("Publication #$id supprimée par " . session('username'));
                
                $this->cleanJsonResponse([
                    'success' => true,
                    'message' => 'Publication supprimée avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de la suppression");
            }
            
        } catch (Exception $e) {
            error_log("DELETE PUBLICATION ERROR: " . $e->getMessage());
            
            $this->cleanJsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression'
            ], 500);
        }
    }
    
    private function validatePublicationData() {
        $titre = trim(post('titre', ''));
        $typePublication = trim(post('type_publication', ''));
        $resume = trim(post('resume', ''));
        $datePublication = trim(post('date_publication', ''));
        $domaine = trim(post('domaine', ''));
        
        $doi = trim(post('doi', ''));
        $lien = trim(post('lien', ''));
        $lienTelechargement = trim(post('lien_telechargement', ''));
        $projetId = post('projet_id', null);
        $coAuteurs = post('co_auteurs', []);
        
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
        
        $date = DateTime::createFromFormat('Y-m-d', $datePublication);
        if (!$date) {
            return ['valid' => false, 'error' => 'Format de date invalide'];
        }
        
        if ($date > new DateTime()) {
            return ['valid' => false, 'error' => 'La date de publication ne peut pas être dans le futur'];
        }
        
        if (empty($domaine)) {
            return ['valid' => false, 'error' => 'Le domaine est obligatoire'];
        }
        
        if (!empty($lien) && !filter_var($lien, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Lien invalide'];
        }
        
        if (!empty($lienTelechargement) && !filter_var($lienTelechargement, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Lien de téléchargement invalide'];
        }
        
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
    
    private function addCoAuteurs($publicationId, $coAuteurs) {
        $ordre = 2;
        
        foreach ($coAuteurs as $membreId) {
            if (!empty($membreId) && is_numeric($membreId)) {
                $this->publicationModel->addAuteur($publicationId, $membreId, $ordre);
                $ordre++;
            }
        }
    }
    
    private function updatePublicationData($id, $data) {
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
            $data['titre'],
            $data['type_publication'],
            $data['resume'],
            $data['date_publication'],
            $data['domaine'],
            $data['doi'] ?: null,
            $data['lien'] ?: null,
            $data['lien_telechargement'] ?: null,
            $data['projet_id'],
            $id
        ]);
        
        if (!$result) {
            error_log("SQL Error: " . print_r($stmt->errorInfo(), true));
            throw new Exception("Erreur lors de la mise à jour de la publication");
        }
        
        return true;
    }
}
?>