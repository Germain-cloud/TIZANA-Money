# Guide d'Installation TIZ-ANA - Étapes Détaillées

## 📋 Prérequis

Avant de commencer, assurez-vous d'avoir :
- ✅ **XAMPP** (ou WAMP/MAMP) installé sur votre ordinateur
- ✅ **Navigateur web** (Chrome, Firefox, Edge)
- ✅ **Éditeur de texte** (Notepad++, VS Code, etc.)

## 🚀 Installation Étape par Étape

### ÉTAPE 1 : Installer XAMPP (si pas déjà installé)

1. Téléchargez XAMPP depuis : https://www.apachefriends.org/
2. Installez XAMPP dans `C:\xampp\` (par défaut)
3. Lancez le **XAMPP Control Panel**
4. Démarrez **Apache** et **MySQL** (cliquez sur "Start")

### ÉTAPE 2 : Copier les fichiers du projet

1. Copiez tous les fichiers du projet dans :
   ```
   C:\xampp\htdocs\tizana\
   ```

2. Structure des fichiers :
   ```
   C:\xampp\htdocs\tizana\
   ├── index.html
   ├── app.js
   ├── api.php
   ├── config.php
   ├── database.sql
   ├── .htaccess
   ├── cron_daily_earnings.php
   ├── README.md
   └── INSTALLATION.md (ce fichier)
   ```

### ÉTAPE 3 : Créer la base de données

#### Option A : Via phpMyAdmin (Recommandé)

1. Ouvrez votre navigateur et allez à :
   ```
   http://localhost/phpmyadmin
   ```

2. Cliquez sur **"Nouvelle base de données"** (ou "New" en haut à gauche)

3. Nommez la base de données : `tizana_db`
   - Choisissez l'interclassement : `utf8mb4_unicode_ci`
   - Cliquez sur **"Créer"**

4. Une fois la base créée, cliquez dessus dans le menu de gauche

5. Cliquez sur l'onglet **"Importer"** (ou "Import")

6. Cliquez sur **"Choisir un fichier"** et sélectionnez `database.sql`

7. Cliquez sur **"Exécuter"** en bas de la page

8. Attendez le message de succès : "Requête SQL exécutée avec succès"

#### Option B : Via la ligne de commande MySQL

1. Ouvrez l'invite de commande (CMD)
2. Naviguez vers le dossier MySQL de XAMPP :
   ```cmd
   cd C:\xampp\mysql\bin
   ```
3. Connectez-vous à MySQL :
   ```cmd
   mysql -u root
   ```
4. Exécutez les commandes :
   ```sql
   CREATE DATABASE tizana_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE tizana_db;
   SOURCE C:/xampp/htdocs/tizana/database.sql;
   EXIT;
   ```

### ÉTAPE 4 : Configurer config.php

1. Ouvrez le fichier `config.php` avec un éditeur de texte

2. Modifiez ces lignes selon votre configuration :

```php
// Si vous utilisez XAMPP avec les paramètres par défaut :
define('DB_HOST', 'localhost');
define('DB_NAME', 'tizana_db');
define('DB_USER', 'root');        // Par défaut dans XAMPP
define('DB_PASS', '');            // Par défaut vide dans XAMPP
define('APP_URL', 'http://localhost/tizana');
```

3. **IMPORTANT** : Changez la clé secrète pour la production :
```php
define('JWT_SECRET', 'changez-cette-cle-secrete-en-production-123456789');
```

4. Sauvegardez le fichier

### ÉTAPE 5 : Tester l'application

1. Ouvrez votre navigateur

2. Allez à l'adresse :
   ```
   http://localhost/tizana/index.html
   ```

3. Vous devriez voir la page d'accueil de TIZ-ANA

4. Testez l'inscription :
   - Cliquez sur **"Connexion"** en haut à droite
   - Cliquez sur **"S'inscrire"**
   - Remplissez le formulaire
   - Vous recevrez automatiquement 5,000 FC de bonus !

### ÉTAPE 6 : Vérifier que tout fonctionne

1. **Test de connexion** :
   - Inscrivez-vous avec un compte test
   - Connectez-vous
   - Vérifiez que le dashboard s'affiche

2. **Test de l'API** :
   - Ouvrez la console du navigateur (F12)
   - Allez dans l'onglet "Console"
   - Vérifiez qu'il n'y a pas d'erreurs

3. **Test de la base de données** :
   - Retournez dans phpMyAdmin
   - Vérifiez que la table `users` contient votre compte
   - Vérifiez que vous avez bien 5,000 FC dans le champ `balance`

## 🔧 Configuration Optionnelle : Revenus Quotidiens Automatiques

### Pour Windows (Tâche Planifiée)

1. Ouvrez le **Planificateur de tâches** Windows
2. Créez une **tâche de base**
3. Configurez :
   - **Nom** : TIZ-ANA Daily Earnings
   - **Déclencheur** : Quotidien à 00:00
   - **Action** : Démarrer un programme
   - **Programme** : `C:\xampp\php\php.exe`
   - **Arguments** : `C:\xampp\htdocs\tizana\cron_daily_earnings.php`

### Pour Linux/Mac (Cron)

1. Ouvrez le terminal
2. Éditez le crontab :
   ```bash
   crontab -e
   ```
3. Ajoutez cette ligne :
   ```bash
   0 0 * * * php /chemin/vers/tizana/cron_daily_earnings.php
   ```

## 🐛 Résolution des Problèmes

### Problème : "Erreur de connexion à la base de données"

**Solution** :
1. Vérifiez que MySQL est démarré dans XAMPP
2. Vérifiez les identifiants dans `config.php`
3. Vérifiez que la base de données `tizana_db` existe

### Problème : "404 Not Found" sur les appels API

**Solution** :
1. Vérifiez que le fichier `api.php` existe bien
2. Vérifiez que Apache est démarré
3. Essayez d'accéder directement : `http://localhost/tizana/api.php/packages`

