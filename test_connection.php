<?php
/**
 * Script de test de connexion à la base de données
 * Accédez à ce fichier via : http://localhost/tizana/test_connection.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Connexion - TIZ-ANA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 700px;
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
        .result {
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            border-left: 4px solid;
        }
        .success {
            background: rgba(76, 175, 80, 0.1);
            border-color: #4caf50;
            color: #2e7d32;
        }
        .error {
            background: rgba(244, 67, 54, 0.1);
            border-color: #f44336;
            color: #c62828;
        }
        .info {
            background: rgba(33, 150, 243, 0.1);
            border-color: #2196f3;
            color: #1565c0;
        }
        .config-form {
            background: rgba(0, 0, 0, 0.05);
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        code {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: rgba(102, 126, 234, 0.1);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔌 Test de Connexion Base de Données</h1>
        
        <?php
        // Charger la configuration
        $configExists = file_exists('config.php');
        
        if (!$configExists) {
            echo '<div class="result error">';
            echo '<strong>❌ Erreur :</strong> Le fichier <code>config.php</code> est introuvable.';
            echo '</div>';
            echo '<div class="config-form">';
            echo '<h3>Configuration manuelle</h3>';
            echo '<form method="POST">';
            echo '<div class="form-group">';
            echo '<label>Hôte MySQL:</label>';
            echo '<input type="text" name="db_host" value="localhost" required>';
            echo '</div>';
            echo '<div class="form-group">';
            echo '<label>Nom de la base de données:</label>';
            echo '<input type="text" name="db_name" value="tizana_db" required>';
            echo '</div>';
            echo '<div class="form-group">';
            echo '<label>Utilisateur MySQL:</label>';
            echo '<input type="text" name="db_user" value="root" required>';
            echo '</div>';
            echo '<div class="form-group">';
            echo '<label>Mot de passe MySQL:</label>';
            echo '<input type="password" name="db_pass" value="">';
            echo '</div>';
            echo '<button type="submit">Tester la connexion</button>';
            echo '</form>';
            echo '</div>';
        } else {
            require_once 'config.php';
            
            // Si des paramètres sont fournis via POST, les utiliser
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host'])) {
                $db_host = $_POST['db_host'];
                $db_name = $_POST['db_name'];
                $db_user = $_POST['db_user'];
                $db_pass = $_POST['db_pass'];
            } else {
                $db_host = DB_HOST;
                $db_name = DB_NAME;
                $db_user = DB_USER;
                $db_pass = DB_PASS;
            }
            
            echo '<div class="result info">';
            echo '<strong>📋 Paramètres de connexion :</strong><br>';
            echo 'Hôte: <code>' . htmlspecialchars($db_host) . '</code><br>';
            echo 'Base de données: <code>' . htmlspecialchars($db_name) . '</code><br>';
            echo 'Utilisateur: <code>' . htmlspecialchars($db_user) . '</code><br>';
            echo 'Mot de passe: ' . (empty($db_pass) ? '<code>(vide)</code>' : '<code>***</code>');
            echo '</div>';
            
            // Tester la connexion
            try {
                $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                
                $pdo = new PDO($dsn, $db_user, $db_pass, $options);
                
                echo '<div class="result success">';
                echo '<strong>✅ Connexion réussie !</strong><br>';
                echo 'La connexion à la base de données fonctionne correctement.';
                echo '</div>';
                
                // Vérifier les tables
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($tables) > 0) {
                    echo '<div class="result success">';
                    echo '<strong>✅ Tables trouvées :</strong> ' . count($tables) . ' table(s)';
                    echo '</div>';
                    
                    echo '<table>';
                    echo '<tr><th>Nom de la table</th><th>Lignes</th></tr>';
                    foreach ($tables as $table) {
                        $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                        $count = $countStmt->fetchColumn();
                        echo "<tr><td><code>$table</code></td><td>$count</td></tr>";
                    }
                    echo '</table>';
                    
                    // Vérifier les tables requises
                    $requiredTables = ['users', 'vip_packages', 'investments', 'transactions', 'daily_earnings', 'withdrawals', 'team_members', 'commissions', 'notifications'];
                    $missingTables = array_diff($requiredTables, $tables);
                    
                    if (empty($missingTables)) {
                        echo '<div class="result success">';
                        echo '<strong>✅ Toutes les tables requises sont présentes</strong>';
                        echo '</div>';
                    } else {
                        echo '<div class="result error">';
                        echo '<strong>⚠️ Tables manquantes :</strong> ' . implode(', ', $missingTables);
                        echo '<br>Importez le fichier <code>database.sql</code> dans phpMyAdmin';
                        echo '</div>';
                    }
                    
                    // Vérifier les packages VIP
                    if (in_array('vip_packages', $tables)) {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM vip_packages");
                        $packageCount = $stmt->fetchColumn();
                        if ($packageCount > 0) {
                            echo '<div class="result success">';
                            echo "<strong>✅ Packages VIP :</strong> $packageCount package(s) configuré(s)";
                            echo '</div>';
                        } else {
                            echo '<div class="result error">';
                            echo '<strong>⚠️ Aucun package VIP trouvé</strong>';
                            echo '</div>';
                        }
                    }
                    
                } else {
                    echo '<div class="result error">';
                    echo '<strong>⚠️ Aucune table trouvée</strong><br>';
                    echo 'La base de données existe mais est vide. Importez le fichier <code>database.sql</code>';
                    echo '</div>';
                }
                
                // Test d'insertion/lecture
                try {
                    $testTable = 'test_connection_' . time();
                    $pdo->exec("CREATE TABLE IF NOT EXISTS $testTable (id INT PRIMARY KEY AUTO_INCREMENT, test VARCHAR(50))");
                    $pdo->exec("INSERT INTO $testTable (test) VALUES ('test_value')");
                    $stmt = $pdo->query("SELECT test FROM $testTable LIMIT 1");
                    $result = $stmt->fetch();
                    $pdo->exec("DROP TABLE $testTable");
                    
                    if ($result && $result['test'] === 'test_value') {
                        echo '<div class="result success">';
                        echo '<strong>✅ Test d\'écriture/lecture :</strong> Fonctionne correctement';
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="result error">';
                    echo '<strong>⚠️ Test d\'écriture/lecture :</strong> ' . $e->getMessage();
                    echo '</div>';
                }
                
            } catch (PDOException $e) {
                echo '<div class="result error">';
                echo '<strong>❌ Erreur de connexion :</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
                
                // Suggestions selon l'erreur
                $errorCode = $e->getCode();
                $errorMsg = $e->getMessage();
                
                echo '<div class="result info">';
                echo '<strong>💡 Suggestions :</strong><br>';
                
                if (strpos($errorMsg, 'Access denied') !== false) {
                    echo '- Vérifiez le nom d\'utilisateur et le mot de passe<br>';
                    echo '- Par défaut dans XAMPP : utilisateur = <code>root</code>, mot de passe = <code>(vide)</code><br>';
                } elseif (strpos($errorMsg, 'Unknown database') !== false) {
                    echo '- La base de données <code>' . htmlspecialchars($db_name) . '</code> n\'existe pas<br>';
                    echo '- Créez-la dans phpMyAdmin ou exécutez : <code>CREATE DATABASE ' . htmlspecialchars($db_name) . ';</code><br>';
                } elseif (strpos($errorMsg, 'Connection refused') !== false || strpos($errorMsg, 'No connection') !== false) {
                    echo '- MySQL n\'est pas démarré<br>';
                    echo '- Démarrez MySQL dans XAMPP Control Panel<br>';
                } else {
                    echo '- Vérifiez que MySQL est démarré<br>';
                    echo '- Vérifiez les paramètres dans <code>config.php</code><br>';
                }
                echo '</div>';
                
                // Formulaire pour modifier la configuration
                echo '<div class="config-form">';
                echo '<h3>Modifier la configuration</h3>';
                echo '<form method="POST">';
                echo '<div class="form-group">';
                echo '<label>Hôte MySQL:</label>';
                echo '<input type="text" name="db_host" value="' . htmlspecialchars($db_host) . '" required>';
                echo '</div>';
                echo '<div class="form-group">';
                echo '<label>Nom de la base de données:</label>';
                echo '<input type="text" name="db_name" value="' . htmlspecialchars($db_name) . '" required>';
                echo '</div>';
                echo '<div class="form-group">';
                echo '<label>Utilisateur MySQL:</label>';
                echo '<input type="text" name="db_user" value="' . htmlspecialchars($db_user) . '" required>';
                echo '</div>';
                echo '<div class="form-group">';
                echo '<label>Mot de passe MySQL:</label>';
                echo '<input type="password" name="db_pass" value="">';
                echo '</div>';
                echo '<button type="submit">Réessayer la connexion</button>';
                echo '</div>';
                echo '</form>';
            }
        }
        ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="index.html" style="color: #667eea; font-weight: 600;">← Retour au site</a> |
            <a href="check_setup.php" style="color: #667eea; font-weight: 600;">Vérification complète</a>
        </div>
    </div>
</body>
</html>

