<?php

declare(strict_types=1);

namespace App;

final class Http {
    public static function dispatch(): void {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Basic routes
        if ($path === '/' && $method === 'GET') { view('home'); return; }

        if ($path === '/register' && $method === 'GET') { view('register'); return; }
        if ($path === '/register' && $method === 'POST') { csrf_check(); Auth::register($_POST); return; }

        if ($path === '/login' && $method === 'GET') { view('login'); return; }
        if ($path === '/login' && $method === 'POST') { csrf_check(); Auth::login($_POST); return; }

        if ($path === '/logout' && $method === 'GET') { Auth::logout(); return; }

        if ($path === '/play' && $method === 'GET') { Auth::require(); Game::play(); return; }
        if ($path === '/preview' && $method === 'GET') { Auth::require(); Game::preview(); return; }
        if ($path === '/guess' && $method === 'POST') { Auth::require(); csrf_check(); Game::guess($_POST); return; }
        if ($path === '/reveal' && $method === 'POST') { Auth::require(); csrf_check(); Game::reveal(); return; }

        if ($path === '/me' && $method === 'GET') { Auth::require(); Stats::me(); return; }
        if ($path === '/leaderboard' && $method === 'GET') { Stats::leaderboard(); return; }

        if ($path === '/playlists' && $method === 'GET') { Auth::require(); Playlists::page(); return; }
        if ($path === '/playlists/search' && $method === 'POST') { Auth::require(); csrf_check(); Playlists::search($_POST); return; }
        if ($path === '/playlists/select' && $method === 'POST') { Auth::require(); csrf_check(); Playlists::select($_POST); return; }

        if ($path === '/rooms' && $method === 'GET') { Auth::require(); Rooms::index(); return; }
        if ($path === '/rooms/create' && $method === 'POST') { Auth::require(); csrf_check(); Rooms::create($_POST); return; }
        if ($path === '/rooms/join' && $method === 'POST') { Auth::require(); csrf_check(); Rooms::join($_POST); return; }
        if ($path === '/room' && $method === 'GET') { Auth::require(); Rooms::lobby(); return; }
        if ($path === '/room/leave' && $method === 'GET') { Auth::require(); Rooms::leave(); return; }
        if ($path === '/room/ping' && $method === 'POST') { Auth::require(); csrf_check(); Rooms::ping(); return; }
        if ($path === '/room/kick' && $method === 'POST') { Auth::require(); csrf_check(); Rooms::kick($_POST); return; }
        if ($path === '/room/use-my-playlist' && $method === 'POST') { Auth::require(); csrf_check(); Rooms::useMyPlaylist(); return; }
        if ($path === '/room/start' && $method === 'POST') { Auth::require(); csrf_check(); Rooms::start($_POST); return; }
        if ($path === '/room/play' && $method === 'GET') { Auth::require(); Rooms::play(); return; }
        if ($path === '/room/reveal' && $method === 'POST') { Auth::require(); csrf_check(); Rooms::reveal(); return; }
        if ($path === '/room/guess' && $method === 'POST') { Auth::require(); csrf_check(); Rooms::guess($_POST); return; }
        if ($path === '/room/leaderboard' && $method === 'GET') { Auth::require(); Rooms::leaderboard(); return; }

        if ($path === '/admin' && $method === 'GET') { Auth::requireAdmin(); Admin::dashboard(); return; }
        if ($path === '/admin/import-chart' && $method === 'POST') { Auth::requireAdmin(); csrf_check(); Admin::importChart($_POST); return; }
        if ($path === '/admin/playlist-search' && $method === 'POST') { Auth::requireAdmin(); csrf_check(); Admin::playlistSearch($_POST); return; }
        if ($path === '/admin/import-playlist' && $method === 'POST') { Auth::requireAdmin(); csrf_check(); Admin::importPlaylist($_POST); return; }
        if ($path === '/admin/track-toggle' && $method === 'POST') { Auth::requireAdmin(); csrf_check(); Admin::toggleTrack($_POST); return; }

        http_response_code(404);
        echo '404';
    }
}
