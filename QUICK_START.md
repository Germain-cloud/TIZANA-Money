# 🚀 Guide de Démarrage Rapide - TIZ-ANA

## Installation en 5 Minutes

### 1️⃣ Démarrer XAMPP
- Ouvrez **XAMPP Control Panel**
- Cliquez sur **Start** pour **Apache** et **MySQL**

### 2️⃣ Créer la Base de Données
1. Allez sur : `http://localhost/phpmyadmin`
2. Cliquez sur **"Nouvelle base de données"**
3. Nom : `tizana_db` → **Créer**
4. Sélectionnez `tizana_db` dans le menu gauche
5. Onglet **"Importer"** → Choisir `database.sql` → **Exécuter**

### 3️⃣ Configurer
Ouvrez `config.php` et vérifiez :
```php
define('DB_USER', 'root');
define('DB_PASS', '');  // Vide par défaut dans XAMPP
```

### 4️⃣ Accéder au Site
Ouvrez : `http://localhost/tizana/index.html`

### 5️⃣ Tester
- Cliquez sur **"Connexion"** → **"S'inscrire"**
- Créez un compte test
- Connectez-vous et explorez !

## ✅ C'est tout !

Votre plateforme est prête. Consultez `INSTALLATION.md` pour plus de détails.