### Problème : Les traductions ne fonctionnent pas

**Solution** :
1. Vérifiez que `app.js` est bien chargé (F12 > Network)
2. Ouvrez la console (F12) et vérifiez les erreurs JavaScript
3. Vérifiez que tous les fichiers sont présents

### Problème : "Access denied" pour MySQL

**Solution** :
1. Dans XAMPP, MySQL utilise `root` sans mot de passe par défaut
2. Si vous avez changé le mot de passe, mettez-le dans `config.php`
3. Ou réinitialisez MySQL dans XAMPP

### Problème : Les revenus quotidiens ne se distribuent pas

**Solution** :
1. Vérifiez que le cron job est configuré
2. Exécutez manuellement : `php cron_daily_earnings.php`
3. Vérifiez le fichier `cron.log` pour les erreurs

## ✅ Checklist de Vérification

Avant de considérer l'installation terminée, vérifiez :

- [ ] XAMPP est installé et fonctionne
- [ ] Apache et MySQL sont démarrés
- [ ] La base de données `tizana_db` existe
- [ ] Les tables sont créées (vérifier dans phpMyAdmin)
- [ ] Le fichier `config.php` est configuré
- [ ] Le site s'affiche à `http://localhost/tizana/index.html`
- [ ] L'inscription fonctionne
- [ ] La connexion fonctionne
- [ ] Le dashboard s'affiche après connexion
- [ ] Les packages VIP s'affichent
- [ ] Les traductions fonctionnent (bouton langue)
- [ ] Le mode sombre/clair fonctionne

## 🎯 Test Complet

1. **Créer un compte** :
   - Nom d'utilisateur : `testuser`
   - Email : `test@example.com`
   - Mot de passe : `test123`
   - Code de parrainage : (laissez vide pour le premier compte)

2. **Se connecter** avec ce compte

3. **Vérifier le solde** : Vous devriez avoir 5,000 FC

4. **Tester un investissement** :
   - Allez dans "Produit"
   - Cléez sur "ACHETER MAINTENANT" sur VIP1
   - Confirmez l'investissement
   - Vérifiez que votre solde a diminué

5. **Vérifier les transactions** :
   - Allez dans "Mon compte" > "Transactions"
   - Vous devriez voir vos transactions

## 📞 Support

Si vous rencontrez des problèmes :

1. Vérifiez les logs d'erreur :
   - Fichier `error.log` dans le dossier du projet
   - Console du navigateur (F12)

2. Vérifiez la configuration :
   - `config.php` est correctement configuré
   - La base de données existe et contient les tables

3. Vérifiez les permissions :
   - Les fichiers doivent être lisibles par Apache
   - Le dossier doit être accessible

## 🎉 Félicitations !

Votre plateforme TIZ-ANA est maintenant installée et prête à être utilisée !

**Prochaines étapes** :
- Personnalisez les couleurs et le design
- Configurez les commissions de parrainage
- Configurez le cron job pour les revenus quotidiens
- Testez toutes les fonctionnalités

---

**Besoin d'aide ?** Consultez le fichier `README.md` pour plus d'informations.

