/**
 * Application JavaScript pour TIZ-ANA
 * Gestion des appels API et interactions utilisateur
 */

const API_BASE_URL = 'api.php';

// Gestion de l'authentification
let currentUser = null;
let sessionToken = localStorage.getItem('session_token');

// Fonction pour faire des appels API
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };

    if (sessionToken) {
        options.headers['Authorization'] = `Bearer ${sessionToken}`;
    }

    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(`${API_BASE_URL}/${endpoint}`, options);
        
        // Vérifier si la réponse est JSON
        const contentType = response.headers.get('content-type');
        let result;
        
        if (contentType && contentType.includes('application/json')) {
            result = await response.json();
        } else {
            const text = await response.text();
            throw new Error(`Réponse non-JSON du serveur: ${text.substring(0, 100)}`);
        }
        
        if (!response.ok) {
            // Si c'est une erreur de connexion DB, donner plus de détails
            if (result.error && result.error.includes('base de données')) {
                console.error('Erreur de connexion à la base de données');
                console.error('Détails:', result.debug);
                alert('Erreur de connexion à la base de données. Vérifiez que MySQL est démarré et que la base de données existe.\n\nVoir: http://localhost/tizana/test_connection.php');
            }
            throw new Error(result.message || 'Erreur API');
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        
        // Si c'est une erreur de réseau, suggérer de vérifier la connexion
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            console.error('Erreur de réseau. Vérifiez que le serveur est démarré.');
            alert('Impossible de se connecter au serveur. Vérifiez que Apache est démarré dans XAMPP.');
        }
        
        throw error;
    }
}

// Fonction pour tester la connexion à l'API
async function testAPIConnection() {
    try {
        const result = await apiCall('packages');
        return result.success !== undefined;
    } catch (error) {
        console.error('Test de connexion API échoué:', error);
        return false;
    }
}

// Fonction pour charger le dashboard
async function loadDashboard() {
    try {
        const result = await apiCall('dashboard');
        if (result.success) {
            const data = result.data;
            
            // Mettre à jour les statistiques
            document.getElementById('user-balance').textContent = formatCurrency(data.user.balance);
            document.getElementById('total-earned').textContent = formatCurrency(data.user.total_earned || 0);
            document.getElementById('total-invested').textContent = formatCurrency(data.user.total_invested || 0);
            document.getElementById('today-earnings').textContent = formatCurrency(data.today_earnings || 0);
            document.getElementById('team-count').textContent = data.team_count || 0;
            
            // Afficher les investissements actifs
            displayActiveInvestments(data.active_investments || []);
            
            // Afficher les transactions récentes
            displayRecentTransactions(data.recent_transactions || []);
        }
    } catch (error) {
        console.error('Erreur chargement dashboard:', error);
    }
}

// Fonction pour afficher les investissements actifs
function displayActiveInvestments(investments) {
    const container = document.getElementById('active-investments-list');
    if (!container) return;
    
    if (investments.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 40px;">Aucun investissement actif</p>';
        return;
    }
    
    container.innerHTML = investments.map(inv => `
        <div class="investment-item">
            <div class="info">
                <h4>${inv.package_name || 'VIP' + inv.package_level}</h4>
                <p>Investi: ${formatCurrency(inv.amount)} | Revenu quotidien: ${formatCurrency(inv.daily_income)}</p>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">
                    Du ${formatDate(inv.start_date)} au ${formatDate(inv.end_date)}
                </p>
            </div>
            <div class="amount">${formatCurrency(inv.daily_income)}/jour</div>
        </div>
    `).join('');
}

