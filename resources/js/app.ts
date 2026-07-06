import '../css/app.css';

import { App as CapApp } from '@capacitor/app';
import { SplashScreen } from '@capacitor/splash-screen';
import { StatusBar, Style } from '@capacitor/status-bar';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const appName = import.meta.env.VITE_APP_NAME || 'TAMPCO queuing system';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || '',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    forceTLS: true,
});

// Make realtime updates more resilient on flaky/slow connections.
// (Browsers may suspend WebSockets in the background; this helps reconnect.)
function setupEchoResilience() {
    const echoAny = (window as any).Echo;
    const pusher = echoAny?.connector?.pusher;
    if (!pusher || !pusher.connection) return;

    const connectIfNeeded = () => {
        const state = pusher.connection.state;
        if (state !== 'connected' && state !== 'connecting') {
            try {
                pusher.connect();
            } catch {
                // ignore
            }
        }
    };

    window.addEventListener('online', connectIfNeeded);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) connectIfNeeded();
    });

    // Periodic safety-net reconnect.
    window.setInterval(connectIfNeeded, 15000);
}

setupEchoResilience();

// Initialize Capacitor native plugins when running inside a native shell.
async function initCapacitor() {
    try {
        await StatusBar.setStyle({ style: Style.Dark });
        await StatusBar.setBackgroundColor({ color: '#1e3a5f' });
    } catch {
        // Not running in native shell; safe to ignore.
    }
    try {
        await SplashScreen.hide({ fadeOutDuration: 300 });
    } catch {
        // ignore
    }
    // Handle Android hardware back button: go back in history or exit.
    CapApp.addListener('backButton', ({ canGoBack }) => {
        if (canGoBack) {
            window.history.back();
        } else {
            CapApp.exitApp();
        }
    });
}

initCapacitor();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: any;
    }
}
