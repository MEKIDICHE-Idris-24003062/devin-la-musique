<div class="card">
  <h2>Résultat</h2>

  <p>Réponse : <strong><?= htmlspecialchars($track['artist']) ?></strong> — <strong><?= htmlspecialchars($track['title']) ?></strong></p>

  <ul>
    <li>Artiste : <?= $artistOk ? '✅' : '❌' ?></li>
    <li>Titre : <?= $titleOk ? '✅' : '❌' ?></li>
  </ul>

  <p>Points (avant bonus moitié) : <span class="badge"><?= (int)$pointsBefore ?></span></p>
  <p>Score final : <span class="badge"><?= (int)$final ?></span></p>

  <?php if (isset($totalScore)): ?>
    <p>Score total : <span class="badge"><?= (int)$totalScore ?> pts</span></p>
  <?php endif; ?>

  <p class="row">
    <a class="badge" href="/play">Rejouer</a>
  </p>
</div>
