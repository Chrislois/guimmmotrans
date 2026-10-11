<?php
// Usage (une seule fois, en ligne de commande) :
//   php database/create_admin.php "Nom Admin" admin@exemple.com "MotDePasseSolide"
// Puis supprimez ce fichier du serveur.
if (PHP_SAPI !== 'cli') exit('CLI uniquement.');
require_once __DIR__ . '/../includes/functions.php';
[$_, $nom, $email, $mdp] = $argv + [null, null, null, null];
if (!$nom || !$email || !$mdp) exit("Usage : php create_admin.php \"Nom\" email \"mot de passe\"\n");
db()->prepare("INSERT INTO users (nom,email,telephone,mot_de_passe,role) VALUES (?,?,?,?, 'admin')")
    ->execute([$nom, $email, '-', password_hash($mdp, PASSWORD_DEFAULT)]);
echo "Administrateur créé.\n";
