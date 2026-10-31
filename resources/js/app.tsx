import { createInertiaApp } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

const appName = import.meta.env.VITE_APP_NAME || 'BuwisBuddy';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Welcome, setup, the lock screen and restore are full-screen; everything else sits in the app shell.
    layout: (name) =>
        ['welcome', 'lock', 'data/restore'].includes(name) ||
        name.startsWith('setup/')
            ? null
            : AppLayout,
    progress: {
        color: '#1D4ED8',
    },
});
