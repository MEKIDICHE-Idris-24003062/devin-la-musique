<div class="card">
  <h2>Multijoueur</h2>
  <p><small class="muted">Crée un salon, partage le code, et tout le monde aura les mêmes musiques (5 manches).</small></p>

  <?php if (!empty($error)): ?><p style="color:#ffb4b4"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <h3>Créer un salon</h3>
  <form method="post" action="/rooms/create" class="row">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
    <button class="primary" type="submit">Créer</button>
    <small class="muted">La playlist du chef = sa playlist active (modifiable ensuite).</small>
  </form>

  <h3 style="margin-top:16px">Rejoindre un salon</h3>
  <form method="post" action="/rooms/join" class="row">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
    <input name="code" placeholder="Code (ex: 7H3KQ)" style="width:180px" />
    <button type="submit">Rejoindre</button>
  </form>
</div>
