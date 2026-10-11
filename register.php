<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect(home_for(user()['role']));

$role = ($_GET['role'] ?? $_POST['role'] ?? '') === 'prestataire' ? 'prestataire' : 'client';
$err = null; $v = ['nom' => '', 'email' => '', 'telephone' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $v['email'] = strtolower($v['email']);
    $mdp = $_POST['mot_de_passe'] ?? '';
    if ($v['nom'] === '' || mb_strlen($v['nom']) > 120) $err = 'Indiquez votre nom.';
    elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $err = 'Adresse e-mail invalide.';
    elseif (!preg_match('/^\+?[0-9 ]{8,20}$/', $v['telephone'])) $err = 'Numéro de téléphone invalide (ex. +224 6xx xx xx xx).';
    elseif (strlen($mdp) < 8) $err = 'Le mot de passe doit contenir au moins 8 caractères.';
    else {
        $s = db()->prepare('SELECT 1 FROM users WHERE email=?'); $s->execute([$v['email']]);
        if ($s->fetch()) $err = 'Un compte existe déjà avec cet e-mail.';
        else {
            db()->prepare('INSERT INTO users (nom,email,telephone,mot_de_passe,role) VALUES (?,?,?,?,?)')
                ->execute([$v['nom'], $v['email'], $v['telephone'], password_hash($mdp, PASSWORD_DEFAULT), $role]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)db()->lastInsertId();
            flash('Bienvenue ! Votre compte est créé.');
            redirect(home_for($role));
        }
    }
}
page_header('Inscription', 'auth');
?>
<div class="wrap auth-box">
  <h1><?= $role === 'prestataire' ? 'Créer un compte prestataire' : 'Créer un compte client' ?></h1>
  <p class="muted"><?= $role === 'prestataire' ? 'Pour publier vos biens à louer ou à vendre.' : 'Pour réserver, demander une visite ou faire une offre.' ?>
    <a href="<?= url('register.php?role=' . ($role === 'prestataire' ? 'client' : 'prestataire')) ?>"><?= $role === 'prestataire' ? 'Je cherche un bien' : 'Je propose un bien' ?></a></p>
  <?php if ($err): ?><div class="flash flash-err" role="alert"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="role" value="<?= e($role) ?>">
    <label for="nom">Nom complet</label>
    <input id="nom" name="nom" required value="<?= e($v['nom']) ?>" autocomplete="name">
    <label for="email">Adresse e-mail</label>
    <input id="email" type="email" name="email" required value="<?= e($v['email']) ?>" autocomplete="email">
    <label for="tel">Téléphone</label>
    <input id="tel" type="tel" name="telephone" required value="<?= e($v['telephone']) ?>" placeholder="+224 6xx xx xx xx" autocomplete="tel">
    <label for="mdp">Mot de passe (8 caractères minimum)</label>
    <input id="mdp" type="password" name="mot_de_passe" required minlength="8" autocomplete="new-password">
    <button class="btn btn-lg" type="submit">Créer mon compte</button>
  </form>
</div>
<?php page_footer();
