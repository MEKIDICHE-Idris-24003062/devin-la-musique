<div class="card">
  <h1>Devin la musique (FR)</h1>
  <p>Le but : deviner <strong>artiste</strong> + <strong>titre</strong> à partir d’un extrait audio (preview Deezer 30s).</p>
  <ul>
    <li>Tu commences avec un extrait très court.</li>
    <li>Tu peux cliquer <em>Découvrir plus</em> : ça révèle plus de secondes, mais ça enlève des points.</li>
    <li>Trouver seulement l’artiste <em>ou</em> seulement le titre = la moitié des points.</li>
  </ul>

  <?php if (App\Auth::check()): ?>
    <p><a class="badge" href="/play">Jouer maintenant →</a></p>
  <?php else: ?>
    <p><a class="badge" href="/register">Créer un compte</a> <a class="badge" href="/login">Connexion</a></p>
  <?php endif; ?>
</div>
