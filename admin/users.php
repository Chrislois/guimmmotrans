<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$admin['id']) flash('Vous ne pouvez pas modifier votre propre compte.', 'err');
    else {
        db()->prepare("UPDATE users SET statut=IF(statut='actif','suspendu','actif') WHERE id=? AND role<>'admin'")->execute([$id]);
        flash('Statut du compte mis à jour.');
    }
    redirect('admin/users.php');
}
$users = db()->query("SELECT u.*, (SELECT COUNT(*) FROM annonces a WHERE a.prestataire_id=u.id) AS nb_annonces FROM users u ORDER BY u.created_at DESC LIMIT 200")->fetchAll();
page_header('Utilisateurs', 'dash');
?>
<div class="wrap">
  <div class="dash-head"><h1>Utilisateurs</h1><a class="lien" href="<?= url('admin/') ?>">Retour au tableau de bord</a></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Nom</th><th>Contact</th><th>Rôle</th><th>Annonces</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $x): ?>
      <tr>
        <td><?= e($x['nom']) ?></td>
        <td><?= e($x['email']) ?><br><span class="muted"><?= e($x['telephone']) ?></span></td>
        <td><?= e(ucfirst($x['role'])) ?></td>
        <td><?= $x['role'] === 'prestataire' ? (int)$x['nb_annonces'] : '–' ?></td>
        <td><?= badge($x['statut'] === 'actif' ? 'confirmee' : 'refusee', ['confirmee' => 'Actif', 'refusee' => 'Suspendu']) ?></td>
        <td class="actions"><?php if ($x['role'] !== 'admin'): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>"><button class="btn btn-sm btn-ghost" type="submit"><?= $x['statut'] === 'actif' ? 'Suspendre' : 'Réactiver' ?></button></form>
        <?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php page_footer();
