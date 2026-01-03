<?php
/**
 * Configuration de la base de données TIZ-ANA
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'tizana_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuration de l'application
define('APP_NAME', 'TIZ-ANA');
define('APP_URL', 'http://localhost');
define('APP_TIMEZONE', 'Africa/Kinshasa');

// Configuration de sécurité
define('JWT_SECRET', 'your-secret-key-change-this-in-production');
define('SESSION_LIFETIME', 86400); // 24 heures en secondes
define('PASSWORD_MIN_LENGTH', 6);

// Configuration des commissions
define('REFERRAL_LEVEL1_COMMISSION', 10); // 10%
define('REFERRAL_LEVEL2_COMMISSION', 5);  // 5%
define('REFERRAL_LEVEL3_COMMISSION', 3);  // 3%

// Configuration des retraits
define('MIN_WITHDRAWAL', 5000);
define('MAX_WITHDRAWAL', 1000000);

// Configuration CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Gestion des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fonction de connexion à la base de données
function getDBConnection() {
    static $pdo = null;
    
    // Réutiliser la connexion si elle existe déjà
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false, // Pas de connexion persistante pour éviter les problèmes
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Test de connexion
        $pdo->query("SELECT 1");
        
        return $pdo;
    } catch (PDOException $e) {
        // Log de l'erreur
        error_log("Erreur de connexion DB: " . $e->getMessage());
        
        // Si on est dans un contexte API, retourner JSON
        if (php_sapi_name() !== 'cli') {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur de connexion à la base de données',
                'error' => $e->getMessage(),
                'debug' => [
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                    'user' => DB_USER,
                    'suggestion' => 'Vérifiez que MySQL est démarré et que la base de données existe'
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit();
        } else {
            // En CLI, lever l'exception
            throw $e;
        }
    }
}

// Fonction de test de connexion (pour vérifier sans erreur)
function testDBConnection() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        return $result && $result['test'] == 1;
    } catch (Exception $e) {
        return false;
    }
}

// Fonction pour générer un token de session
function generateSessionToken($length = 64) {
    return bin2hex(random_bytes($length));
}

// Fonction pour vérifier le token de session
function verifySessionToken($token) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT us.*, u.id as user_id, u.username, u.email, u.balance, u.status
        FROM user_sessions us
        JOIN users u ON us.user_id = u.id
        WHERE us.session_token = ? 
        AND us.expires_at > NOW()
        AND u.status = 'active'
    ");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

// Fonction pour obtenir l'utilisateur actuel
function getCurrentUser() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $_COOKIE['session_token'] ?? null;
    
    if (!$token) {
        return null;
    }
    
    // Retirer "Bearer " si présent
    $token = str_replace('Bearer ', '', $token);
    
    return verifySessionToken($token);
}

// Fonction pour répondre avec JSON
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Fonction pour valider l'email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Fonction pour hasher le mot de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Fonction pour vérifier le mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Fonction pour générer un code de parrainage unique
function generateReferralCode($username) {
    $code = strtoupper(substr($username, 0, 3) . rand(1000, 9999));
    return $code;
}

// Configuration du fuseau horaire
date_default_timezone_set(APP_TIMEZONE);

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

?>

