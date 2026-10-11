<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$n = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
$stats = [
    'À valider' => $n("SELECT COUNT(*) FROM annonces WHERE statut='en_attente'"),
    'Annonces publiées' => $n("SELECT COUNT(*) FROM annonces WHERE statut='publiee'"),
    'Prestataires' => $n("SELECT COUNT(*) FROM users WHERE role='prestataire'"),
    'Clients' => $n("SELECT COUNT(*) FROM users WHERE role='client'"),
    'Demandes en attente' => $n("SELECT COUNT(*) FROM demandes WHERE statut='en_attente'"),
];
page_header('Administration', 'dash');
?>
<div class="wrap">
  <div class="dash-head"><h1>Administration</h1>
    <div><a class="btn" href="<?= url('admin/annonces.php?statut=en_attente') ?>">Valider les annonces</a>
    <a class="btn btn-ghost" href="<?= url('admin/users.php') ?>">Utilisateurs</a></div></div>
  <div class="stats">
    <?php foreach ($stats as $l => $v): ?><div class="stat"><strong><?= $v ?></strong><span><?= e($l) ?></span></div><?php endforeach; ?>
  </div>
</div>
<?php page_footer();
