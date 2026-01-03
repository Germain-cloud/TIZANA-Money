<?php
/**
 * Script de vérification de l'installation TIZ-ANA
 * Accédez à ce fichier via : http://localhost/tizana/check_setup.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Installation TIZ-ANA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .check-item.success {
            background: rgba(76, 175, 80, 0.1);
            border-left: 4px solid #4caf50;
        }
        .check-item.error {
            background: rgba(244, 67, 54, 0.1);
            border-left: 4px solid #f44336;
        }
        .check-item.warning {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
        }
        .icon {
            font-size: 24px;
        }
        .success .icon { color: #4caf50; }
        .error .icon { color: #f44336; }
        .warning .icon { color: #ffc107; }
        .message {
            flex: 1;
        }
        .message strong {
            display: block;
            margin-bottom: 5px;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .summary.success {
            background: rgba(76, 175, 80, 0.2);
            color: #2e7d32;
        }
        .summary.error {
            background: rgba(244, 67, 54, 0.2);
            color: #c62828;
        }
        code {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Vérification de l'Installation TIZ-ANA</h1>
        
        <?php
        $checks = [];
        $allPassed = true;
        
        // Vérification 1: PHP Version
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '7.4.0', '>=');
        $checks[] = [
            'status' => $phpOk ? 'success' : 'error',
            'icon' => $phpOk ? '✅' : '❌',
            'title' => 'Version PHP',
            'message' => $phpOk 
                ? "Version PHP: <code>$phpVersion</code> (OK)"
                : "Version PHP: <code>$phpVersion</code> (Minimum requis: 7.4.0)"
        ];
        if (!$phpOk) $allPassed = false;
        
        // Vérification 2: Extension PDO
        $pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
        $checks[] = [
            'status' => $pdoOk ? 'success' : 'error',
            'icon' => $pdoOk ? '✅' : '❌',
            'title' => 'Extension PDO MySQL',
            'message' => $pdoOk 
                ? "Extension PDO MySQL activée"
                : "Extension PDO MySQL non trouvée. Activez-la dans php.ini"
        ];
        if (!$pdoOk) $allPassed = false;
        
        // Vérification 3: Fichier config.php
        $configExists = file_exists('config.php');
        $checks[] = [
            'status' => $configExists ? 'success' : 'error',
            'icon' => $configExists ? '✅' : '❌',
            'title' => 'Fichier config.php',
            'message' => $configExists 
                ? "Fichier config.php trouvé"
                : "Fichier config.php manquant"
        ];
        if (!$configExists) $allPassed = false;
        
        // Vérification 4: Fichier api.php
        $apiExists = file_exists('api.php');
        $checks[] = [
            'status' => $apiExists ? 'success' : 'error',
            'icon' => $apiExists ? '✅' : '❌',
            'title' => 'Fichier api.php',
            'message' => $apiExists 
                ? "Fichier api.php trouvé"
                : "Fichier api.php manquant"
        ];
        if (!$apiExists) $allPassed = false;
        
        // Vérification 5: Fichier database.sql
        $dbExists = file_exists('database.sql');
        $checks[] = [
            'status' => $dbExists ? 'success' : 'error',
            'icon' => $dbExists ? '✅' : '❌',
            'title' => 'Fichier database.sql',
            'message' => $dbExists 
                ? "Fichier database.sql trouvé"
                : "Fichier database.sql manquant"
        ];
        if (!$dbExists) $allPassed = false;
        
        // Vérification 6: Connexion à la base de données
        if ($configExists) {
            require_once 'config.php';
            try {
                $pdo = getDBConnection();
                $checks[] = [
                    'status' => 'success',
                    'icon' => '✅',
                    'title' => 'Connexion à la base de données',
                    'message' => "Connexion réussie à la base de données <code>" . DB_NAME . "</code>"
                ];
                
                // Vérification 7: Tables de la base de données
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $requiredTables = ['users', 'vip_packages', 'investments', 'transactions', 'daily_earnings', 'withdrawals', 'team_members', 'commissions', 'notifications'];
                $missingTables = array_diff($requiredTables, $tables);
                
                if (empty($missingTables)) {
                    $checks[] = [
                        'status' => 'success',
                        'icon' => '✅',
                        'title' => 'Tables de la base de données',
                        'message' => "Toutes les tables requises sont présentes (" . count($tables) . " tables)"
                    ];
                } else {
                    $checks[] = [
                        'status' => 'error',
                        'icon' => '❌',
                        'title' => 'Tables de la base de données',
                        'message' => "Tables manquantes: " . implode(', ', $missingTables) . "<br>Exécutez <code>database.sql</code> dans phpMyAdmin"
                    ];
                    $allPassed = false;
                }
                
                // Vérification 8: Packages VIP
                $stmt = $pdo->query("SELECT COUNT(*) FROM vip_packages");
                $packageCount = $stmt->fetchColumn();
                if ($packageCount >= 9) {
                    $checks[] = [
                        'status' => 'success',
                        'icon' => '✅',
                        'title' => 'Packages VIP',
                        'message' => "$packageCount packages VIP configurés"
                    ];
                } else {
                    $checks[] = [
                        'status' => 'warning',
                        'icon' => '⚠️',
                        'title' => 'Packages VIP',
                        'message' => "Seulement $packageCount packages trouvés (9 attendus)"
                    ];
                }
                
            } catch (Exception $e) {
                $checks[] = [
                    'status' => 'error',
                    'icon' => '❌',
                    'title' => 'Connexion à la base de données',
                    'message' => "Erreur: " . $e->getMessage() . "<br>Vérifiez <code>config.php</code>"
                ];
                $allPassed = false;
            }
        } else {
            $checks[] = [
                'status' => 'warning',
                'icon' => '⚠️',
                'title' => 'Connexion à la base de données',
                'message' => "Impossible de vérifier (config.php manquant)"
            ];
        }
        
        // Vérification 9: Fichier index.html
        $indexExists = file_exists('index.html');
        $checks[] = [
            'status' => $indexExists ? 'success' : 'error',
            'icon' => $indexExists ? '✅' : '❌',
            'title' => 'Fichier index.html',
            'message' => $indexExists 
                ? "Fichier index.html trouvé"
                : "Fichier index.html manquant"
        ];
        if (!$indexExists) $allPassed = false;
        
        // Vérification 10: Fichier app.js
        $appJsExists = file_exists('app.js');
        $checks[] = [
            'status' => $appJsExists ? 'success' : 'error',
            'icon' => $appJsExists ? '✅' : '❌',
            'title' => 'Fichier app.js',
            'message' => $appJsExists 
                ? "Fichier app.js trouvé"
                : "Fichier app.js manquant"
        ];
        if (!$appJsExists) $allPassed = false;
        
        // Afficher les résultats
        foreach ($checks as $check) {
            echo '<div class="check-item ' . $check['status'] . '">';
            echo '<span class="icon">' . $check['icon'] . '</span>';
            echo '<div class="message">';
            echo '<strong>' . $check['title'] . '</strong>';
            echo '<div>' . $check['message'] . '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        // Résumé
        echo '<div class="summary ' . ($allPassed ? 'success' : 'error') . '">';
        if ($allPassed) {
            echo '<h2>✅ Installation Complète !</h2>';
            echo '<p>Votre plateforme TIZ-ANA est correctement installée.</p>';
            echo '<p style="margin-top: 20px;"><a href="index.html" style="color: #2e7d32; font-weight: bold;">→ Accéder au site</a></p>';
        } else {
            echo '<h2>❌ Problèmes Détectés</h2>';
            echo '<p>Veuillez corriger les erreurs ci-dessus avant de continuer.</p>';
            echo '<p style="margin-top: 20px;">Consultez <code>INSTALLATION.md</code> pour plus d\'aide.</p>';
        }
        echo '</div>';
        ?>
    </div>
</body>
</html>

