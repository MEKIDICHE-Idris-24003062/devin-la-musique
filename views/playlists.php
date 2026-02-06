<div class="card">
  <h2>Choisir une playlist</h2>

  <p><small class="muted">Tu peux choisir ta playlist (rap FR, etc.). Le jeu piochera dedans.</small></p>

  <div style="margin:10px 0">
    <strong>Playlist active :</strong>
    <?php if (!empty($activePlaylistId)): ?>
      <span class="badge">#<?= (int)$activePlaylistId ?></span>
      <?php $t = App\Playlists::activeTitle((int)$activePlaylistId); if ($t): ?><span class="badge"><?= htmlspecialchars($t) ?></span><?php endif; ?>
      <form method="post" action="/playlists/select" style="display:inline">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
        <input type="hidden" name="playlist_id" value="0" />
        <button type="submit">Désactiver</button>
      </form>
    <?php else: ?>
      <span class="badge">Aucune (toutes musiques)</span>
    <?php endif; ?>
  </div>

  <form method="post" action="/playlists/search" class="row">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
    <input name="q" placeholder="Rechercher une playlist Deezer (ex: rap francais, rap fr, french rap)" style="flex:1" value="<?= htmlspecialchars($playlistQuery ?? '') ?>" />
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
          <form method="post" action="/playlists/select">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
            <input type="hidden" name="playlist_id" value="<?= (int)$p['id'] ?>" />
            <button class="primary" type="submit">Utiliser cette playlist</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
