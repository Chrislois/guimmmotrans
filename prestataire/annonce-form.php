<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role('prestataire');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$a = null;
if ($id) {
    $s = db()->prepare('SELECT * FROM annonces WHERE id=? AND prestataire_id=?'); $s->execute([$id, $u['id']]);
    $a = $s->fetch();
    if (!$a) { http_response_code(404); exit('Annonce introuvable.'); }
}
$d = $a ?: ['titre' => '', 'description' => '', 'categorie' => 'appartement', 'transaction' => 'location', 'prix' => '', 'periode' => 'mois', 'ville' => 'Conakry', 'quartier' => '', 'surface' => '', 'chambres' => '', 'salles_bain' => ''];
$images = [];
if ($id) { $s = db()->prepare('SELECT * FROM annonce_images WHERE annonce_id=? ORDER BY principale DESC, id'); $s->execute([$id]); $images = $s->fetchAll(); }
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [
        'titre' => trim($_POST['titre'] ?? ''), 'description' => trim($_POST['description'] ?? ''),
        'categorie' => $_POST['categorie'] ?? '', 'transaction' => $_POST['transaction'] ?? '',
        'prix' => (int)preg_replace('/\D/', '', $_POST['prix'] ?? ''), 'periode' => $_POST['periode'] ?? 'unique',
        'ville' => $_POST['ville'] ?? '', 'quartier' => trim($_POST['quartier'] ?? ''),
        'surface' => ($_POST['surface'] ?? '') === '' ? null : (int)$_POST['surface'],
        'chambres' => ($_POST['chambres'] ?? '') === '' ? null : (int)$_POST['chambres'],
        'salles_bain' => ($_POST['salles_bain'] ?? '') === '' ? null : (int)$_POST['salles_bain'],
    ];
    if ($d['transaction'] === 'vente') $d['periode'] = 'unique';

    if (mb_strlen($d['titre']) < 8 || mb_strlen($d['titre']) > 160) $err = 'Le titre doit faire entre 8 et 160 caractères.';
    elseif (mb_strlen($d['description']) < 30) $err = 'Décrivez le bien en au moins 30 caractères.';
    elseif (!isset(CATEGORIES[$d['categorie']]) || !in_array($d['transaction'], ['vente', 'location'], true)) $err = 'Type de bien ou de transaction invalide.';
    elseif (!in_array($d['ville'], VILLES, true) || $d['quartier'] === '') $err = 'Indiquez la ville et le quartier.';
    elseif ($d['prix'] <= 0) $err = 'Indiquez un prix en GNF.';
    elseif (!isset(PERIODES[$d['periode']]) || ($d['transaction'] === 'location' && $d['periode'] === 'unique')) $err = 'Choisissez la période du loyer.';

    // Photos
    $nouveaux = [];
    if (!$err && !empty($_FILES['photos']['name'][0])) {
        $n = count($_FILES['photos']['name']);
        if ($n + count($images) > 8) $err = '8 photos maximum par annonce.';
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        for ($i = 0; $i < $n && !$err; $i++) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) { $err = "Échec de l'envoi d'une photo."; break; }
            if ($_FILES['photos']['size'][$i] > 3 * 1024 * 1024) { $err = 'Chaque photo doit faire moins de 3 Mo.'; break; }
            $mime = $fi->file($_FILES['photos']['tmp_name'][$i]);
            if (!isset($ext[$mime])) { $err = 'Formats acceptés : JPG, PNG, WebP.'; break; }
            $nouveaux[] = [$_FILES['photos']['tmp_name'][$i], bin2hex(random_bytes(10)) . '.' . $ext[$mime]];
        }
    }

    if (!$err) {
        $pdo = db(); $pdo->beginTransaction();
        $cols = [$d['titre'], $d['description'], $d['categorie'], $d['transaction'], $d['prix'], $d['periode'], $d['ville'], $d['quartier'], $d['surface'], $d['chambres'], $d['salles_bain']];
        if ($id) {
            // Toute modification repasse l'annonce en validation
            $pdo->prepare("UPDATE annonces SET titre=?,description=?,categorie=?,transaction=?,prix=?,periode=?,ville=?,quartier=?,surface=?,chambres=?,salles_bain=?,statut='en_attente',motif_refus=NULL WHERE id=? AND prestataire_id=?")
                ->execute([...$cols, $id, $u['id']]);
        } else {
            $pdo->prepare('INSERT INTO annonces (titre,description,categorie,transaction,prix,periode,ville,quartier,surface,chambres,salles_bain,prestataire_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([...$cols, $u['id']]);
            $id = (int)$pdo->lastInsertId();
        }
        $premiere = !$images;
        foreach ($nouveaux as [$tmp, $nom]) {
            if (!move_uploaded_file($tmp, UPLOAD_DIR . $nom)) continue;
            $pdo->prepare('INSERT INTO annonce_images (annonce_id,chemin,principale) VALUES (?,?,?)')->execute([$id, $nom, $premiere ? 1 : 0]);
            $premiere = false;
        }
        $pdo->commit();
        flash('Annonce enregistrée. Elle sera visible après validation par l\'équipe.');
        redirect('prestataire/');
    }
}
page_header($a ? "Modifier l'annonce" : 'Nouvelle annonce', 'dash');
?>
<div class="wrap form-page">
  <h1><?= $a ? "Modifier l'annonce" : 'Nouvelle annonce' ?></h1>
  <?php if ($a): ?><p class="muted">Après modification, l'annonce repasse en validation avant d'être de nouveau visible.</p><?php endif; ?>
  <?php if ($err): ?><div class="flash flash-err" role="alert"><?= e($err) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="grille-form">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <div class="plein"><label for="titre">Titre</label>
      <input id="titre" name="titre" required maxlength="160" value="<?= e($d['titre']) ?>" placeholder="Ex. Appartement meublé 2 chambres à Kipé"></div>
    <div><label for="transaction">Transaction</label>
      <select id="transaction" name="transaction"><option value="location" <?= $d['transaction'] === 'location' ? 'selected' : '' ?>>Location</option><option value="vente" <?= $d['transaction'] === 'vente' ? 'selected' : '' ?>>Vente</option></select></div>
    <div><label for="categorie">Type de bien</label>
      <select id="categorie" name="categorie"><?php foreach (CATEGORIES as $k => $v): ?><option value="<?= e($k) ?>" <?= $d['categorie'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div><label for="prix">Prix (GNF)</label>
      <input id="prix" name="prix" inputmode="numeric" required value="<?= e($d['prix']) ?>" placeholder="3500000"></div>
    <div id="bloc-periode"><label for="periode">Période du loyer</label>
      <select id="periode" name="periode"><?php foreach (['nuit' => 'Par nuit', 'mois' => 'Par mois', 'an' => 'Par an'] as $k => $v): ?><option value="<?= $k ?>" <?= $d['periode'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div><label for="ville">Ville</label>
      <select id="ville" name="ville"><?php foreach (VILLES as $v): ?><option <?= $d['ville'] === $v ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div><label for="quartier">Quartier</label>
      <input id="quartier" name="quartier" required value="<?= e($d['quartier']) ?>" placeholder="Ex. Kipé, Lambanyi, Nongo"></div>
    <div><label for="surface">Surface (m²)</label><input id="surface" type="number" min="0" name="surface" value="<?= e($d['surface']) ?>"></div>
    <div><label for="chambres">Chambres</label><input id="chambres" type="number" min="0" max="50" name="chambres" value="<?= e($d['chambres']) ?>"></div>
    <div><label for="salles_bain">Salles de bain</label><input id="salles_bain" type="number" min="0" max="50" name="salles_bain" value="<?= e($d['salles_bain']) ?>"></div>
    <div class="plein"><label for="description">Description</label>
      <textarea id="description" name="description" rows="6" required placeholder="État du bien, équipements, accès, eau et électricité, conditions…"><?= e($d['description']) ?></textarea></div>
    <div class="plein"><label for="photos">Photos (8 max, JPG/PNG/WebP, 3 Mo chacune)</label>
      <input id="photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
      <?php if ($images): ?><p class="muted"><?= count($images) ?> photo(s) déjà enregistrée(s). Les nouvelles photos s'ajoutent aux existantes.</p><?php endif; ?></div>
    <div class="plein"><button class="btn btn-lg" type="submit">Enregistrer et soumettre pour validation</button>
      <a class="lien" href="<?= url('prestataire/') ?>">Annuler</a></div>
  </form>
</div>
<script>
  const tr = document.getElementById('transaction'), bp = document.getElementById('bloc-periode');
  function maj() { bp.hidden = tr.value === 'vente'; }
  tr.addEventListener('change', maj); maj();
</script>
<?php page_footer();
