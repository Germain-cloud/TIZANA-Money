<?php
/**
 * API REST pour TIZ-ANA
 * Gestion de toutes les opérations backend
 */

require_once 'config.php';

// Récupération de la méthode et de l'URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/api.php', '', $uri);
$uri = trim($uri, '/');
$segments = explode('/', $uri);

// Récupération des données
$input = json_decode(file_get_contents('php://input'), true);
$input = $input ?? [];

// Routeur simple
$action = $segments[0] ?? '';
$resource = $segments[1] ?? '';

try {
    switch ($action) {
        case 'auth':
            handleAuth($method, $resource, $input);
            break;
        case 'user':
            handleUser($method, $resource, $input);
            break;
        case 'packages':
            handlePackages($method, $resource, $input);
            break;
        case 'investments':
            handleInvestments($method, $resource, $input);
            break;
        case 'transactions':
            handleTransactions($method, $resource, $input);
            break;
        case 'withdrawals':
            handleWithdrawals($method, $resource, $input);
            break;
        case 'team':
            handleTeam($method, $resource, $input);
            break;
        case 'earnings':
            handleEarnings($method, $resource, $input);
            break;
        case 'notifications':
            handleNotifications($method, $resource, $input);
            break;
        case 'dashboard':
            handleDashboard($method, $resource, $input);
            break;
        case 'deposits':
            handleDeposits($method, $resource, $input);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Route non trouvée'], 404);
    }
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erreur serveur',
        'error' => $e->getMessage()
    ], 500);
}

// ==================== GESTION DE L'AUTHENTIFICATION ====================

function handleAuth($method, $resource, $input) {
    $pdo = getDBConnection();
    
    switch ($resource) {
        case 'register':
            if ($method !== 'POST') {
                jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }
            
            $username = $input['username'] ?? '';
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            $referral_code = $input['referral_code'] ?? '';
            
            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                jsonResponse(['success' => false, 'message' => 'Tous les champs sont requis'], 400);
            }
            
            if (!isValidEmail($email)) {
                jsonResponse(['success' => false, 'message' => 'Email invalide'], 400);
            }
            
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                jsonResponse(['success' => false, 'message' => 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères'], 400);
            }
            
            // Vérifier si l'utilisateur existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Nom d\'utilisateur ou email déjà utilisé'], 400);
            }
            
            // Gérer le code de parrainage
            $referred_by = null;
            if (!empty($referral_code)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                $stmt->execute([$referral_code]);
                $referrer = $stmt->fetch();
                if ($referrer) {
                    $referred_by = $referrer['id'];
                }
            }
            
            // Générer un code de parrainage unique
            $user_referral_code = generateReferralCode($username);
            $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
            $stmt->execute([$user_referral_code]);
            while ($stmt->fetch()) {
                $user_referral_code = generateReferralCode($username . rand(1, 999));
                $stmt->execute([$user_referral_code]);
            }
            
            // Créer l'utilisateur
            $hashed_password = hashPassword($password);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, referral_code, referred_by, balance)
                VALUES (?, ?, ?, ?, ?, 5000.00)
            ");
            $stmt->execute([$username, $email, $hashed_password, $user_referral_code, $referred_by]);
            $user_id = $pdo->lastInsertId();
            
            // Créer une transaction de bonus d'inscription
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, type, amount, description, status)
                VALUES (?, 'deposit', 5000.00, 'Bonus d\'inscription', 'completed')
            ");
            $stmt->execute([$user_id]);
            
            // Si parrainé, ajouter au réseau
            if ($referred_by) {
                $stmt = $pdo->prepare("
                    INSERT INTO team_members (user_id, referred_user_id, level)
                    VALUES (?, ?, 1)
                ");
                $stmt->execute([$referred_by, $user_id]);
            }
            
            jsonResponse([
                'success' => true,
                'message' => 'Inscription réussie',
                'user_id' => $user_id
            ], 201);
            break;
            
        case 'login':
            if ($method !== 'POST') {
                jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }
            
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                jsonResponse(['success' => false, 'message' => 'Nom d\'utilisateur et mot de passe requis'], 400);
            }
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user || !verifyPassword($password, $user['password'])) {
                jsonResponse(['success' => false, 'message' => 'Identifiants incorrects'], 401);
            }
            
            if ($user['status'] !== 'active') {
                jsonResponse(['success' => false, 'message' => 'Compte désactivé'], 403);
            }
            
            // Créer une session
            $token = generateSessionToken();
            $expires_at = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $token, $ip_address, $user_agent, $expires_at]);
            
            // Retourner les informations utilisateur (sans le mot de passe)
            unset($user['password']);
            jsonResponse([
                'success' => true,
                'message' => 'Connexion réussie',
                'token' => $token,
                'user' => $user
            ]);
            break;
            
        case 'logout':
            $user = getCurrentUser();
            if (!$user) {
                jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }
            
            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$token]);
            
            jsonResponse(['success' => true, 'message' => 'Déconnexion réussie']);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Action non trouvée'], 404);
    }
}

