<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect(home_for(user()['role']));

$next = $_GET['next'] ?? $_POST['next'] ?? '';
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = db()->prepare('SELECT * FROM users WHERE email=?');
    $s->execute([strtolower(trim($_POST['email'] ?? ''))]);
    $row = $s->fetch();
    if (!$row || !password_verify($_POST['mot_de_passe'] ?? '', $row['mot_de_passe'])) $err = 'E-mail ou mot de passe incorrect.';
    elseif ($row['statut'] !== 'actif') $err = 'Ce compte est suspendu. Contactez l\'administrateur.';
    else {
        session_regenerate_id(true);
        $_SESSION['uid'] = $row['id'];
        // On n'accepte qu'un chemin interne comme destination
        $ok = $next !== '' && $next[0] === '/' && !str_starts_with($next, '//') && !str_contains($next, '\\');
        redirect($ok ? $next : home_for($row['role']));
    }
}
page_header('Connexion', 'auth');
?>
<div class="wrap auth-box">
  <h1>Connexion</h1>
  <?php if ($err): ?><div class="flash flash-err" role="alert"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
    <label for="email">Adresse e-mail</label>
    <input id="email" type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
    <label for="mdp">Mot de passe</label>
    <input id="mdp" type="password" name="mot_de_passe" required autocomplete="current-password">
    <button class="btn btn-lg" type="submit">Se connecter</button>
  </form>
  <p class="muted">Pas encore de compte ? <a href="<?= url('register.php') ?>">Inscrivez-vous</a></p>
</div>
<?php page_footer();
