<div class="card">
  <h2>Classement du salon</h2>
  <p class="row"><span class="badge">Code: <?= htmlspecialchars($room['code']) ?></span></p>

  <div style="display:grid;gap:8px">
    <?php $rank=0; foreach ($rows as $r): $rank++; ?>
      <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;border:1px solid #1f2a3a;border-radius:12px;padding:10px">
        <div>
          <div><strong>#<?= $rank ?></strong> <?= htmlspecialchars($r['username']) ?></div>
          <div><small class="muted">manches jouées: <?= (int)$r['rounds'] ?></small></div>
        </div>
        <div class="badge"><?= (int)$r['total_points'] ?> pts</div>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="row" style="margin-top:12px">
    <a class="badge" href="/room">Retour salon</a>
    <a class="badge" href="/play">Solo</a>
  </p>
</div>
