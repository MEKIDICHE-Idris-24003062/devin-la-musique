let state = { clipSeconds: 0, lastStop: 0, playing: false };

function post(path, data) {
  return fetch(path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(data)
  });
}

function stopPlayback() {
  const audio = document.getElementById('preview');
  if (!audio) return;
  window.clearTimeout(audio.__dlmTimeout);
  try { audio.pause(); } catch (_) {}
  state.playing = false;
}

function playSegment(startSeconds, endSeconds) {
  const audio = document.getElementById('preview');
  if (!audio) return;

  const duration = Math.max(0, endSeconds - startSeconds);
  const clipMs = duration * 1000;

  const doPlay = () => {
    try {
      audio.pause();
      audio.currentTime = startSeconds;
    } catch (_) {}

    state.playing = true;
    audio.play();

    window.clearTimeout(audio.__dlmTimeout);
    audio.__dlmTimeout = window.setTimeout(() => {
      try { audio.pause(); } catch (_) {}
      state.lastStop = endSeconds;
      state.playing = false;
    }, clipMs);
  };

  // If metadata not loaded yet, wait so currentTime assignment works
  if (isNaN(audio.duration) || audio.duration === 0) {
    audio.addEventListener('loadedmetadata', doPlay, { once: true });
    audio.load();
  } else {
    doPlay();
  }
}

function playClipFromStart() {
  state.lastStop = 0;
  playSegment(0, state.clipSeconds);
}

document.addEventListener('click', async (e) => {
  const revealBtn = e.target.closest('[data-action="reveal"]');
  if (revealBtn) {
    e.preventDefault();
    const csrf = revealBtn.dataset.csrf;

    const form = document.querySelector('form[action]');
    const action = form ? form.getAttribute('action') : '';
    const revealPath = (action && action.startsWith('/room/')) ? '/room/reveal' : '/reveal';
    const res = await post(revealPath, { csrf });
    const json = await res.json();

    if (json.ok) {
      const prev = typeof json.prevClipSeconds === 'number' ? json.prevClipSeconds : state.clipSeconds;
      state.clipSeconds = json.clipSeconds;
      document.getElementById('clipSeconds').textContent = String(state.clipSeconds);
      document.getElementById('pointsNow').textContent = String(json.pointsNow);

      // Continue from where the last excerpt stopped (ex: 5→10 plays starting at 5s)
      playSegment(prev, state.clipSeconds);
    } else {
      alert(json.error || 'Erreur');
    }
  }

  const playBtn = e.target.closest('[data-action="playclip"]');
  if (playBtn) {
    e.preventDefault();
    playClipFromStart();
  }

  const pauseBtn = e.target.closest('[data-action="pauseclip"]');
  if (pauseBtn) {
    e.preventDefault();
    stopPlayback();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const audio = document.getElementById('preview');
  if (!audio) return;
  state.clipSeconds = parseInt(audio.dataset.clipSeconds, 10);

  audio.addEventListener('error', () => {
    // Provide a more actionable hint
    const src = (audio.querySelector('source') || {}).src;
    alert('Le preview audio ne se charge pas.\n\nEssaye de cliquer sur “Ouvrir l’audio (preview)”.\nSi ça ne marche pas non plus, le lien est bloqué (403/404) ou invalide.\n\nURL: ' + (src || '(inconnue)'));
  });
});
