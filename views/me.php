<div class="card">
  <h2>Mon score</h2>

  <p class="row">
    <span class="badge">Total : <?= (int)$totalPoints ?> pts</span>
    <span class="badge">Parties : <?= (int)$games ?></span>
    <span class="badge">Meilleur : <?= (int)$bestScore ?> pts</span>
    <a class="badge" href="/leaderboard">Voir le classement</a>
  </p>

  <h3>Dernières parties</h3>
  <?php if (empty($recent)): ?>
    <p><small class="muted">Aucune partie pour l’instant.</small></p>
  <?php else: ?>
    <div style="display:grid;gap:8px">
      <?php foreach ($recent as $r): ?>
        <div style="border:1px solid #1f2a3a;border-radius:12px;padding:10px">
          <div><strong><?= htmlspecialchars($r['artist']) ?></strong> — <?= htmlspecialchars($r['title']) ?></div>
          <div><small class="muted"><?= htmlspecialchars($r['created_at']) ?> | points: <?= (int)$r['points'] ?></small></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
