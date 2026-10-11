<?php
require_once __DIR__ . '/includes/functions.php';

$f = [
    'transaction' => in_array($_GET['transaction'] ?? '', ['vente', 'location'], true) ? $_GET['transaction'] : '',
    'categorie'   => array_key_exists($_GET['categorie'] ?? '', CATEGORIES) ? $_GET['categorie'] : '',
    'ville'       => in_array($_GET['ville'] ?? '', VILLES, true) ? $_GET['ville'] : '',
    'prix_max'    => max(0, (int)($_GET['prix_max'] ?? 0)),
    'q'           => trim($_GET['q'] ?? ''),
];
$where = ["a.statut='publiee'"]; $p = [];
if ($f['transaction']) { $where[] = 'a.transaction=?'; $p[] = $f['transaction']; }
if ($f['categorie'])   { $where[] = 'a.categorie=?';   $p[] = $f['categorie']; }
if ($f['ville'])       { $where[] = 'a.ville=?';       $p[] = $f['ville']; }
if ($f['prix_max'])    { $where[] = 'a.prix<=?';       $p[] = $f['prix_max']; }
if ($f['q'] !== '')    { $where[] = '(a.titre LIKE ? OR a.quartier LIKE ?)'; $p[] = $p[] = '%' . $f['q'] . '%'; }
$w = implode(' AND ', $where);

$par = 12; $page = max(1, (int)($_GET['page'] ?? 1));
$st = db()->prepare("SELECT COUNT(*) FROM annonces a WHERE $w"); $st->execute($p);
$total = (int)$st->fetchColumn(); $pages = max(1, (int)ceil($total / $par)); $page = min($page, $pages);

$st = db()->prepare("SELECT a.*, (SELECT chemin FROM annonce_images i WHERE i.annonce_id=a.id ORDER BY principale DESC, id LIMIT 1) AS photo
    FROM annonces a WHERE $w ORDER BY a.created_at DESC LIMIT $par OFFSET " . (($page - 1) * $par));
$st->execute($p);
$annonces = $st->fetchAll();

$qs = fn(array $extra = []) => http_build_query(array_filter(array_merge($f, $extra), fn($v) => $v !== '' && $v !== 0));

page_header('Annonces immobilières');
?>
<div class="wrap liste-page">
  <aside class="filtres">
    <form method="get" action="">
      <h2>Affiner</h2>
      <label for="q">Quartier ou mot-clé</label>
      <input id="q" name="q" value="<?= e($f['q']) ?>" placeholder="Ex. Kipé, Nongo…">
      <label for="transaction">Transaction</label>
      <select id="transaction" name="transaction">
        <option value="">Vente et location</option>
        <option value="location" <?= $f['transaction'] === 'location' ? 'selected' : '' ?>>Location</option>
        <option value="vente" <?= $f['transaction'] === 'vente' ? 'selected' : '' ?>>Vente</option>
      </select>
      <label for="categorie">Type de bien</label>
      <select id="categorie" name="categorie">
        <option value="">Tous</option>
        <?php foreach (CATEGORIES as $k => $v): ?><option value="<?= e($k) ?>" <?= $f['categorie'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
      </select>
      <label for="ville">Ville</label>
      <select id="ville" name="ville">
        <option value="">Toute la Guinée</option>
        <?php foreach (VILLES as $v): ?><option <?= $f['ville'] === $v ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
      </select>
      <label for="prix_max">Prix maximum (GNF)</label>
      <input id="prix_max" name="prix_max" type="number" min="0" step="100000" value="<?= $f['prix_max'] ?: '' ?>">
      <button class="btn" type="submit">Appliquer</button>
      <a class="lien" href="<?= url('annonces.php') ?>">Réinitialiser</a>
    </form>
  </aside>

  <section>
    <h1><?= $total ?> annonce<?= $total > 1 ? 's' : '' ?></h1>
    <?php if ($annonces): ?>
      <div class="grille grille-2"><?php foreach ($annonces as $a) echo carte_annonce($a); ?></div>
      <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?<?= e($qs(['page' => $i])) ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <p class="vide">Aucune annonce ne correspond à ces critères. Essayez d'élargir la ville ou le budget.</p>
    <?php endif; ?>
  </section>
</div>
<?php page_footer();
