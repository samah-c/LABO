<?php
/**
 * Test direct de la page membres
 * À placer dans : /TDW_project/test-membres.php
 */
opcache_reset();
echo "<h1>TEST DIAGNOSTIC - Page Membres</h1>";

// 1. Vérifier les fichiers
echo "<h2>1. Vérification des fichiers</h2>";

$files = [
    'controllers/visitor/VisiteurController.php',
    'models/MembreModel.php',
    'views/visitor/membres/membres.php',
    'lib/helpers.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo "<p><strong>$file :</strong> ";
    if (file_exists($path)) {
        echo "✅ Existe (" . filesize($path) . " octets)</p>";
    } else {
        echo "❌ INTROUVABLE</p>";
    }
}

// 2. Tester la base de données
echo "<h2>2. Test de connexion base de données</h2>";
try {
    require_once __DIR__ . '/models/Model.php';
    require_once __DIR__ . '/models/MembreModel.php';
    
    $membreModel = new MembreModel();
    echo "<p>✅ Connexion DB OK</p>";
    
    // 3. Tester la récupération des membres
    echo "<h2>3. Test récupération des membres</h2>";
    $membres = $membreModel->getAllMembresWithUser();
    echo "<p>✅ Nombre de membres récupérés : " . count($membres) . "</p>";
    
    if (count($membres) > 0) {
        echo "<h3>Premier membre (exemple) :</h3>";
        echo "<pre>";
        print_r($membres[0]);
        echo "</pre>";
        
        // Vérifier si 'role' existe
        if (isset($membres[0]['role'])) {
            echo "<p>✅ Le champ 'role' est présent : " . $membres[0]['role'] . "</p>";
        } else {
            echo "<p>❌ Le champ 'role' est ABSENT</p>";
        }
    }
    
    // 4. Tester le filtre
    echo "<h2>4. Test du filtre visiteur</h2>";
    $membresFiltered = array_filter($membres, function($m) {
        return !isset($m['role']) || $m['role'] !== 'visiteur';
    });
    echo "<p>Membres après filtre : " . count($membresFiltered) . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ ERREUR : " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 5. Tester le contrôleur
echo "<h2>5. Test du contrôleur</h2>";
try {
    require_once __DIR__ . '/lib/helpers.php';
    require_once __DIR__ . '/controllers/visitor/VisiteurController.php';
    
    $controller = new VisiteurController();
    echo "<p>✅ VisiteurController instancié</p>";
    
    echo "<h3>Tentative d'appel de membres()...</h3>";
    ob_start();
    $controller->membres();
    $output = ob_get_clean();
    
    echo "<p>✅ Méthode membres() exécutée sans erreur</p>";
    echo "<p>Longueur de la sortie : " . strlen($output) . " caractères</p>";
    
} catch (Exception $e) {
    echo "<p>❌ ERREUR dans le contrôleur : " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>6. Test via index.php</h2>";
echo '<p><a href="/TDW_project/membres">Cliquez ici pour tester via index.php</a></p>';

echo "<hr>";
echo "<p><strong>Si tout est vert ci-dessus mais que /membres ne marche pas, le problème est dans index.php</strong></p>";
?>