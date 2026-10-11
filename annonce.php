<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT a.*, u.nom AS presta_nom, u.telephone AS presta_tel FROM annonces a JOIN users u ON u.id=a.prestataire_id WHERE a.id=?');
$st->execute([$id]);
$a = $st->fetch();
$u = user();

// Une annonce non publiée n'est visible que par son auteur et l'admin
$peut_voir = $a && ($a['statut'] === 'publiee' || ($u && ($u['role'] === 'admin' || $u['id'] == $a['prestataire_id'])));
if (!$peut_voir) { http_response_code(404); page_header('Annonce introuvable'); echo '<div class="wrap bloc"><p class="vide">Cette annonce n\'existe pas ou n\'est plus disponible.</p></div>'; page_footer(); exit; }

$imgs = db()->prepare('SELECT chemin FROM annonce_images WHERE annonce_id=? ORDER BY principale DESC, id');
$imgs->execute([$id]); $imgs = $imgs->fetchAll(PDO::FETCH_COLUMN);

// Types de demande proposés selon l'annonce
$types = $a['transaction'] === 'vente' ? ['achat', 'visite'] : ['reservation', 'visite'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = require_role('client');
    $type = $_POST['type'] ?? '';
    $msg = trim($_POST['message'] ?? '');
    $d1 = $_POST['date_debut'] ?? ''; $d2 = $_POST['date_fin'] ?? '';
    $err = null;
    if ($a['statut'] !== 'publiee') $err = "Cette annonce n'accepte plus de demandes.";
    elseif (!in_array($type, $types, true)) $err = 'Type de demande invalide.';
    elseif ($type === 'reservation' || $type === 'visite') {
        $ok1 = DateTime::createFromFormat('Y-m-d', $d1); $ok2 = $d2 === '' ? true : DateTime::createFromFormat('Y-m-d', $d2);
        if (!$ok1 || !$ok2) $err = 'Indiquez une date valide.';
        elseif ($d1 < date('Y-m-d')) $err = 'La date ne peut pas être dans le passé.';
        elseif ($d2 !== '' && $d2 < $d1) $err = "La date de départ doit suivre la date d'arrivée.";
    }
    if ($err) { flash($err, 'err'); }
    else {
        db()->prepare('INSERT INTO demandes (annonce_id,client_id,type,date_debut,date_fin,message) VALUES (?,?,?,?,?,?)')
            ->execute([$id, $u['id'], $type, $d1 ?: null, $d2 ?: null, mb_substr($msg, 0, 500) ?: null]);
        flash('Demande envoyée. Le prestataire vous répondra depuis votre espace.');
        redirect('client/');
    }
    redirect('annonce.php?id=' . $id);
}

page_header($a['titre']);
?>
<div class="wrap detail">
  <?php if ($a['statut'] !== 'publiee'): ?><div class="flash flash-err">Cette annonce n'est pas publique (statut : <?= e(STATUTS_ANNONCE[$a['statut']]) ?>).</div><?php endif; ?>
  <div class="galerie">
    <?php if ($imgs): foreach (array_slice($imgs, 0, 5) as $i => $c): ?>
      <img class="<?= $i === 0 ? 'g-main' : '' ?>" src="<?= e(photo_url($c)) ?>" alt="Photo <?= $i + 1 ?> de <?= e($a['titre']) ?>">
    <?php endforeach; else: ?><div class="ph ph-lg" aria-hidden="true"></div><?php endif; ?>
  </div>

  <div class="detail-cols">
    <article>
      <p class="muted"><?= e(CATEGORIES[$a['categorie']]) ?> · <?= $a['transaction'] === 'vente' ? 'À vendre' : 'À louer' ?></p>
      <h1><?= e($a['titre']) ?></h1>
      <p class="muted"><?= e($a['quartier']) ?>, <?= e($a['ville']) ?></p>
      <ul class="faits">
        <?php if ($a['surface']): ?><li><strong><?= (int)$a['surface'] ?> m²</strong> surface</li><?php endif; ?>
        <?php if ($a['chambres']): ?><li><strong><?= (int)$a['chambres'] ?></strong> chambre<?= $a['chambres'] > 1 ? 's' : '' ?></li><?php endif; ?>
        <?php if ($a['salles_bain']): ?><li><strong><?= (int)$a['salles_bain'] ?></strong> salle<?= $a['salles_bain'] > 1 ? 's' : '' ?> de bain</li><?php endif; ?>
      </ul>
      <h2>Description</h2>
      <p class="texte"><?= nl2br(e($a['description'])) ?></p>
      <p class="muted">Proposé par <?= e($a['presta_nom']) ?></p>
    </article>

    <aside class="reserver">
      <p class="carte-prix"><?= e(prix_fmt($a)) ?></p>
      <?php if (!$u): ?>
        <p>Connectez-vous pour contacter le prestataire.</p>
        <a class="btn" href="<?= url('login.php?next=' . urlencode($_SERVER['REQUEST_URI'])) ?>">Se connecter</a>
        <a class="lien" href="<?= url('register.php') ?>">Créer un compte client</a>
      <?php elseif ($u['role'] !== 'client'): ?>
        <p class="muted">Les demandes sont réservées aux comptes clients.</p>
      <?php elseif ($a['statut'] === 'publiee'): ?>
        <form method="post">
          <?= csrf_field() ?>
          <label for="type">Votre demande</label>
          <select id="type" name="type">
            <?php foreach ($types as $t): ?><option value="<?= e($t) ?>"><?= e(TYPES_DEMANDE[$t]) ?></option><?php endforeach; ?>
          </select>
          <div id="dates">
            <label for="date_debut">Date d'arrivée / de visite</label>
            <input id="date_debut" type="date" name="date_debut" min="<?= date('Y-m-d') ?>">
            <label for="date_fin" id="l-fin">Date de départ (facultatif)</label>
            <input id="date_fin" type="date" name="date_fin" min="<?= date('Y-m-d') ?>">
          </div>
          <label for="message">Message au prestataire</label>
          <textarea id="message" name="message" rows="3" maxlength="500" placeholder="Présentez-vous, précisez votre besoin ou votre offre."></textarea>
          <button class="btn btn-lg" type="submit">Envoyer la demande</button>
        </form>
        <script>
          const t = document.getElementById('type'), d = document.getElementById('dates'), fin = [document.getElementById('date_fin'), document.getElementById('l-fin')];
          function maj() { d.hidden = t.value === 'achat'; fin.forEach(el => el.hidden = t.value !== 'reservation'); }
          t.addEventListener('change', maj); maj();
        </script>
      <?php endif; ?>
    </aside>
  </div>
</div>
<?php page_footer();