// Fonction pour afficher les transactions récentes
function displayRecentTransactions(transactions) {
    const container = document.getElementById('recent-transactions-list');
    if (!container) return;
    
    if (transactions.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 40px;">Aucune transaction</p>';
        return;
    }
    
    container.innerHTML = transactions.map(tx => {
        const typeClass = tx.type;
        const typeLabels = {
            'deposit': 'Dépôt',
            'withdrawal': 'Retrait',
            'investment': 'Investissement',
            'income': 'Revenu',
            'referral_bonus': 'Bonus parrainage',
            'commission': 'Commission'
        };
        
        const amountClass = tx.type === 'withdrawal' ? 'negative' : 'positive';
        const amountSign = tx.type === 'withdrawal' ? '-' : '+';
        
        return `
            <div class="transaction-item">
                <div class="info">
                    <h4>${typeLabels[tx.type] || tx.type}</h4>
                    <p>${tx.description || ''}</p>
                    <p style="font-size: 0.8rem; color: var(--text-secondary);">
                        ${formatDateTime(tx.created_at)}
                    </p>
                </div>
                <div style="display: flex; align-items: center;">
                    <span class="type ${typeClass}">${typeLabels[tx.type] || tx.type}</span>
                    <div class="amount ${amountClass}">${amountSign}${formatCurrency(Math.abs(tx.amount))}</div>
                </div>
            </div>
        `;
    }).join('');
}

// Fonction pour charger les packages VIP
async function loadPackages() {
    try {
        const result = await apiCall('packages');
        if (result.success) {
            return result.data;
        }
    } catch (error) {
        console.error('Erreur chargement packages:', error);
    }
    return [];
}

// Fonction pour ouvrir la modale de paiement
function openPaymentModal(packageLevel, packageName, packagePrice) {
    const modal = document.getElementById('payment-overlay');
    const packageNameEl = document.getElementById('payment-package-name');
    const packagePriceEl = document.getElementById('payment-amount');
    
    if (packageNameEl) packageNameEl.textContent = packageName;
    if (packagePriceEl) packagePriceEl.textContent = formatCurrency(packagePrice);
    
    // Stocker les informations pour l'achat
    modal.dataset.packageLevel = packageLevel;
    modal.dataset.packagePrice = packagePrice;
    
    if (modal) modal.classList.add('active');
}

// Fonction pour fermer la modale de paiement
function closePaymentModal() {
    const modal = document.getElementById('payment-overlay');
    if (modal) modal.classList.remove('active');
}

// Fonction pour ouvrir la modale de dépôt
function openDepositModal() {
    const modal = document.getElementById('deposit-overlay');
    if (modal) modal.classList.add('active');
}

// Fonction pour fermer la modale de dépôt
function closeDepositModal() {
    const modal = document.getElementById('deposit-overlay');
    if (modal) modal.classList.remove('active');
}

// Fonction pour copier un numéro dans le presse-papier
function copyToClipboard(text, element) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = element.textContent;
        element.textContent = '✓ Copié!';
        element.style.background = 'rgba(76, 175, 80, 0.2)';
        setTimeout(() => {
            element.textContent = originalText;
            element.style.background = '';
        }, 2000);
    }).catch(() => {
        // Fallback pour les navigateurs plus anciens
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        
        const originalText = element.textContent;
        element.textContent = '✓ Copié!';
        setTimeout(() => {
            element.textContent = originalText;
        }, 2000);
    });
}

// Fonction pour créer un investissement
async function createInvestment(packageId) {
    try {
        const result = await apiCall('investments', 'POST', { package_id: packageId });
        if (result.success) {
            const message = currentLang === 'fr' ? 
                'Investissement créé avec succès! Vous recevrez vos revenus quotidiens après 24h.' :
                currentLang === 'en' ?
                'Investment created successfully! You will receive daily earnings after 24h.' :
                'Uwekezaji umeundwa kwa mafanikio! Utapokea mapato yako ya kila siku baada ya masaa 24.';
            alert(message);
            loadDashboard();
            closePaymentModal();
            return true;
        }
    } catch (error) {
        alert('Erreur: ' + error.message);
    }
    return false;
}

// Fonction pour créer un dépôt
async function createDeposit(amount, phone, reference, agentNumber) {
    try {
        const result = await apiCall('deposits', 'POST', {
            amount: amount,
            phone: phone,
            reference: reference,
            agent_number: agentNumber
        });
        if (result.success) {
            const message = currentLang === 'fr' ? 
                'Demande de recharge soumise avec succès! Votre solde sera crédité après vérification.' :
                currentLang === 'en' ?
                'Deposit request submitted successfully! Your balance will be credited after verification.' :
                'Ombi la kuongeza salio limewasilishwa! Salio lako litajazwa baada ya uthibitishaji.';
            alert(message);
            closeDepositModal();
            loadDashboard();
            return true;
        }
    } catch (error) {
        alert('Erreur: ' + error.message);
    }
    return false;
}

