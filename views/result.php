<div class="card">
  <h2>Résultat</h2>

  <p>Réponse : <strong><?= htmlspecialchars($track['artist']) ?></strong> — <strong><?= htmlspecialchars($track['title']) ?></strong></p>

  <ul>
    <li>Artiste : <?= $artistOk ? '✅' : '❌' ?></li>
    <li>Titre : <?= $titleOk ? '✅' : '❌' ?></li>
  </ul>

  <p>Points (avant bonus moitié) : <span class="badge"><?= (int)$pointsBefore ?></span></p>
  <p>Score final : <span class="badge"><?= (int)$final ?></span></p>

  <?php if (isset($nextRound)): ?>
    <p class="row">
      <span class="badge">Total provisoire : <?= (int)$totalSoFar ?> pts</span>
      <span class="badge">Manche suivante : <?= (int)$nextRound ?>/<?= (int)$roundsTotal ?></span>
    </p>
    <p class="row">
      <a class="badge" href="/play">Continuer</a>
    </p>
  <?php else: ?>
    <p class="row">
      <a class="badge" href="/play">Rejouer</a>
    </p>
  <?php endif; ?>
</div>
