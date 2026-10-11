<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0); $action = $_POST['action'] ?? '';
    if ($action === 'publier') {
        db()->prepare("UPDATE annonces SET statut='publiee', motif_refus=NULL WHERE id=?")->execute([$id]);
        flash('Annonce publiée.');
    } elseif ($action === 'refuser') {
        $motif = trim($_POST['motif'] ?? '');
        if ($motif === '') flash('Indiquez un motif de refus pour que le prestataire puisse corriger.', 'err');
        else { db()->prepare("UPDATE annonces SET statut='refusee', motif_refus=? WHERE id=?")->execute([mb_substr($motif, 0, 255), $id]); flash('Annonce refusée.'); }
    } elseif ($action === 'archiver') {
        db()->prepare("UPDATE annonces SET statut='archivee' WHERE id=?")->execute([$id]);
        flash('Annonce archivée.');
    }
    redirect('admin/annonces.php?statut=' . urlencode($_POST['retour'] ?? 'en_attente'));
}
$statut = array_key_exists($_GET['statut'] ?? '', STATUTS_ANNONCE) ? $_GET['statut'] : 'en_attente';
$st = db()->prepare("SELECT a.*, u.nom AS presta_nom, u.telephone AS presta_tel,
   (SELECT chemin FROM annonce_images i WHERE i.annonce_id=a.id ORDER BY principale DESC, id LIMIT 1) AS photo
   FROM annonces a JOIN users u ON u.id=a.prestataire_id WHERE a.statut=? ORDER BY a.updated_at DESC LIMIT 100");
$st->execute([$statut]); $liste = $st->fetchAll();

page_header('Gestion des annonces', 'dash');
?>
<div class="wrap">
  <div class="dash-head"><h1>Annonces</h1><a class="lien" href="<?= url('admin/') ?>">Retour au tableau de bord</a></div>
  <nav class="onglets" aria-label="Filtrer par statut">
    <?php foreach (STATUTS_ANNONCE as $k => $l): ?><a href="?statut=<?= $k ?>" <?= $k === $statut ? 'aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach; ?>
  </nav>
  <?php if (!$liste): ?><p class="vide">Aucune annonce dans cette catégorie.</p><?php endif; ?>
  <?php foreach ($liste as $a): ?>
    <article class="modo">
      <div class="modo-img"><?php if ($a['photo']): ?><img src="<?= e(photo_url($a['photo'])) ?>" alt=""><?php else: ?><div class="ph" aria-hidden="true"></div><?php endif; ?></div>
      <div class="modo-corps">
        <h3><a href="<?= url('annonce.php?id=' . $a['id']) ?>"><?= e($a['titre']) ?></a></h3>
        <p class="muted"><?= e(CATEGORIES[$a['categorie']]) ?> · <?= $a['transaction'] === 'vente' ? 'Vente' : 'Location' ?> · <?= e(prix_fmt($a)) ?> · <?= e($a['quartier']) ?>, <?= e($a['ville']) ?></p>
        <p class="muted">Par <?= e($a['presta_nom']) ?> (<?= e($a['presta_tel']) ?>)</p>
        <p class="texte"><?= e(mb_strimwidth($a['description'], 0, 220, '…')) ?></p>
        <?php if ($a['motif_refus']): ?><p class="muted">Motif du refus : <?= e($a['motif_refus']) ?></p><?php endif; ?>
        <form method="post" class="modo-actions">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="retour" value="<?= e($statut) ?>">
          <?php if ($a['statut'] !== 'publiee'): ?><button class="btn btn-sm" name="action" value="publier">Publier</button><?php endif; ?>
          <?php if (in_array($a['statut'], ['en_attente', 'publiee'], true)): ?>
            <input name="motif" placeholder="Motif (obligatoire pour refuser)" aria-label="Motif du refus">
            <button class="btn btn-sm btn-ghost" name="action" value="refuser">Refuser</button>
          <?php endif; ?>
          <?php if ($a['statut'] !== 'archivee'): ?><button class="btn btn-sm btn-ghost" name="action" value="archiver">Archiver</button><?php endif; ?>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php page_footer();
