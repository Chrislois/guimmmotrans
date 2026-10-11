<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role('prestataire');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($action === 'archiver') {
        db()->prepare("UPDATE annonces SET statut='archivee' WHERE id=? AND prestataire_id=?")->execute([$id, $u['id']]);
        flash('Annonce archivée.');
    } elseif (in_array($action, ['confirmer', 'refuser'], true)) {
        $s = db()->prepare("UPDATE demandes d JOIN annonces a ON a.id=d.annonce_id SET d.statut=? WHERE d.id=? AND a.prestataire_id=? AND d.statut='en_attente'");
        $s->execute([$action === 'confirmer' ? 'confirmee' : 'refusee', $id, $u['id']]);
        flash($action === 'confirmer' ? 'Demande confirmée.' : 'Demande refusée.');
    }
    redirect('prestataire/');
}

$st = db()->prepare("SELECT a.*, (SELECT COUNT(*) FROM demandes d WHERE d.annonce_id=a.id AND d.statut='en_attente') AS nb_attente
    FROM annonces a WHERE a.prestataire_id=? ORDER BY a.created_at DESC");
$st->execute([$u['id']]); $annonces = $st->fetchAll();

$st = db()->prepare("SELECT d.*, a.titre, c.nom AS client_nom, c.telephone AS client_tel, c.email AS client_email
    FROM demandes d JOIN annonces a ON a.id=d.annonce_id JOIN users c ON c.id=d.client_id
    WHERE a.prestataire_id=? ORDER BY (d.statut='en_attente') DESC, d.created_at DESC LIMIT 50");
$st->execute([$u['id']]); $demandes = $st->fetchAll();

page_header('Espace prestataire', 'dash');
?>
<div class="wrap">
  <div class="dash-head">
    <h1>Mes annonces</h1>
    <a class="btn" href="<?= url('prestataire/annonce-form.php') ?>">Nouvelle annonce</a>
  </div>

  <?php if ($annonces): ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Annonce</th><th>Prix</th><th>Statut</th><th>Demandes en attente</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($annonces as $a): ?>
      <tr>
        <td><a href="<?= url('annonce.php?id=' . $a['id']) ?>"><?= e($a['titre']) ?></a><br><span class="muted"><?= e($a['quartier']) ?>, <?= e($a['ville']) ?></span></td>
        <td><?= e(prix_fmt($a)) ?></td>
        <td><?= badge($a['statut'], STATUTS_ANNONCE) ?><?php if ($a['statut'] === 'refusee' && $a['motif_refus']): ?><br><span class="muted">Motif : <?= e($a['motif_refus']) ?></span><?php endif; ?></td>
        <td><?= (int)$a['nb_attente'] ?></td>
        <td class="actions">
          <?php if ($a['statut'] !== 'archivee'): ?>
            <a class="btn btn-sm btn-ghost" href="<?= url('prestataire/annonce-form.php?id=' . $a['id']) ?>">Modifier</a>
            <form method="post" onsubmit="return confirm('Archiver cette annonce ?')"><?= csrf_field() ?><input type="hidden" name="action" value="archiver"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-ghost" type="submit">Archiver</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php else: ?>
    <p class="vide">Vous n'avez pas encore d'annonce. Déposez la première : elle sera visible après validation par l'équipe.</p>
  <?php endif; ?>

  <h2>Demandes reçues</h2>
  <?php if ($demandes): ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Client</th><th>Annonce</th><th>Demande</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($demandes as $d): ?>
      <tr>
        <td><?= e($d['client_nom']) ?><br><span class="muted"><?= e($d['client_tel']) ?> · <?= e($d['client_email']) ?></span></td>
        <td><?= e($d['titre']) ?></td>
        <td><?= e(TYPES_DEMANDE[$d['type']]) ?>
          <?php if ($d['date_debut']): ?><br><span class="muted">du <?= e(date('d/m/Y', strtotime($d['date_debut']))) ?><?= $d['date_fin'] ? ' au ' . e(date('d/m/Y', strtotime($d['date_fin']))) : '' ?></span><?php endif; ?>
          <?php if ($d['message']): ?><br><span class="muted">« <?= e($d['message']) ?> »</span><?php endif; ?></td>
        <td><?= badge($d['statut'], STATUTS_DEMANDE) ?></td>
        <td class="actions">
          <?php if ($d['statut'] === 'en_attente'): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $d['id'] ?>"><button class="btn btn-sm" name="action" value="confirmer">Confirmer</button> <button class="btn btn-sm btn-ghost" name="action" value="refuser">Refuser</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php else: ?><p class="vide">Aucune demande pour le moment.</p><?php endif; ?>
</div>
<?php page_footer();
