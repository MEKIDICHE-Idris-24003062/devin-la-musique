<div class="card">
  <h2>Fin de partie</h2>

  <p class="row">
    <span class="badge">Score total : <?= (int)$total ?> pts</span>
    <span class="badge">Objectif : <?= (int)$threshold ?> pts</span>
    <span class="badge"><?= $won ? '✅ Gagné' : '❌ Perdu' ?></span>
  </p>

  <h3>Détail des 5 manches</h3>
  <div style="display:grid;gap:8px">
    <?php foreach ($rounds as $r): ?>
      <div style="border:1px solid #1f2a3a;border-radius:12px;padding:10px">
        <div><strong>Manche <?= (int)$r['round'] ?></strong> — <?= htmlspecialchars($r['artist']) ?> — <?= htmlspecialchars($r['title']) ?></div>
        <div><small class="muted">points: <?= (int)$r['points'] ?></small></div>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="row" style="margin-top:12px">
    <a class="badge" href="/play">Rejouer (nouvelle partie)</a>
    <a class="badge" href="/leaderboard">Classement</a>
  </p>
</div>
