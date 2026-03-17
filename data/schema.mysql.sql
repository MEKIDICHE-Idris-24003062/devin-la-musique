-- MySQL schema for devin-la-musique
-- Engine/charset chosen for Alwaysdata / common shared hosting.

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tracks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  deezer_track_id BIGINT NOT NULL,
  title TEXT NOT NULL,
  artist TEXT NOT NULL,
  album TEXT,
  release_date VARCHAR(32),
  preview_url TEXT,
  link TEXT,
  playlist_id BIGINT NULL,
  playlist_title TEXT,
  title_norm TEXT NOT NULL,
  artist_norm TEXT NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_tracks_deezer_track_id (deezer_track_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(190) NOT NULL,
  value TEXT,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlists (
  deezer_playlist_id BIGINT NOT NULL,
  title TEXT,
  PRIMARY KEY (deezer_playlist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlist_tracks (
  deezer_playlist_id BIGINT NOT NULL,
  track_id INT NOT NULL,
  PRIMARY KEY (deezer_playlist_id, track_id),
  CONSTRAINT fk_playlist_tracks_playlist
    FOREIGN KEY (deezer_playlist_id) REFERENCES playlists(deezer_playlist_id) ON DELETE CASCADE,
  CONSTRAINT fk_playlist_tracks_track
    FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_settings (
  user_id INT NOT NULL,
  `key` VARCHAR(190) NOT NULL,
  value TEXT,
  PRIMARY KEY (user_id, `key`),
  CONSTRAINT fk_user_settings_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) NOT NULL,
  host_user_id INT NOT NULL,
  playlist_id BIGINT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'lobby',
  started_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_rooms_code (code),
  KEY idx_rooms_host_user_id (host_user_id),
  CONSTRAINT fk_rooms_host_user
    FOREIGN KEY (host_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_players (
  room_id INT NOT NULL,
  user_id INT NOT NULL,
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at DATETIME NULL,
  PRIMARY KEY (room_id, user_id),
  KEY idx_room_players_user_id (user_id),
  CONSTRAINT fk_room_players_room
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  CONSTRAINT fk_room_players_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_rounds (
  room_id INT NOT NULL,
  `round` INT NOT NULL,
  track_id INT NOT NULL,
  PRIMARY KEY (room_id, `round`),
  KEY idx_room_rounds_track_id (track_id),
  CONSTRAINT fk_room_rounds_room
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  CONSTRAINT fk_room_rounds_track
    FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  user_id INT NOT NULL,
  `round` INT NOT NULL,
  track_id INT NOT NULL,
  reveals INT NOT NULL DEFAULT 0,
  seconds_revealed INT NOT NULL DEFAULT 0,
  guessed_title TINYINT(1) NOT NULL DEFAULT 0,
  guessed_artist TINYINT(1) NOT NULL DEFAULT 0,
  points INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_room_games_room_id (room_id),
  KEY idx_room_games_user_id (user_id),
  KEY idx_room_games_track_id (track_id),
  CONSTRAINT fk_room_games_room
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  CONSTRAINT fk_room_games_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_room_games_track
    FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  played_rounds INT NOT NULL DEFAULT 0,
  total_points INT NOT NULL DEFAULT 0,
  finished TINYINT(1) NOT NULL DEFAULT 0,
  won TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_matches_user_id (user_id),
  CONSTRAINT fk_matches_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  match_id INT NULL,
  `round` INT NULL,
  user_id INT NOT NULL,
  track_id INT NOT NULL,
  reveals INT NOT NULL DEFAULT 0,
  seconds_revealed INT NOT NULL DEFAULT 0,
  guessed_title TINYINT(1) NOT NULL DEFAULT 0,
  guessed_artist TINYINT(1) NOT NULL DEFAULT 0,
  points INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_games_match_id (match_id),
  KEY idx_games_user_id (user_id),
  KEY idx_games_track_id (track_id),
  CONSTRAINT fk_games_match
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
  CONSTRAINT fk_games_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_games_track
    FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
