<?php
/**
 * Contrôleur pour les pages publiques
 * Gère l'affichage des pages accessibles aux visiteurs
 */

require_once __DIR__ . '/../../models/ProjetModel.php';
require_once __DIR__ . '/../../models/PublicationModel.php';
require_once __DIR__ . '/../../models/EquipeModel.php';
require_once __DIR__ . '/../../models/EquipementModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/EvenementModel.php';
require_once __DIR__ . '/../../models/ActualiteModel.php';
require_once __DIR__ . '/../../models/PartenaireModel.php';
require_once __DIR__ . '/../../models/Model.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';
require_once __DIR__ . '/../../views/visitor/OrganigrammeView.php';
require_once __DIR__ . '/../../views/visitor/HomeView.php';
require_once __DIR__ . '/../../views/visitor/ContactView.php';
require_once __DIR__ . '/../../views/visitor/publications/PublicationsListView.php';
require_once __DIR__ . '/../../views/visitor/publications/PublicationDetailView.php';
require_once __DIR__ . '/../../views/visitor/projets/ProjetsListView.php';
require_once __DIR__ . '/../../views/visitor/projets/ProjetDetailView.php';
require_once __DIR__ . '/../../views/visitor/equipements/EquipementsView.php';
require_once __DIR__ . '/../../views/visitor/equipements/EquipementDetailView.php';
require_once __DIR__ . '/../../views/visitor/membres/MembresView.php';
require_once __DIR__ . '/../../views/visitor/membres/MembreDetailView.php';
require_once __DIR__ . '/../../views/visitor/evenements/EvenementsView.php';
require_once __DIR__ . '/../../views/visitor/evenements/EvenementDetailView.php';
require_once __DIR__ . '/../../views/visitor/actualites/ActualitesView.php';
require_once __DIR__ . '/../../views/visitor/actualites/ActualiteDetailView.php';


class VisiteurController {
    private $projetModel;
    private $publicationModel;
    private $equipeModel;
    private $equipementModel;
    private $membreModel;
    private $evenementModel;
    private $actualiteModel;
    private $partenaireModel;
    
    public function __construct() {
        $this->projetModel = new ProjetModel();
        $this->publicationModel = new PublicationModel();
        $this->equipeModel = new EquipeModel();
        $this->membreModel = new MembreModel();
        $this->evenementModel = new EvenementModel();
        $this->equipementModel = new EquipementModel();
        $this->actualiteModel = new ActualiteModel();
        $this->partenaireModel = new PartenaireModel();
    }
    
