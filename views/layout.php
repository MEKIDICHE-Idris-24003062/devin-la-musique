<?php
/** @var string $name */
/** @var array $data */

$page = $name;

?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Devin la musique</title>
  <link rel="stylesheet" href="/assets/style.css" />
</head>
<body>
  <header class="topbar">
    <div class="brand"><a href="/">Devin la musique</a></div>
    <nav>
      <?php if (App\Auth::check()): ?>
        <a href="/play">Solo</a>
        <a href="/rooms">Multi</a>
        <a href="/playlists">Playlists</a>
        <a href="/me">Mon score</a>
        <a href="/leaderboard">Classement</a>
        <?php if (App\Auth::user()['is_admin']): ?>
          <a href="/admin">Admin</a>
        <?php endif; ?>
        <a href="/logout">Déconnexion</a>
      <?php else: ?>
        <a href="/login">Connexion</a>
        <a href="/register">Créer un compte</a>
      <?php endif; ?>
    </nav>
  </header>

  <main class="container">
    <?php require __DIR__ . "/{$page}.php"; ?>
  </main>

  <script src="/assets/app.js"></script>
</body>
</html>
