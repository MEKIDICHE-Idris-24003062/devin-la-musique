<div class="card">
  <h2>Admin</h2>

  <h3>1) Import rapide (Chart FR)</h3>
  <form method="post" action="/admin/import-chart" class="row">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
    <input type="hidden" name="country_id" value="16" />
    <input name="limit" type="number" min="1" max="300" value="200" style="width:120px" />
    <button class="primary" type="submit">Importer le Chart FR (Deezer)</button>
  </form>
  <p><small class="muted">Le chart FR n’est pas 100% musique FR. Pour du FR-only, utilise l’import playlist.</small></p>

  <h3>2) FR-only (chercher une playlist Deezer)</h3>
  <form method="post" action="/admin/playlist-search" class="row">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
    <input name="q" placeholder="Rechercher une playlist (ex: rap francais, rap fr, french rap)" style="flex:1" value="<?= htmlspecialchars($playlistQuery ?? '') ?>" />
    <button type="submit">Chercher</button>
  </form>

  <?php if (!empty($playlistResults)): ?>
    <div style="display:grid;gap:8px;margin-top:10px">
      <?php foreach ($playlistResults as $p): ?>
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;border:1px solid #1f2a3a;border-radius:12px;padding:10px">
          <div>
            <div><strong><?= htmlspecialchars($p['title']) ?></strong></div>
            <div><small class="muted">id: <?= (int)$p['id'] ?><?= $p['creator'] ? ' | by ' . htmlspecialchars($p['creator']) : '' ?><?= $p['nb_tracks'] ? ' | ' . (int)$p['nb_tracks'] . ' tracks' : '' ?><?= $p['link'] ? ' | ' . htmlspecialchars($p['link']) : '' ?></small></div>
          </div>
          <form method="post" action="/admin/import-playlist">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
            <input type="hidden" name="playlist_id" value="<?= (int)$p['id'] ?>" />
            <button class="primary" type="submit">Importer</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h3>Dernières musiques</h3>
  <?php if (empty($tracks)): ?>
    <p>Aucune musique.</p>
  <?php else: ?>
    <div style="display:grid;gap:8px">
      <?php foreach ($tracks as $t): ?>
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;border:1px solid #1f2a3a;border-radius:12px;padding:10px">
          <div>
            <div><strong><?= htmlspecialchars($t['artist']) ?></strong> — <?= htmlspecialchars($t['title']) ?></div>
            <div><small class="muted">deezer_id: <?= (int)$t['deezer_track_id'] ?> | enabled: <?= (int)$t['enabled'] ?></small></div>
          </div>
          <form method="post" action="/admin/track-toggle">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
            <button type="submit"><?= (int)$t['enabled'] ? 'Désactiver' : 'Activer' ?></button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