    /**
     * Page d'accueil publique
     */
    public function index() {
        // Initialiser les variables
        $stats = [
            'total_projets' => 0,
            'total_publications' => 0,
            'total_membres' => 0,
            'total_equipes' => 0
        ];
        $actualites = [];
        $actualitesScientifiques = [];
        $presentation = [
            'description' => 'Le Laboratoire TDW est un centre de recherche de pointe spécialisé dans les Technologies du Développement Web, l\'Intelligence Artificielle et la Cybersécurité.'
        ];
        $directeur = null;
        $evenements = [];
        $partenaires = [];
        $projetsRecents = [];
        $publicationsRecentes = [];
        
        try {
            // Statistiques générales
            $stats['total_projets'] = $this->projetModel->count();
            $stats['total_publications'] = $this->publicationModel->count();
            $stats['total_membres'] = $this->membreModel->count();
            $stats['total_equipes'] = $this->equipeModel->count();
            
            // Actualités pour le diaporama
                // ============================================
    // ACTUALITÉS POUR LE DIAPORAMA
    // ============================================
    try {
        $db = Database::getInstance()->getConnection();
        
        // Récupérer les actualités du laboratoire pour le diaporama
        $stmt = $db->query("
            SELECT 
                id,
                type_actualite,
                titre,
                descriptif as description,
                date_publication,
                image,
                CASE 
                    WHEN type_actualite = 'evenement' THEN 'Événement'
                    WHEN type_actualite = 'soutenance' THEN 'Soutenance'
                    WHEN type_actualite = 'projet' THEN 'Projet'
                    WHEN type_actualite = 'publication' THEN 'Publication'
                    ELSE 'Actualité'
                END as categorie
            FROM actualite_laboratoire
            ORDER BY date_publication DESC
            LIMIT 5
        ");
        $actualites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erreur récupération actualités: " . $e->getMessage());
        $actualites = [];
    }
    
      
            // Actualités scientifiques
            $actualitesScientifiques = $this->actualiteModel->getAllScientifiques(4);
            
            // Directeur du laboratoire
            $directeur = $this->getDirecteur();
            
            // Événements à venir
            $evenements = $this->evenementModel->getUpcoming(6);
            
            // Partenaires
            $partenaires = $this->partenaireModel->getRecent(6);
            
            // Projets récents
            $projetsRecents = $this->projetModel->getRecent(6);
            
            // Publications récentes
            $publicationsRecentes = $this->publicationModel->getRecent(5);
            
        } catch (Exception $e) {
            error_log("Erreur dans VisiteurController::index() - " . $e->getMessage());
        }
        
     

        $data = [
            'stats' => $stats,
            'actualites' => $actualites,
            'actualitesScientifiques' => $actualitesScientifiques,
            'presentation' => $presentation,
            'directeur' => $directeur,
            'evenements' => $evenements,
            'partenaires' => $partenaires,
            'projetsRecents' => $projetsRecents,
            'publicationsRecentes' => $publicationsRecentes
        ];

        $view = new HomeView($data);
        $view->render();
    }
    
    /**
     * Récupérer le directeur du laboratoire
     */
    private function getDirecteur() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("
                SELECT 
                    m.id,
                    u.username as nom,
                    '' as prenom,
                    m.grade
                FROM membre m
                JOIN user u ON m.user_id = u.id
                WHERE m.grade LIKE '%Professeur%'
                ORDER BY m.id ASC
                LIMIT 1
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getDirecteur() - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Liste des projets publics
     */
    public function projets() {
        $projets = $this->projetModel->getAllWithResponsables();
        
        // Enrichir avec le nombre de membres et normaliser
        foreach ($projets as &$projet) {
            $membres = $this->projetModel->getMembres($projet['id']);
            $projet['nb_membres'] = count($membres);
            
            // Normalisation du statut
            $statusBrut = $projet['status'] ?? '';
            $projet['status_original'] = $statusBrut;
            $statusNettoye = strtolower(trim($statusBrut));
            
            $statusMap = [
                'en_cours' => 'en_cours',
                'en cours' => 'en_cours',
                'encours' => 'en_cours',
                'terminé' => 'termine',
                'termine' => 'termine',
                'soumis' => 'soumis'
            ];
            
            $projet['status_normalized'] = $statusMap[$statusNettoye] ?? str_replace(' ', '_', $statusNettoye);
            
            if (empty($projet['status_normalized'])) {
                $projet['status_normalized'] = 'en_cours';
            }
        }
        
        // Appliquer les filtres
        $filters = [
            'thematique' => get('thematique'),
            'status' => get('status'),
            'search' => get('search')
        ];
        
        if (!empty($filters['search'])) {
            $projets = array_filter($projets, function($p) use ($filters) {
                return stripos($p['titre'], $filters['search']) !== false ||
                       stripos($p['description'] ?? '', $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['thematique'])) {
            $projets = array_filter($projets, function($p) use ($filters) {
                return $p['thematique'] === $filters['thematique'];
            });
        }
        
        if (!empty($filters['status'])) {
            $projets = array_filter($projets, function($p) use ($filters) {
                return ($p['status_normalized'] ?? 'en_cours') === $filters['status'];
            });
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 12;
        $pagination = Utils::paginate(count($projets), $perPage, $page);
        $projets = array_slice($projets, $pagination['offset'], $perPage);
        
        $view = new ProjetsListView($projets, $pagination);
        $view->render();
    }

    /**
     * Détail d'un projet
     */
    public function projetDetail($id) {
        $projet = $this->projetModel->getById($id);
        
        if (!$projet) {
            $_SESSION['error'] = 'Projet non trouvé';
            redirect(base_url('projets'));
        }
        
        $membres = $this->projetModel->getMembres($id);
        $publications = $this->projetModel->getPublications($id);
        
        $responsable = null;
        if (!empty($projet['responsable_id'])) {
            $responsable = $this->membreModel->getById($projet['responsable_id']);
            
            if ($responsable) {
                $user = $this->membreModel->getUserByMembreId($responsable['id']);
                if ($user) {
                    $responsable['username'] = $user['username'];
                    $responsable['email'] = $user['email'];
                }
            }
        }
        
        $stats = [
            'nb_membres' => count($membres),
            'nb_publications' => count($publications),
            'progression' => LabHelpers::calculateProjectProgress(
                $projet['date_debut'], 
                $projet['date_fin'] ?? null
            )
        ];
        
        $view = new ProjetDetailView($projet, $membres, $publications, $responsable, $stats);
        $view->render();
    }
    
    /**
     * Liste des publications publiques
     */
    public function publications() {
        $publications = $this->publicationModel->getAllPublic();
        
        foreach ($publications as &$pub) {
            $auteurs = $this->publicationModel->getAuteurs($pub['id']);
            $pub['auteurs_noms'] = implode(', ', array_column($auteurs, 'username'));
            $pub['nb_auteurs'] = count($auteurs);
            $pub['annee_publication'] = date('Y', strtotime($pub['date_publication']));
        }
        
        // Filtres
        $filters = [
            'type' => get('type'),
            'domaine' => get('domaine'),
            'annee' => get('annee'),
            'search' => get('search')
        ];
        
        if (!empty($filters['search'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return stripos($p['titre'], $filters['search']) !== false ||
                       stripos($p['resume'] ?? '', $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['type'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return $p['type_publication'] === $filters['type'];
            });
        }
        
        if (!empty($filters['domaine'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return $p['domaine'] === $filters['domaine'];
            });
        }
        
        if (!empty($filters['annee'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return ($p['annee_publication'] ?? '') == $filters['annee'];
            });
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 12;
        $pagination = Utils::paginate(count($publications), $perPage, $page);
        $publications = array_slice($publications, $pagination['offset'], $perPage);
        
        $view = new PublicationsListView($publications, $pagination);
        $view->render();
    }

    /**
     * Détail d'une publication
     */
    public function publicationDetail($id) {
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication || ($publication['statut_validation'] ?? '') !== 'valide') {
            $_SESSION['error'] = 'Publication non disponible';
            redirect(base_url('publications'));
            exit;
        }
        
        $auteurs = $this->publicationModel->getAuteurs($id);
        
        foreach ($auteurs as &$auteur) {
            $membre = $this->membreModel->getById($auteur['id']);
            if ($membre && !empty($membre['equipe_id'])) {
                $equipe = $this->equipeModel->getById($membre['equipe_id']);
                $auteur['equipe_nom'] = $equipe['nom'] ?? null;
            }
        }
        
        $projet = null;
        if (!empty($publication['projet_id'])) {
            $projet = $this->projetModel->getById($publication['projet_id']);
        }
        
        $view = new PublicationDetailView($publication, $auteurs, $projet);
        $view->render();
    }

    /**
     * Liste des équipements
     */
    public function equipements() {
        $equipements = $this->equipementModel->getAll();
        
        // Filtres
        $filters = [
            'type' => get('type'),
            'etat' => get('etat'),
            'localisation' => get('localisation'),
            'search' => get('search')
        ];
        
        if (!empty($filters['search'])) {
            $equipements = array_filter($equipements, function($e) use ($filters) {
                return stripos($e['nom'], $filters['search']) !== false ||
                       stripos($e['description'] ?? '', $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['type'])) {
            $equipements = array_filter($equipements, function($e) use ($filters) {
                return $e['type_equipement'] === $filters['type'];
            });
        }
        
        if (!empty($filters['etat'])) {
            $equipements = array_filter($equipements, function($e) use ($filters) {
                return $e['etat'] === $filters['etat'];
            });
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 12;
        $pagination = Utils::paginate(count($equipements), $perPage, $page);
        $equipements = array_slice($equipements, $pagination['offset'], $perPage);
        
        $view = new EquipementsView($equipements, $pagination);
        $view->render();
    }

    /**
     * Détail d'un équipement
     */
    public function equipementDetail($id) {
        $equipement = $this->equipementModel->getById($id);
        
        if (!$equipement) {
            $_SESSION['error'] = 'Équipement introuvable';
            redirect(base_url('equipements'));
            exit;
        }
        
        $stats = [
            'nb_reservations_total' => 0,
            'nb_reservations_actives' => 0,
            'taux_utilisation' => 0
        ];
        
        $view = new EquipementDetailView($equipement, $stats);
        $view->render();
    }

    /**
     * Liste des membres du laboratoire
     */
    public function membres() {
        $membres = $this->membreModel->getAllMembresWithUser();
        
        // Filtrer pour exclure les visiteurs
        $membres = array_filter($membres, function($m) {
            return !isset($m['role']) || $m['role'] !== 'visiteur';
        });
        
        foreach ($membres as &$membre) {
            $membre['nb_projets'] = $this->projetModel->countByMembre($membre['id']);
            $membre['nb_publications'] = $this->publicationModel->countByMembre($membre['id']);
        }
        
        // Filtres
        $filters = [
            'poste' => get('poste'),
            'equipe' => get('equipe'),
            'grade' => get('grade'),
            'search' => get('search')
        ];
        
        if (!empty($filters['search'])) {
            $membres = array_filter($membres, function($m) use ($filters) {
                $searchTerm = strtolower($filters['search']);
                return stripos($m['username'], $searchTerm) !== false ||
                       stripos($m['email'] ?? '', $searchTerm) !== false;
            });
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 12;
        $pagination = Utils::paginate(count($membres), $perPage, $page);
        $membres = array_slice($membres, $pagination['offset'], $perPage);
        
        $view = new MembresView($membres, $pagination);
        $view->render();
    }

    /**
     * Détail d'un membre
     */
    public function membreDetail($id) {
        $membre = $this->membreModel->getWithDetails($id);
        
        if (!$membre) {
            $_SESSION['error'] = 'Membre introuvable';
            redirect(base_url('membres'));
            exit;
        }
        
        $projets = $this->projetModel->getByMembre($id);
        $publications = $this->publicationModel->getByAuteur($id);
        
        // Filtrer pour ne garder que les publications validées
        $publications = array_filter($publications, function($pub) {
            return ($pub['statut_validation'] ?? '') === 'valide';
        });
        
        $view = new MembreDetailView($membre, $projets, $publications);
        $view->render();
    }


    /**
     * Liste des événements publics
     */
    
    public function evenements() {
    $evenements = $this->evenementModel->getUpcoming();
    
    // Appliquer les filtres
    $filters = [
        'type' => get('type'),
        'mois' => get('mois'),
        'search' => get('search')
    ];
    
    if (!empty($filters['search'])) {
        $evenements = array_filter($evenements, function($e) use ($filters) {
            return stripos($e['titre'], $filters['search']) !== false ||
                   stripos($e['description'] ?? '', $filters['search']) !== false;
        });
    }
    
    if (!empty($filters['type'])) {
        $evenements = array_filter($evenements, function($e) use ($filters) {
            return $e['type_evenement'] === $filters['type'];
        });
    }
    
    if (!empty($filters['mois'])) {
        $evenements = array_filter($evenements, function($e) use ($filters) {
            return date('Y-m', strtotime($e['date_evenement'])) === $filters['mois'];
        });
    }
    
    // Pagination
    $page = (int) get('page', 1);
    $perPage = 12;
    $pagination = Utils::paginate(count($evenements), $perPage, $page);
    $evenements = array_slice($evenements, $pagination['offset'], $perPage);
    
    // Instancier et afficher la vue (2 lignes)
    $view = new EvenementsView($evenements, $pagination);
    $view->render();
}

/**
 * Détail d'un événement
 */
public function evenementDetail($id) {
    $evenement = $this->evenementModel->getById($id);
    
    if (!$evenement) {
        $_SESSION['error'] = 'Événement introuvable';
        redirect(base_url('evenements'));
        exit;
    }
    
    // Instancier et afficher la vue (2 lignes)
    $view = new EvenementDetailView($evenement);
    $view->render();
}
    
    /**
     * Liste des actualités
     */
    public function actualites() {
        // Récupérer les publications récentes
        $publications = $this->publicationModel->getRecent(10);
        
        // Récupérer les événements récents
        $evenements = $this->evenementModel->getRecent(10);
        
        // Récupérer les actualités scientifiques
        $actualitesScientifiques = $this->actualiteModel->getAllScientifiques(10);
        
        // Récupérer les actualités laboratoire
        $actualitesLaboratoire = $this->actualiteModel->getAllLaboratoire(10);
        
        // Fusionner toutes les actualités
        $actualites = [];
        
        // Ajouter les publications
        foreach ($publications as $pub) {
            $actualites[] = [
                'type' => 'publication',
                'date' => $pub['date_publication'],
                'data' => $pub
            ];
        }
        
        // Ajouter les événements
        foreach ($evenements as $event) {
            $actualites[] = [
                'type' => 'evenement',
                'date' => $event['date_evenement'],
                'data' => $event
            ];
        }
        
        // Ajouter les actualités scientifiques
        foreach ($actualitesScientifiques as $actu) {
            $actualites[] = [
                'type' => 'scientifique',
                'source' => 'scientifique',
                'date' => $actu['date_publication'],
                'data' => $actu,
                'titre' => $actu['titre'],
                'description' => $actu['description'],
                'image' => $actu['image'] ?? null
            ];
        }
        
        // Ajouter les actualités laboratoire
        foreach ($actualitesLaboratoire as $actu) {
            $actualites[] = [
                'type' => 'laboratoire',
                'source' => 'laboratoire',
                'date' => $actu['date_publication'],
                'data' => $actu,
                'titre' => $actu['titre'],
                'description' => $actu['description'],
                'image' => $actu['image'] ?? null
            ];
        }
        
        // Trier par date décroissante
        usort($actualites, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Appliquer les filtres
        $filters = [
            'type' => get('type'),
            'mois' => get('mois'),
            'search' => get('search')
        ];
        
        if (!empty($filters['search'])) {
            $actualites = array_filter($actualites, function($a) use ($filters) {
                $titre = $a['data']['titre'] ?? '';
                $description = $a['data']['description'] ?? $a['data']['resume'] ?? '';
                return stripos($titre, $filters['search']) !== false ||
                       stripos($description, $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['type'])) {
            $actualites = array_filter($actualites, function($a) use ($filters) {
                return $a['type'] === $filters['type'] || 
                       ($a['source'] ?? '') === $filters['type'];
            });
        }
        
        if (!empty($filters['mois'])) {
            $actualites = array_filter($actualites, function($a) use ($filters) {
                return date('Y-m', strtotime($a['date'])) === $filters['mois'];
            });
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 12;
        $pagination = Utils::paginate(count($actualites), $perPage, $page);
        $actualites = array_slice($actualites, $pagination['offset'], $perPage);
        
       
        $view = new ActualitesView($actualites, $pagination);
        $view->render();
    }
    
    /**
     * Détail d'une actualité
     */
    public function actualiteDetail($id) {
        // Essayer de récupérer l'actualité scientifique
        $actualite = null;
        $source = 'scientifique';
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Chercher dans actualite_scientifique
            $stmt = $db->prepare("
                SELECT 
                    a.id,
                    a.titre,
                    a.contenu as description,
                    a.image,
                    a.date_publication,
                    'scientifique' as source,
                    u.username as auteur_nom
                FROM actualite_scientifique a
                LEFT JOIN membre m ON a.auteur_id = m.id
                LEFT JOIN user u ON m.user_id = u.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $actualite = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si pas trouvé, chercher dans actualite_laboratoire
            if (!$actualite) {
                $stmt = $db->prepare("
                    SELECT 
                        id,
                        titre,
                        descriptif as description,
                        image,
                        date_publication,
                        lien_detail,
                        'laboratoire' as source
                    FROM actualite_laboratoire
                    WHERE id = ?
                ");
                $stmt->execute([$id]);
                $actualite = $stmt->fetch(PDO::FETCH_ASSOC);
                $source = 'laboratoire';
            }
            
        } catch (Exception $e) {
            error_log("Erreur actualiteDetail(): " . $e->getMessage());
        }
        
        if (!$actualite) {
            $_SESSION['error'] = 'Actualité introuvable';
            redirect(base_url('actualites'));
            exit;
        }
        
        // Récupérer les actualités liées (même source)
        $actualitesLiees = [];
        if ($source === 'scientifique') {
            $actualitesLiees = $this->actualiteModel->getAllScientifiques(5);
            // Retirer l'actualité courante
            $actualitesLiees = array_filter($actualitesLiees, function($a) use ($id) {
                return $a['id'] != $id;
            });
        } else {
            $actualitesLiees = $this->actualiteModel->getAllLaboratoire(5);
            // Retirer l'actualité courante
            $actualitesLiees = array_filter($actualitesLiees, function($a) use ($id) {
                return $a['id'] != $id;
            });
        }
        

        $view = new ActualiteDetailView($actualite, $actualitesLiees);
        $view->render();
    }

    
    /**
     * Page de contact
     */
    public function contact() {
        $successMessage = $_SESSION['success'] ?? null;
        $errorMessage = $_SESSION['error'] ?? null;
        unset($_SESSION['success'], $_SESSION['error']);
        
        $view = new ContactView([], $successMessage, $errorMessage);
        $view->render();
    }
    
    /**
     * Traiter le formulaire de contact
     */
    public function envoyerContact() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contact');
        }
        
        $data = [
            'nom' => post('nom'),
            'email' => post('email'),
            'sujet' => post('sujet'),
            'message' => post('message')
        ];
        
        if (empty($data['nom']) || empty($data['email']) || empty($data['message'])) {
            flash('error', 'Veuillez remplir tous les champs obligatoires');
            redirect('contact');
        }
        
        if (!Utils::validateEmail($data['email'])) {
            flash('error', 'Adresse email invalide');
            redirect('contact');
        }
        
        flash('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');
        redirect('contact');
    }

    /**
 * Page Organigramme - Présentation et équipes
 */
public function organigramme() {
    $membres = [];
    $equipes = [];
    $directeur = null;
    
    try {
        // Récupérer tous les membres avec leurs informations
        $membres = $this->membreModel->getAllMembresWithUser();
        
        // Filtrer pour exclure les visiteurs et normaliser les postes
        $membres = array_filter($membres, function($m) {
            return !isset($m['role']) || $m['role'] !== 'visiteur';
        });
        
        // Normaliser les postes pour le filtrage
        foreach ($membres as &$membre) {
            $posteBrut = $membre['poste'] ?? '';
            $posteNettoye = strtolower(trim($posteBrut));
            
            $posteMap = [
                'enseignant' => 'enseignant',
                'doctorant' => 'doctorant',
                'etudiant' => 'etudiant',
                'Ã©tudiant' => 'etudiant',
                'invite' => 'invite',
                'invitÃ©' => 'invite'
            ];
            
            $membre['poste_normalized'] = $posteMap[$posteNettoye] ?? $posteNettoye;
            
            if (empty($membre['poste_normalized'])) {
                $membre['poste_normalized'] = 'enseignant';
            }
        }
        
        // Récupérer le directeur (premier professeur)
        $directeur = $this->getDirecteur();
        
        // Récupérer toutes les équipes avec leurs chefs
        $equipes = $this->equipeModel->getAllWithChefs();
        
        // Pour chaque équipe, enrichir avec les membres
        foreach ($equipes as &$equipe) {
            $equipe['membres'] = $this->equipeModel->getMembres($equipe['id']);
        }
        
    } catch (Exception $e) {
        error_log("Erreur dans VisiteurController::organigramme() - " . $e->getMessage());
    }
    
     $view = new OrganigrammeView($directeur, $membres, $equipes, $this->equipeModel);
     $view->render();
}
}