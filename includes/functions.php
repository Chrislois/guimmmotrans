<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
require_once __DIR__ . '/db.php';

const CATEGORIES = [
    'maison' => 'Maison', 'villa' => 'Villa', 'appartement' => 'Appartement',
    'appartement_meuble' => 'Appartement meublé', 'parcelle' => 'Parcelle / terrain', 'commerce' => 'Local commercial',
];
const VILLES = ['Conakry', 'Coyah', 'Dubréka', 'Kindia', 'Mamou', 'Labé', 'Kankan', 'Faranah', 'Boké', 'Nzérékoré'];
const PERIODES = ['unique' => '', 'nuit' => ' / nuit', 'mois' => ' / mois', 'an' => ' / an'];
const STATUTS_ANNONCE = ['en_attente' => 'En attente', 'publiee' => 'Publiée', 'refusee' => 'Refusée', 'archivee' => 'Archivée'];
const STATUTS_DEMANDE = ['en_attente' => 'En attente', 'confirmee' => 'Confirmée', 'refusee' => 'Refusée', 'annulee' => 'Annulée'];
const TYPES_DEMANDE = ['reservation' => 'Réservation', 'achat' => "Offre d'achat", 'visite' => 'Demande de visite'];

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): never { header('Location: ' . (str_starts_with($p, '/') ? $p : url($p))); exit; }

function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Session expirée : rechargez la page et réessayez.');
    }
}

function user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT id,nom,email,telephone,role,statut FROM users WHERE id=?');
            $s->execute([$_SESSION['uid']]);
            $u = $s->fetch() ?: null;
            if ($u && $u['statut'] !== 'actif') { $u = null; unset($_SESSION['uid']); }
        }
    }
    return $u;
}
function require_role(string ...$roles): array {
    $u = user();
    if (!$u) redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    if (!in_array($u['role'], $roles, true)) { http_response_code(403); exit('Accès refusé.'); }
    return $u;
}
function home_for(string $role): string {
    return ['admin' => 'admin/', 'prestataire' => 'prestataire/', 'client' => 'client/'][$role] ?? '';
}

function prix_fmt(array $a): string {
    return number_format((int)$a['prix'], 0, ',', ' ') . ' GNF' . (PERIODES[$a['periode']] ?? '');
}
function photo_url(?string $chemin): ?string { return $chemin ? url(UPLOAD_URL . $chemin) : null; }
function badge(string $statut, array $labels): string {
    return '<span class="badge badge-' . e($statut) . '">' . e($labels[$statut] ?? $statut) . '</span>';
}

function carte_annonce(array $a): string {
    $img = photo_url($a['photo'] ?? null);
    $fond = $img ? '<img src="' . e($img) . '" alt="" loading="lazy">' : '<div class="ph" aria-hidden="true"></div>';
    $meta = [];
    if ($a['surface']) $meta[] = (int)$a['surface'] . ' m²';
    if ($a['chambres']) $meta[] = (int)$a['chambres'] . ' ch.';
    if ($a['salles_bain']) $meta[] = (int)$a['salles_bain'] . ' sdb';
    return '<a class="carte" href="' . url('annonce.php?id=' . (int)$a['id']) . '">'
        . '<div class="carte-img">' . $fond . '<span class="tag">' . ($a['transaction'] === 'vente' ? 'À vendre' : 'À louer') . '</span></div>'
        . '<div class="carte-corps"><p class="carte-prix">' . e(prix_fmt($a)) . '</p>'
        . '<h3>' . e($a['titre']) . '</h3>'
        . '<p class="muted">' . e(CATEGORIES[$a['categorie']] ?? '') . ' · ' . e($a['quartier']) . ', ' . e($a['ville']) . '</p>'
        . ($meta ? '<p class="muted">' . e(implode('  ·  ', $meta)) . '</p>' : '')
        . '</div></a>';
}

function page_header(string $titre, string $classe = ''): void {
    $u = user(); $f = flash();
    ?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titre) ?> · <?= e(SITE_NOM) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="<?= e($classe) ?>">
<header class="site-header">
  <div class="wrap bar">
    <a class="logo" href="<?= url() ?>"><?= e(SITE_NOM) ?></a>
    <nav class="nav" aria-label="Navigation principale">
      <a href="<?= url('annonces.php?transaction=location') ?>">Louer</a>
      <a href="<?= url('annonces.php?transaction=vente') ?>">Acheter</a>
      <a href="<?= url('annonces.php?categorie=parcelle') ?>">Parcelles</a>
    </nav>
    <div class="nav-user">
      <?php if ($u): ?>
        <a href="<?= url(home_for($u['role'])) ?>"><?= e($u['nom']) ?></a>
        <a class="btn btn-ghost" href="<?= url('logout.php') ?>">Déconnexion</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Connexion</a>
        <a class="btn" href="<?= url('register.php?role=prestataire') ?>">Publier une annonce</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main>
<?php if ($f): ?><div class="wrap"><div class="flash flash-<?= e($f[1]) ?>" role="status"><?= e($f[0]) ?></div></div><?php endif;
}

function page_footer(): void {
    ?></main>
<footer class="site-footer">
  <div class="wrap">
    <p><strong><?= e(SITE_NOM) ?></strong> — l'immobilier en Guinée, de Conakry à Nzérékoré.</p>
    <p class="muted">Chaque annonce est vérifiée par notre équipe avant publication.</p>
  </div>
</footer>
</body>
</html><?php
}
