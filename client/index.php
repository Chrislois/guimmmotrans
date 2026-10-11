<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role('client');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db()->prepare("UPDATE demandes SET statut='annulee' WHERE id=? AND client_id=? AND statut='en_attente'")
        ->execute([(int)($_POST['id'] ?? 0), $u['id']]);
    flash('Demande annulée.');
    redirect('client/');
}
$st = db()->prepare("SELECT d.*, a.titre, a.ville, a.quartier, a.prix, a.periode, p.nom AS presta_nom, p.telephone AS presta_tel
    FROM demandes d JOIN annonces a ON a.id=d.annonce_id JOIN users p ON p.id=a.prestataire_id
    WHERE d.client_id=? ORDER BY d.created_at DESC");
$st->execute([$u['id']]); $demandes = $st->fetchAll();

page_header('Mes demandes', 'dash');
?>
<div class="wrap">
  <div class="dash-head"><h1>Mes demandes</h1><a class="btn btn-ghost" href="<?= url('annonces.php') ?>">Chercher un bien</a></div>
  <?php if ($demandes): ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Annonce</th><th>Demande</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($demandes as $d): ?>
      <tr>
        <td><a href="<?= url('annonce.php?id=' . $d['annonce_id']) ?>"><?= e($d['titre']) ?></a><br><span class="muted"><?= e($d['quartier']) ?>, <?= e($d['ville']) ?> · <?= e(prix_fmt($d)) ?></span></td>
        <td><?= e(TYPES_DEMANDE[$d['type']]) ?>
          <?php if ($d['date_debut']): ?><br><span class="muted">du <?= e(date('d/m/Y', strtotime($d['date_debut']))) ?><?= $d['date_fin'] ? ' au ' . e(date('d/m/Y', strtotime($d['date_fin']))) : '' ?></span><?php endif; ?></td>
        <td><?= badge($d['statut'], STATUTS_DEMANDE) ?>
          <?php if ($d['statut'] === 'confirmee'): ?><br><span class="muted">Contact : <?= e($d['presta_nom']) ?>, <?= e($d['presta_tel']) ?></span><?php endif; ?></td>
        <td class="actions"><?php if ($d['statut'] === 'en_attente'): ?>
          <form method="post" onsubmit="return confirm('Annuler cette demande ?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['id'] ?>"><button class="btn btn-sm btn-ghost" type="submit">Annuler</button></form>
        <?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php else: ?><p class="vide">Vous n'avez pas encore fait de demande. Parcourez les annonces pour réserver, visiter ou faire une offre.</p><?php endif; ?>
</div>
<?php page_footer();