// ==================== GESTION DES UTILISATEURS ====================

function handleUser($method, $resource, $input) {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
    }
    
    $pdo = getDBConnection();
    
    switch ($resource) {
        case 'profile':
            if ($method === 'GET') {
                $stmt = $pdo->prepare("
                    SELECT id, username, email, full_name, phone, referral_code, balance, 
                           total_earned, total_invested, created_at
                    FROM users WHERE id = ?
                ");
                $stmt->execute([$user['user_id']]);
                $profile = $stmt->fetch();
                
                jsonResponse(['success' => true, 'data' => $profile]);
            } elseif ($method === 'PUT') {
                $full_name = $input['full_name'] ?? null;
                $phone = $input['phone'] ?? null;
                
                $stmt = $pdo->prepare("
                    UPDATE users SET full_name = ?, phone = ? WHERE id = ?
                ");
                $stmt->execute([$full_name, $phone, $user['user_id']]);
                
                jsonResponse(['success' => true, 'message' => 'Profil mis à jour']);
            }
            break;
            
        case 'change-password':
            if ($method === 'POST') {
                $current_password = $input['current_password'] ?? '';
                $new_password = $input['new_password'] ?? '';
                
                if (empty($current_password) || empty($new_password)) {
                    jsonResponse(['success' => false, 'message' => 'Tous les champs sont requis'], 400);
                }
                
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user['user_id']]);
                $user_data = $stmt->fetch();
                
                if (!verifyPassword($current_password, $user_data['password'])) {
                    jsonResponse(['success' => false, 'message' => 'Mot de passe actuel incorrect'], 400);
                }
                
                $hashed_password = hashPassword($new_password);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user['user_id']]);
                
                jsonResponse(['success' => true, 'message' => 'Mot de passe modifié']);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Action non trouvée'], 404);
    }
}

// ==================== GESTION DES PACKAGES ====================

function handlePackages($method, $resource, $input) {
    $pdo = getDBConnection();
    
    if ($method === 'GET' && empty($resource)) {
        $stmt = $pdo->query("SELECT * FROM vip_packages WHERE status = 'active' ORDER BY package_level");
        $packages = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $packages]);
    } elseif ($method === 'GET' && is_numeric($resource)) {
        $stmt = $pdo->prepare("SELECT * FROM vip_packages WHERE id = ?");
        $stmt->execute([$resource]);
        $package = $stmt->fetch();
        
        if (!$package) {
            jsonResponse(['success' => false, 'message' => 'Package non trouvé'], 404);
        }
        
        jsonResponse(['success' => true, 'data' => $package]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }
}

// ==================== GESTION DES INVESTISSEMENTS ====================

