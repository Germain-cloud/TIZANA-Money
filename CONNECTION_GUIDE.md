# 🔌 Guide de Connexion à la Base de Données - TIZ-ANA

## Méthode 1 : Installation Automatique (Recommandé)

### Étape 1 : Vérifier que XAMPP est démarré
1. Ouvrez **XAMPP Control Panel**
2. Vérifiez que **Apache** et **MySQL** sont en vert (démarrés)
3. Si ce n'est pas le cas, cliquez sur **"Start"** pour chacun

### Étape 2 : Installation automatique
1. Ouvrez votre navigateur
2. Allez à : `http://localhost/tizana/setup_database.php`
3. Remplissez le formulaire avec vos paramètres MySQL :
   - **Hôte** : `localhost`
   - **Base de données** : `tizana_db`
   - **Utilisateur** : `root` (par défaut dans XAMPP)
   - **Mot de passe** : (laissez vide par défaut dans XAMPP)
4. Cliquez sur **"Installer la Base de Données"**
5. Attendez le message de succès ✅

### Étape 3 : Vérifier la connexion
1. Allez à : `http://localhost/tizana/test_connection.php`
2. Vérifiez que tous les éléments sont verts ✅

## Méthode 2 : Installation Manuelle

### Étape 1 : Créer la base de données dans phpMyAdmin

1. Ouvrez phpMyAdmin : `http://localhost/phpmyadmin`

2. Cliquez sur **"Nouvelle base de données"** (ou "New" en haut à gauche)

3. Configurez :
   - **Nom** : `tizana_db`
   - **Interclassement** : `utf8mb4_unicode_ci`
   - Cliquez sur **"Créer"**

### Étape 2 : Importer les tables

1. Dans phpMyAdmin, sélectionnez la base `tizana_db` dans le menu de gauche

2. Cliquez sur l'onglet **"Importer"** (ou "Import")

3. Cliquez sur **"Choisir un fichier"**

4. Sélectionnez le fichier `database.sql` dans votre dossier du projet

5. Cliquez sur **"Exécuter"** en bas de la page

6. Attendez le message : **"Requête SQL exécutée avec succès"**

### Étape 3 : Configurer config.php

1. Ouvrez le fichier `config.php` avec un éditeur de texte

2. Vérifiez/modifiez ces lignes :

```php
define('DB_HOST', 'localhost');     // Hôte MySQL
define('DB_NAME', 'tizana_db');     // Nom de la base de données
define('DB_USER', 'root');          // Votre utilisateur MySQL
define('DB_PASS', '');              // Votre mot de passe MySQL (vide par défaut)
```

3. Sauvegardez le fichier

### Étape 4 : Tester la connexion

1. Allez à : `http://localhost/tizana/test_connection.php`
2. Vérifiez que la connexion fonctionne ✅

## 🧪 Tester la Connexion

### Option 1 : Script de test automatique

Ouvrez : `http://localhost/tizana/test_connection.php`

Ce script vérifie :
- ✅ La connexion à MySQL
- ✅ L'existence de la base de données
- ✅ La présence des tables
- ✅ Les packages VIP
- ✅ Les permissions d'écriture/lecture

### Option 2 : Test via l'API

1. Allez à : `http://localhost/tizana/index.html`
2. Ouvrez la console du navigateur (F12)
3. Vous devriez voir les données se charger sans erreur

### Option 3 : Test direct PHP

Créez un fichier `test.php` :

```php
<?php
require_once 'config.php';
try {
    $pdo = getDBConnection();
    echo "✅ Connexion réussie !";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    echo "<br>Tables créées : OK";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>
```

## ❌ Résolution des Problèmes

### Erreur : "Access denied for user"

**Cause** : Mauvais identifiant ou mot de passe MySQL

**Solution** :
1. Vérifiez `config.php`
2. Par défaut dans XAMPP : `root` / (vide)
3. Si vous avez changé le mot de passe MySQL, mettez-le dans `config.php`

### Erreur : "Unknown database 'tizana_db'"

**Cause** : La base de données n'existe pas

**Solution** :
1. Allez dans phpMyAdmin
2. Créez la base de données `tizana_db`
3. Ou utilisez `setup_database.php` pour le faire automatiquement

### Erreur : "Connection refused" ou "Can't connect"

**Cause** : MySQL n'est pas démarré

**Solution** :
1. Ouvrez XAMPP Control Panel
2. Cliquez sur **"Start"** pour MySQL
3. Attendez que le statut passe au vert

### Erreur : "Table doesn't exist"

**Cause** : Les tables n'ont pas été importées

**Solution** :
1. Allez dans phpMyAdmin
2. Sélectionnez `tizana_db`
3. Importez le fichier `database.sql`
4. Ou utilisez `setup_database.php`

### Erreur : "PDO extension not found"

**Cause** : Extension PDO non activée dans PHP

**Solution** :
1. Ouvrez `php.ini` (dans XAMPP : `C:\xampp\php\php.ini`)
2. Cherchez et décommentez (retirez le `;`) :
   ```ini
   extension=pdo_mysql
   ```
3. Redémarrez Apache dans XAMPP

## ✅ Vérification Complète

Après l'installation, vérifiez que :

- [ ] MySQL est démarré dans XAMPP
- [ ] La base de données `tizana_db` existe
- [ ] Les tables sont créées (voir dans phpMyAdmin)
- [ ] `config.php` est correctement configuré
- [ ] `test_connection.php` affiche tout en vert
- [ ] L'API fonctionne (pas d'erreurs dans la console)

## 🔗 Liens Utiles

- **Test de connexion** : `http://localhost/tizana/test_connection.php`
- **Installation automatique** : `http://localhost/tizana/setup_database.php`
- **Vérification complète** : `http://localhost/tizana/check_setup.php`
- **phpMyAdmin** : `http://localhost/phpmyadmin`

## 📝 Notes

- Par défaut dans XAMPP, MySQL utilise :
  - Utilisateur : `root`
  - Mot de passe : (vide)
  
- La connexion est réutilisée automatiquement pour améliorer les performances

- Les erreurs sont loggées dans `error.log` (dans le dossier du projet)

---

**Besoin d'aide ?** Consultez `INSTALLATION.md` pour plus de détails.

