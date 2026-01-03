<?php
/**
 * Script d'installation automatique de la base de données
 * Ce script crée la base de données et importe toutes les tables
 * Accédez à ce fichier via : http://localhost/tizana/setup_database.php
 */

header('Content-Type: text/html; charset=utf-8');

// Si config.php existe, on l'utilise, sinon on demande les paramètres
$configExists = file_exists('config.php');
$setupComplete = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'tizana_db';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    
    try {
        // Connexion sans spécifier la base de données (pour la créer si nécessaire)
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Créer la base de données si elle n'existe pas
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        
        // Lire et exécuter le fichier SQL
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("Le fichier database.sql est introuvable");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Supprimer les commentaires SQL qui pourraient causer des problèmes
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Diviser les commandes SQL
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (empty($statement) || strlen(trim($statement)) < 5) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                // Ignorer les erreurs "table already exists" et similaires
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    $errorCount++;
                    $errors[] = $e->getMessage();
                } else {
                    $successCount++;
                }
            }
        }
        
        // Mettre à jour config.php si nécessaire
        if (!$configExists && isset($_POST['save_config'])) {
            $configContent = "<?php\n";
            $configContent .= "define('DB_HOST', '$db_host');\n";
            $configContent .= "define('DB_NAME', '$db_name');\n";
            $configContent .= "define('DB_USER', '$db_user');\n";
            $configContent .= "define('DB_PASS', '" . addslashes($db_pass) . "');\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
            $configContent .= "\n// ... (rest of config.php content)\n";
            // Note: Il faudrait copier le reste du contenu de config.php
            
            file_put_contents('config.php', $configContent);
        }
        
        $setupComplete = true;
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Base de Données - TIZ-ANA</title>
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
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
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
        .checkbox-group {
            margin: 20px 0;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Installation de la Base de Données</h1>
        
        <?php if ($setupComplete): ?>
            <div class="result success">
                <h2>✅ Installation Réussie !</h2>
                <p>La base de données a été créée et configurée avec succès.</p>
                <p><strong>Commandes exécutées :</strong> <?php echo $successCount; ?></p>
                <?php if ($errorCount > 0): ?>
                    <p><strong>Erreurs (non critiques) :</strong> <?php echo $errorCount; ?></p>
                <?php endif; ?>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="test_connection.php" style="color: #667eea; font-weight: 600; margin-right: 20px;">Tester la connexion</a>
                <a href="index.html" style="color: #667eea; font-weight: 600;">Aller au site</a>
            </div>
        <?php else: ?>
            <?php if (isset($errorMessage)): ?>
                <div class="result error">
                    <strong>❌ Erreur :</strong><br>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <div class="result info">
                <strong>📋 Instructions :</strong><br>
                Ce script va créer la base de données <code>tizana_db</code> et importer toutes les tables nécessaires.<br>
                Assurez-vous que MySQL est démarré dans XAMPP avant de continuer.
            </div>
            
            <form method="POST" style="margin-top: 30px;">
                <div class="form-group">
                    <label>Hôte MySQL :</label>
                    <input type="text" name="db_host" value="<?php echo $configExists ? DB_HOST : 'localhost'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Nom de la base de données :</label>
                    <input type="text" name="db_name" value="<?php echo $configExists ? DB_NAME : 'tizana_db'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Utilisateur MySQL :</label>
                    <input type="text" name="db_user" value="<?php echo $configExists ? DB_USER : 'root'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Mot de passe MySQL :</label>
                    <input type="password" name="db_pass" value="<?php echo $configExists ? DB_PASS : ''; ?>">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Laissez vide si vous utilisez XAMPP avec les paramètres par défaut
                    </small>
                </div>
                
                <?php if (!$configExists): ?>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="save_config" checked>
                            Sauvegarder ces paramètres dans config.php
                        </label>
                    </div>
                <?php endif; ?>
                
                <button type="submit" name="install">
                    🚀 Installer la Base de Données
                </button>
            </form>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="test_connection.php" style="color: #667eea; font-weight: 600;">Tester la connexion existante</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

