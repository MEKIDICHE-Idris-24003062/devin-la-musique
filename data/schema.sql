CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  is_admin INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS tracks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  deezer_track_id INTEGER NOT NULL UNIQUE,
  title TEXT NOT NULL,
  artist TEXT NOT NULL,
  album TEXT,
  release_date TEXT,
  preview_url TEXT,
  link TEXT,
  playlist_id INTEGER,
  playlist_title TEXT,
  title_norm TEXT NOT NULL,
  artist_norm TEXT NOT NULL,
  enabled INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT
);

CREATE TABLE IF NOT EXISTS playlists (
  deezer_playlist_id INTEGER PRIMARY KEY,
  title TEXT
);

CREATE TABLE IF NOT EXISTS playlist_tracks (
  deezer_playlist_id INTEGER NOT NULL,
  track_id INTEGER NOT NULL,
  PRIMARY KEY (deezer_playlist_id, track_id),
  FOREIGN KEY(deezer_playlist_id) REFERENCES playlists(deezer_playlist_id) ON DELETE CASCADE,
  FOREIGN KEY(track_id) REFERENCES tracks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_settings (
  user_id INTEGER NOT NULL,
  key TEXT NOT NULL,
  value TEXT,
  PRIMARY KEY (user_id, key),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rooms (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT NOT NULL UNIQUE,
  host_user_id INTEGER NOT NULL,
  playlist_id INTEGER,
  status TEXT NOT NULL DEFAULT 'lobby',
  started_at TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(host_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS room_players (
  room_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  joined_at TEXT NOT NULL DEFAULT (datetime('now')),
  last_seen_at TEXT,
  PRIMARY KEY (room_id, user_id),
  FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS room_rounds (
  room_id INTEGER NOT NULL,
  round INTEGER NOT NULL,
  track_id INTEGER NOT NULL,
  PRIMARY KEY (room_id, round),
  FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY(track_id) REFERENCES tracks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS room_games (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  room_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  round INTEGER NOT NULL,
  track_id INTEGER NOT NULL,
  reveals INTEGER NOT NULL DEFAULT 0,
  seconds_revealed INTEGER NOT NULL DEFAULT 0,
  guessed_title INTEGER NOT NULL DEFAULT 0,
  guessed_artist INTEGER NOT NULL DEFAULT 0,
  points INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(track_id) REFERENCES tracks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS matches (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  played_rounds INTEGER NOT NULL DEFAULT 0,
  total_points INTEGER NOT NULL DEFAULT 0,
  finished INTEGER NOT NULL DEFAULT 0,
  won INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS games (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  match_id INTEGER,
  round INTEGER,
  user_id INTEGER NOT NULL,
  track_id INTEGER NOT NULL,
  reveals INTEGER NOT NULL DEFAULT 0,
  seconds_revealed INTEGER NOT NULL DEFAULT 0,
  guessed_title INTEGER NOT NULL DEFAULT 0,
  guessed_artist INTEGER NOT NULL DEFAULT 0,
  points INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(match_id) REFERENCES matches(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(track_id) REFERENCES tracks(id) ON DELETE CASCADE
);
