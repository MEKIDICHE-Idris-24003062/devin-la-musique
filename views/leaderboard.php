<div class="card">
  <h2>Classement mondial</h2>
  <p><small class="muted">Top 50 basé sur le total de points.</small></p>

  <?php if (empty($rows)): ?>
    <p>Aucun joueur.</p>
  <?php else: ?>
    <div style="display:grid;gap:8px">
      <?php $rank = 0; foreach ($rows as $r): $rank++; ?>
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;border:1px solid #1f2a3a;border-radius:12px;padding:10px">
          <div>
            <div><strong>#<?= $rank ?></strong> <?= htmlspecialchars($r['username']) ?></div>
            <div><small class="muted">parties: <?= (int)$r['games'] ?> | best: <?= (int)$r['best'] ?></small></div>
          </div>
          <div class="badge"><?= (int)$r['total_points'] ?> pts</div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