// Fonction pour charger l'équipe
async function loadTeam() {
    try {
        const result = await apiCall('team/members');
        if (result.success) {
            return result;
        }
    } catch (error) {
        console.error('Erreur chargement équipe:', error);
    }
    return { data: [], stats: {} };
}

// Fonction pour charger les transactions
async function loadTransactions(type = null, limit = 50) {
    try {
        const endpoint = `transactions${type ? '?type=' + type : ''}&limit=${limit}`;
        const result = await apiCall(endpoint);
        if (result.success) {
            return result.data;
        }
    } catch (error) {
        console.error('Erreur chargement transactions:', error);
    }
    return [];
}

// Fonction pour créer un retrait
async function createWithdrawal(amount, paymentMethod, accountDetails) {
    try {
        const result = await apiCall('withdrawals', 'POST', {
            amount: amount,
            payment_method: paymentMethod,
            account_details: accountDetails
        });
        if (result.success) {
            alert('Demande de retrait créée avec succès!');
            return true;
        }
    } catch (error) {
        alert('Erreur: ' + error.message);
    }
    return false;
}

// Fonction pour charger les notifications
async function loadNotifications(unreadOnly = false) {
    try {
        const endpoint = `notifications${unreadOnly ? '?unread_only=true' : ''}`;
        const result = await apiCall(endpoint);
        if (result.success) {
            return result;
        }
    } catch (error) {
        console.error('Erreur chargement notifications:', error);
    }
    return { data: [], unread_count: 0 };
}

// Fonction pour marquer une notification comme lue
async function markNotificationAsRead(notificationId) {
    try {
        const result = await apiCall(`notifications/${notificationId}`, 'PUT');
        return result.success;
    } catch (error) {
        console.error('Erreur:', error);
    }
    return false;
}

// Fonctions utilitaires
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'CDF',
        minimumFractionDigits: 0
    }).format(amount).replace('CDF', 'FC');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('fr-FR');
}

// Fonction pour afficher une section
function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    
    const section = document.getElementById(sectionId);
    const button = document.querySelector(`[data-section="${sectionId}"]`);
    
    if (section) section.classList.add('active');
    if (button) button.classList.add('active');
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Charger les données spécifiques à la section
    if (sectionId === 'maison') {
        loadDashboard();
    } else if (sectionId === 'monequipe') {
        loadTeamData();
    } else if (sectionId === 'moncompte') {
        loadAccountData();
    }
}

// Fonction pour charger les données de l'équipe
async function loadTeamData() {
    const result = await loadTeam();
    // Afficher les données dans la section équipe
    // (à implémenter dans le HTML)
}

// Fonction pour charger les données du compte
async function loadAccountData() {
    // Charger les transactions, retraits, etc.
    // (à implémenter dans le HTML)
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', async function() {
    // Tester la connexion API au démarrage
    const apiConnected = await testAPIConnection();
    if (!apiConnected) {
        console.warn('⚠️ API non accessible. Vérifiez la configuration.');
        // Afficher une notification discrète
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 193, 7, 0.95);
            color: #333;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 10000;
            max-width: 300px;
        `;
        notification.innerHTML = `
            <strong>⚠️ Connexion API</strong><br>
            Vérifiez que le serveur est démarré.<br>
            <a href="test_connection.php" style="color: #667eea; font-weight: bold;">Tester</a>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.5s';
            setTimeout(() => notification.remove(), 500);
        }, 5000);
    }
    
    if (sessionToken) {
        loadDashboard();
    }
});

// Exporter les fonctions pour utilisation globale
window.apiCall = apiCall;
window.loadDashboard = loadDashboard;
window.createInvestment = createInvestment;
window.createWithdrawal = createWithdrawal;
window.showSection = showSection;
window.formatCurrency = formatCurrency;