function handleInvestments($method, $resource, $input) {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
    }
    
    $pdo = getDBConnection();
    
    if ($method === 'GET' && empty($resource)) {
        $stmt = $pdo->prepare("
            SELECT i.*, vp.package_name, vp.package_level
            FROM investments i
            JOIN vip_packages vp ON i.package_id = vp.id
            WHERE i.user_id = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$user['user_id']]);
        $investments = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $investments]);
    } elseif ($method === 'POST' && empty($resource)) {
        $package_id = $input['package_id'] ?? 0;
        
        if (empty($package_id)) {
            jsonResponse(['success' => false, 'message' => 'Package ID requis'], 400);
        }
        
        // Récupérer le package
        $stmt = $pdo->prepare("SELECT * FROM vip_packages WHERE id = ? AND status = 'active'");
        $stmt->execute([$package_id]);
        $package = $stmt->fetch();
        
        if (!$package) {
            jsonResponse(['success' => false, 'message' => 'Package non trouvé'], 404);
        }
        
        // Vérifier le solde
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user['user_id']]);
        $user_data = $stmt->fetch();
        
        if ($user_data['balance'] < $package['price']) {
            jsonResponse(['success' => false, 'message' => 'Solde insuffisant'], 400);
        }
        
        // Créer l'investissement
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$package['duration_days']} days"));
        
        $pdo->beginTransaction();
        try {
            // Déduire le montant du solde
            $stmt = $pdo->prepare("
                UPDATE users 
                SET balance = balance - ?, total_invested = total_invested + ?
                WHERE id = ?
            ");
            $stmt->execute([$package['price'], $package['price'], $user['user_id']]);
            
            // Créer l'investissement
            $stmt = $pdo->prepare("
                INSERT INTO investments (user_id, package_id, amount, daily_income, total_income, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['user_id'],
                $package_id,
                $package['price'],
                $package['daily_income'],
                $package['total_income'],
                $start_date,
                $end_date
            ]);
            $investment_id = $pdo->lastInsertId();
            
            // Créer la transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, type, amount, description, status, reference)
                VALUES (?, 'investment', ?, ?, 'completed', ?)
            ");
            $stmt->execute([
                $user['user_id'],
                $package['price'],
                "Investissement dans {$package['package_name']}",
                "INV-{$investment_id}"
            ]);
            
            // Calculer les commissions de parrainage
            calculateReferralCommissions($pdo, $user['user_id'], $package['price'], $investment_id);
            
            $pdo->commit();
            
            jsonResponse([
                'success' => true,
                'message' => 'Investissement créé avec succès',
                'investment_id' => $investment_id
            ], 201);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }
}

// ==================== GESTION DES TRANSACTIONS ====================

function handleTransactions($method, $resource, $input) {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
    }
    
    $pdo = getDBConnection();
    
    if ($method === 'GET' && empty($resource)) {
        $type = $_GET['type'] ?? null;
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $sql = "
            SELECT * FROM transactions 
            WHERE user_id = ?
        ";
        $params = [$user['user_id']];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();
        
        // Compter le total
        $count_sql = "SELECT COUNT(*) as total FROM transactions WHERE user_id = ?";
        if ($type) {
            $count_sql .= " AND type = ?";
        }
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute(array_slice($params, 0, $type ? 2 : 1));
        $total = $stmt->fetch()['total'];
        
        jsonResponse([
            'success' => true,
            'data' => $transactions,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }
}

// ==================== GESTION DES RETRAITS ====================

function handleWithdrawals($method, $resource, $input) {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
    }
    
    $pdo = getDBConnection();
    
    if ($method === 'GET' && empty($resource)) {
        $stmt = $pdo->prepare("
            SELECT * FROM withdrawals 
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user['user_id']]);
        $withdrawals = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $withdrawals]);
    } elseif ($method === 'POST' && empty($resource)) {
        $amount = floatval($input['amount'] ?? 0);
        $payment_method = $input['payment_method'] ?? '';
        $account_details = $input['account_details'] ?? '';
        
        if ($amount < MIN_WITHDRAWAL) {
            jsonResponse(['success' => false, 'message' => "Le montant minimum est " . MIN_WITHDRAWAL . " FC"], 400);
        }
        
        if ($amount > MAX_WITHDRAWAL) {
            jsonResponse(['success' => false, 'message' => "Le montant maximum est " . MAX_WITHDRAWAL . " FC"], 400);
        }
        
        // Vérifier le solde
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user['user_id']]);
        $user_data = $stmt->fetch();
        
        if ($user_data['balance'] < $amount) {
            jsonResponse(['success' => false, 'message' => 'Solde insuffisant'], 400);
        }
        
        // Créer la demande de retrait
        $pdo->beginTransaction();
        try {
            // Déduire le montant du solde
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user['user_id']]);
            
            // Créer le retrait
            $stmt = $pdo->prepare("
                INSERT INTO withdrawals (user_id, amount, payment_method, account_details)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user['user_id'], $amount, $payment_method, $account_details]);
            $withdrawal_id = $pdo->lastInsertId();
            
            // Créer la transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, type, amount, description, status, reference)
                VALUES (?, 'withdrawal', ?, ?, 'pending', ?)
            ");
            $stmt->execute([
                $user['user_id'],
                $amount,
                "Demande de retrait via $payment_method",
                "WD-{$withdrawal_id}"
            ]);
            
            $pdo->commit();
            
            jsonResponse([
                'success' => true,
                'message' => 'Demande de retrait créée',
                'withdrawal_id' => $withdrawal_id
            ], 201);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }
}

