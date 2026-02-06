<div class="card">
  <h2>Salon</h2>

  <p class="row">
    <button id="copyCode" class="badge" type="button" data-code="<?= htmlspecialchars($room['code']) ?>">Copier le code: <?= htmlspecialchars($room['code']) ?></button>
    <span class="badge">Statut: <?= htmlspecialchars($room['status']) ?></span>
    <a class="badge" href="/room/leave">Quitter le multi</a>
    <span class="badge">En ligne: <?= count($players) ?></span>
  </p>

  <p>
    Playlist du salon :
    <?php if (!empty($room['playlist_id'])): ?>
      <span class="badge">#<?= (int)$room['playlist_id'] ?></span>
      <?php if (!empty($playlistTitle)): ?><span class="badge"><?= htmlspecialchars($playlistTitle) ?></span><?php endif; ?>
    <?php else: ?>
      <span class="badge">(toutes musiques)</span>
    <?php endif; ?>
  </p>

  <h3>Joueurs</h3>
  <ul>
    <?php foreach ($players as $p): ?>
      <li class="row" style="justify-content:space-between;align-items:center">
        <span>
          <?= htmlspecialchars($p['username']) ?><?= ((int)$p['user_id'] === (int)$room['host_user_id']) ? ' (chef)' : '' ?>
        </span>
        <?php if ((int)$room['host_user_id'] === (int)App\Auth::user()['id'] && (int)$p['user_id'] !== (int)$room['host_user_id']): ?>
          <form method="post" action="/room/kick" style="margin:0">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
            <input type="hidden" name="user_id" value="<?= (int)$p['user_id'] ?>" />
            <button type="submit">Supprimer</button>
          </form>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if ((int)$room['host_user_id'] === (int)App\Auth::user()['id']): ?>
    <p><small class="muted">Chef : va dans <a href="/playlists">Playlists</a>, choisis ta playlist, puis clique “Appliquer au salon”.</small></p>

    <form method="post" action="/room/use-my-playlist" class="row">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
      <button type="submit">Appliquer au salon (ma playlist)</button>
    </form>

    <form method="post" action="/room/start" class="row">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
      <button class="primary" type="submit">Démarrer (5 manches)</button>
    </form>
  <?php else: ?>
    <p><small class="muted">Attends que le chef démarre.</small></p>
    <p><a class="badge" href="/room/play">Jouer / rafraîchir</a></p>
  <?php endif; ?>
  <script>
    // Copy room code
    (function(){
      const btn = document.getElementById('copyCode');
      if (btn) {
        btn.addEventListener('click', async () => {
          const code = btn.dataset.code || '';
          try {
            await navigator.clipboard.writeText(code);
            const old = btn.textContent;
            btn.textContent = 'Copié ! (' + code + ')';
            setTimeout(() => { btn.textContent = old; }, 1200);
          } catch (e) {
            // Fallback prompt
            window.prompt('Copie ce code :', code);
          }
        });
      }
    })();

    // Keep presence updated; if the browser is closed, pings stop and the player disappears.
    setInterval(() => {
      fetch('/room/ping', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ csrf: '<?= htmlspecialchars(csrf_token()) ?>' })
      })
      .then(r => r.json().catch(() => null))
      .then(j => {
        if (j && j.kicked) {
          alert('Tu as été supprimé du salon.');
          window.location.href = '/rooms';
        }
      })
      .catch(() => {});
    }, 20000);
  </script>
</div>
