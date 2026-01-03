<?php
/**
 * Script cron pour distribuer les revenus quotidiens
 * À exécuter quotidiennement (recommandé à minuit)
 * 
 * Configuration cron (Linux):
 * 0 0 * * * php /chemin/vers/tizana/cron_daily_earnings.php
 * 
 * Configuration tâche planifiée (Windows):
 * Créer une tâche planifiée qui exécute: php.exe cron_daily_earnings.php
 */

require_once 'config.php';

// Désactiver l'affichage des erreurs pour les logs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/cron_error.log');

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/cron.log';
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

try {
    logMessage("Début de la distribution des revenus quotidiens");
    
    $pdo = getDBConnection();
    
    // Récupérer tous les investissements actifs
    $stmt = $pdo->query("
        SELECT i.*, u.id as user_id, u.balance
        FROM investments i
        JOIN users u ON i.user_id = u.id
        WHERE i.status = 'active'
        AND CURDATE() BETWEEN i.start_date AND i.end_date
        AND CURDATE() > i.start_date
    ");
    $investments = $stmt->fetchAll();
    
    logMessage("Nombre d'investissements actifs: " . count($investments));
    
    $pdo->beginTransaction();
    
    $totalDistributed = 0;
    $count = 0;
    
    foreach ($investments as $investment) {
        // Vérifier si le revenu du jour n'a pas déjà été distribué
        $stmt = $pdo->prepare("
            SELECT id FROM daily_earnings
            WHERE user_id = ? 
            AND investment_id = ? 
            AND earning_date = CURDATE()
        ");
        $stmt->execute([$investment['user_id'], $investment['id']]);
        
        if ($stmt->fetch()) {
            continue; // Déjà distribué aujourd'hui
        }
        
        // Vérifier que l'investissement a au moins 24h
        $stmt = $pdo->prepare("
            SELECT TIMESTAMPDIFF(HOUR, created_at, NOW()) as hours_elapsed
            FROM investments
            WHERE id = ?
        ");
        $stmt->execute([$investment['id']]);
        $timeCheck = $stmt->fetch();
        
        // Ne distribuer que si au moins 24h se sont écoulées
        if ($timeCheck && $timeCheck['hours_elapsed'] < 24) {
            continue; // Pas encore 24h
        }
        
        // Créer l'enregistrement de revenu quotidien
        $stmt = $pdo->prepare("
            INSERT INTO daily_earnings (user_id, investment_id, amount, earning_date, status)
            VALUES (?, ?, ?, CURDATE(), 'paid')
        ");
        $stmt->execute([
            $investment['user_id'],
            $investment['id'],
            $investment['daily_income']
        ]);
        
        // Ajouter au solde de l'utilisateur
        $stmt = $pdo->prepare("
            UPDATE users 
            SET balance = balance + ?, total_earned = total_earned + ?
            WHERE id = ?
        ");
        $stmt->execute([
            $investment['daily_income'],
            $investment['daily_income'],
            $investment['user_id']
        ]);
        
        // Créer une transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, type, amount, description, status, reference)
            VALUES (?, 'income', ?, ?, 'completed', ?)
        ");
        $stmt->execute([
            $investment['user_id'],
            $investment['daily_income'],
            "Revenu quotidien - " . $investment['package_name'],
            "DAILY-" . date('Ymd') . "-" . $investment['id']
        ]);
        
        // Créer une notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, 'success')
        ");
        $stmt->execute([
            $investment['user_id'],
            'Revenu quotidien reçu',
            "Vous avez reçu {$investment['daily_income']} FC de revenu quotidien"
        ]);
        
        $totalDistributed += $investment['daily_income'];
        $count++;
    }
    
    // Vérifier les investissements terminés
    $stmt = $pdo->query("
        UPDATE investments 
        SET status = 'completed'
        WHERE status = 'active' 
        AND end_date < CURDATE()
    ");
    $completedCount = $stmt->rowCount();
    
    if ($completedCount > 0) {
        logMessage("Investissements terminés: $completedCount");
    }
    
    $pdo->commit();
    
    logMessage("Distribution terminée: $count revenus distribués, total: $totalDistributed FC");
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logMessage("ERREUR: " . $e->getMessage());
    exit(1);
}

logMessage("Processus terminé avec succès");
exit(0);

?>