// ==================== GESTION DE L'ÉQUIPE ====================

function handleTeam($method, $resource, $input) {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
    }
    
    $pdo = getDBConnection();
    
    if ($method === 'GET' && $resource === 'members') {
        $level = $_GET['level'] ?? null;
        
        $sql = "
            SELECT u.id, u.username, u.email, u.created_at, u.balance, u.total_invested,
                   tm.level, tm.total_commission
            FROM team_members tm
            JOIN users u ON tm.referred_user_id = u.id
            WHERE tm.user_id = ?
        ";
        $params = [$user['user_id']];
        
        if ($level) {
            $sql .= " AND tm.level = ?";
            $params[] = $level;
        }
        
        $sql .= " ORDER BY tm.level, u.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $members = $stmt->fetchAll();
        
        // Statistiques de l'équipe
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_members,
                SUM(CASE WHEN level = 1 THEN 1 ELSE 0 END) as level1_count,
                SUM(CASE WHEN level = 2 THEN 1 ELSE 0 END) as level2_count,
                SUM(CASE WHEN level = 3 THEN 1 ELSE 0 END) as level3_count,
                SUM(total_commission) as total_commissions
            FROM team_members
            WHERE user_id = ?
        ");
        $stmt->execute([$user['user_id']]);
        $stats = $stmt->fetch();
        
        jsonResponse([
            'success' => true,
            'data' => $members,
            'stats' => $stats
        ]);
    } elseif ($method === 'GET' && $resource === 'commissions') {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username as from_username
            FROM commissions c
            JOIN users u ON c.from_user_id = u.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$user['user_id']]);
        $commissions = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $commissions]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Action non trouvée'], 404);
    }
}

// ==================== GESTION DES REVENUS ====================

function handleEarnings($method, $resource, $input) {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
    }
    
    $pdo = getDBConnection();
    
    if ($method === 'GET' && empty($resource)) {
        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT de.*, i.amount as investment_amount, vp.package_name
            FROM daily_earnings de
            JOIN investments i ON de.investment_id = i.id
            JOIN vip_packages vp ON i.package_id = vp.id
            WHERE de.user_id = ? 
            AND de.earning_date BETWEEN ? AND ?
            ORDER BY de.earning_date DESC
        ");
        $stmt->execute([$user['user_id'], $date_from, $date_to]);
        $earnings = $stmt->fetchAll();
        
        // Total des revenus
        $stmt = $pdo->prepare("
            SELECT SUM(amount) as total FROM daily_earnings
            WHERE user_id = ? AND status = 'paid'
        ");
        $stmt->execute([$user['user_id']]);
        $total = $stmt->fetch()['total'] ?? 0;
        
        jsonResponse([
            'success' => true,
            'data' => $earnings,
            'total' => $total
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }
}

// ==================== GESTION DES NOTIFICATIONS ====================

function handleNotifications($method, $resource, $input) {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
    }
    
    $pdo = getDBConnection();
    
    if ($method === 'GET' && empty($resource)) {
        $unread_only = $_GET['unread_only'] ?? false;
        
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$user['user_id']];
        
        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $notifications = $stmt->fetchAll();
        
        // Compter les non lues
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user['user_id']]);
        $unread_count = $stmt->fetch()['count'];
        
        jsonResponse([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unread_count
        ]);
    } elseif ($method === 'PUT' && is_numeric($resource)) {
        // Marquer comme lu
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$resource, $user['user_id']]);
        
        jsonResponse(['success' => true, 'message' => 'Notification marquée comme lue']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }
}

