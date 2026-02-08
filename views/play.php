<div class="card">
  <h2>Partie</h2>

  <?php if (isset($score)): ?>
    <p>Score : <span class="badge"><?= (int)$score ?></span></p>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <p style="color:#ffb4b4"><?= htmlspecialchars($error) ?></p>
    <?php if (App\Auth::user()['is_admin']): ?>
      <p><a class="badge" href="/admin">Aller dans Admin →</a></p>
    <?php endif; ?>
  <?php else: ?>

    <p>
      Extrait actuel : <span class="badge"><span id="clipSeconds"><?= (int)$clipSeconds ?></span>s</span>
      | Points si tu réponds maintenant : <span class="badge"><span id="pointsNow"><?= (int)$pointsNow ?></span></span>
    </p>

    <?php $previewUrl = '/preview?id=' . (int)$track['id']; ?>
    <!-- Audio element kept for JS playback, but native controls are hidden to prevent manual play -->
    <audio id="preview" preload="none" data-clip-seconds="<?= (int)$clipSeconds ?>" style="display:none">
      <source src="<?= htmlspecialchars($previewUrl) ?>" type="audio/mpeg" />
      Ton navigateur ne supporte pas l’audio.
    </audio>
    <p class="row">
      <button class="primary" data-action="playclip">Écouter l’extrait</button>
      <button data-action="pauseclip">Pause</button>
      <button data-action="reveal" data-csrf="<?= htmlspecialchars(csrf_token()) ?>">Découvrir plus (-points)</button>
      <a class="badge" href="/play">Nouvelle musique</a>
    </p>

    <hr style="border-color:#1f2a3a" />

    <form method="post" action="<?= !empty($roomMode) ? '/room/guess' : '/guess' ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
      <div class="row">
        <input name="artist" placeholder="Artiste" autocomplete="off" />
        <input name="title" placeholder="Titre" autocomplete="off" />
        <button class="primary" type="submit">Valider</button>
      </div>
      <p><small class="muted">Tolérance aux fautes : on compare avec une similarité (Levenshtein).</small></p>
    </form>

  <?php endif; ?>
</div>
