import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

/**
 * Lazy Echo: the websocket used to connect on every page load, costing
 * main-thread time during startup and blocking back/forward-cache restores
 * even for visitors who never touch a realtime feature. `window.Echo` is now
 * a getter that builds the connection on first use — existing call sites
 * (`window.Echo.channel(...)`, `if (!window.Echo) ...`) work unchanged, and
 * pages that never subscribe never open a socket.
 */
let instance = null;

function createEcho() {
    return new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        // Private-channel auth (/broadcasting/auth) runs through web middleware → needs the CSRF token.
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        },
    });
}

Object.defineProperty(window, 'Echo', {
    configurable: true,
    get() {
        return (instance ??= createEcho());
    },
});