// ==================== GESTION DU DASHBOARD ====================

function handleDashboard($method, $resource, $input) {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
    }
    
    $pdo = getDBConnection();
    
    if ($method === 'GET' && empty($resource)) {
        $user_id = $user['user_id'];
        
        // Informations utilisateur
        $stmt = $pdo->prepare("
            SELECT balance, total_earned, total_invested, referral_code
            FROM users WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        // Investissements actifs
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, SUM(amount) as total
            FROM investments
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$user_id]);
        $active_investments = $stmt->fetch();
        
        // Revenus du jour
        $stmt = $pdo->prepare("
            SELECT SUM(amount) as total
            FROM daily_earnings
            WHERE user_id = ? AND earning_date = CURDATE() AND status = 'paid'
        ");
        $stmt->execute([$user_id]);
        $today_earnings = $stmt->fetch()['total'] ?? 0;
        
        // Revenus du mois
        $stmt = $pdo->prepare("
            SELECT SUM(amount) as total
            FROM daily_earnings
            WHERE user_id = ? 
            AND MONTH(earning_date) = MONTH(CURDATE())
            AND YEAR(earning_date) = YEAR(CURDATE())
            AND status = 'paid'
        ");
        $stmt->execute([$user_id]);
        $month_earnings = $stmt->fetch()['total'] ?? 0;
        
        // Membres de l'équipe
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM team_members
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $team_count = $stmt->fetch()['count'];
        
        // Transactions récentes
        $stmt = $pdo->prepare("
            SELECT * FROM transactions
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $recent_transactions = $stmt->fetchAll();
        
        jsonResponse([
            'success' => true,
            'data' => [
                'user' => $user_data,
                'active_investments' => $active_investments,
                'today_earnings' => $today_earnings,
                'month_earnings' => $month_earnings,
                'team_count' => $team_count,
                'recent_transactions' => $recent_transactions
            ]
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }
}

// ==================== FONCTIONS UTILITAIRES ====================

function calculateReferralCommissions($pdo, $user_id, $investment_amount, $investment_id) {
    // Récupérer les niveaux de parrainage (jusqu'à 3 niveaux)
    $levels = [
        1 => REFERRAL_LEVEL1_COMMISSION,
        2 => REFERRAL_LEVEL2_COMMISSION,
        3 => REFERRAL_LEVEL3_COMMISSION
    ];
    
    $current_user_id = $user_id;
    
    foreach ($levels as $level => $commission_rate) {
        // Trouver le parrain
        $stmt = $pdo->prepare("
            SELECT user_id FROM team_members
            WHERE referred_user_id = ? AND level = 1
        ");
        $stmt->execute([$current_user_id]);
        $referrer = $stmt->fetch();
        
        if (!$referrer) {
            break; // Plus de parrain
        }
        
        $referrer_id = $referrer['user_id'];
        $commission_amount = ($investment_amount * $commission_rate) / 100;
        
        // Créer la commission
        $stmt = $pdo->prepare("
            INSERT INTO commissions (user_id, from_user_id, from_investment_id, amount, level, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$referrer_id, $current_user_id, $investment_id, $commission_amount, $level]);
        
        // Ajouter au solde
        $stmt = $pdo->prepare("
            UPDATE users SET balance = balance + ? WHERE id = ?
        ");
        $stmt->execute([$commission_amount, $referrer_id]);
        
        // Mettre à jour le total de commission
        $stmt = $pdo->prepare("
            UPDATE team_members 
            SET total_commission = total_commission + ?
            WHERE user_id = ? AND referred_user_id = ?
        ");
        $stmt->execute([$commission_amount, $referrer_id, $current_user_id]);
        
        // Créer une notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, 'success')
        ");
        $stmt->execute([
            $referrer_id,
            'Nouvelle commission',
            "Vous avez reçu {$commission_amount} FC de commission niveau {$level}"
        ]);
        
        $current_user_id = $referrer_id;
    }
}

?>

