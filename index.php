<?php
require_once __DIR__ . '/includes/functions.php';

$sql = "SELECT a.*, (SELECT chemin FROM annonce_images i WHERE i.annonce_id=a.id ORDER BY principale DESC, id LIMIT 1) AS photo
        FROM annonces a WHERE a.statut='publiee' ORDER BY a.created_at DESC LIMIT 8";
$recentes = db()->query($sql)->fetchAll();

page_header('Trouvez votre bien en Guinée', 'home');
?>
<section class="hero">
  <div class="wrap">
    <h1>Un toit, une parcelle, une adresse en Guinée.</h1>
    <p class="lead">Maisons, appartements meublés, terrains : des annonces vérifiées, des propriétaires joignables.</p>
    <form class="recherche" action="<?= url('annonces.php') ?>" method="get">
      <div class="champ">
        <label for="h-trans">Je cherche à</label>
        <select id="h-trans" name="transaction">
          <option value="location">Louer</option>
          <option value="vente">Acheter</option>
        </select>
      </div>
      <div class="champ">
        <label for="h-cat">Type de bien</label>
        <select id="h-cat" name="categorie">
          <option value="">Tous les types</option>
          <?php foreach (CATEGORIES as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="champ">
        <label for="h-ville">Ville</label>
        <select id="h-ville" name="ville">
          <option value="">Toute la Guinée</option>
          <?php foreach (VILLES as $v): ?><option><?= e($v) ?></option><?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-lg" type="submit">Voir les annonces</button>
    </form>
  </div>
</section>

<section class="bloc">
  <div class="wrap">
    <h2>Dernières annonces</h2>
    <?php if ($recentes): ?>
      <div class="grille"><?php foreach ($recentes as $a) echo carte_annonce($a); ?></div>
      <p class="center"><a class="btn btn-ghost" href="<?= url('annonces.php') ?>">Toutes les annonces</a></p>
    <?php else: ?>
      <p class="vide">Aucune annonce publiée pour le moment. <a href="<?= url('register.php?role=prestataire') ?>">Publiez la première.</a></p>
    <?php endif; ?>
  </div>
</section>

<section class="bloc bloc-alt">
  <div class="wrap duo">
    <div>
      <h2>Vous avez un bien à louer ou à vendre ?</h2>
      <p>Créez un compte prestataire, déposez votre annonce avec photos et prix. Notre équipe la vérifie, puis elle est visible de tous les clients.</p>
      <a class="btn" href="<?= url('register.php?role=prestataire') ?>">Devenir prestataire</a>
    </div>
    <ol class="etapes">
      <li><strong>Inscrivez-vous</strong> en deux minutes.</li>
      <li><strong>Déposez l'annonce</strong> : photos, prix, quartier.</li>
      <li><strong>Validation</strong> par l'équipe, généralement sous 24 h.</li>
      <li><strong>Recevez les demandes</strong> de réservation, de visite ou d'achat.</li>
    </ol>
  </div>
</section>
<?php page_footer();
