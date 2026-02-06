<div class="card">
  <h2>Créer un compte</h2>
  <?php if (!empty($error)): ?><p style="color:#ffb4b4"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form method="post" action="/register">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
    <div class="row">
      <input name="username" placeholder="Pseudo" required />
      <input name="password" type="password" placeholder="Mot de passe (6+ chars)" required />
      <button class="primary" type="submit">Créer</button>
    </div>
  </form>
</div>
