import { createInertiaApp } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

const appName = import.meta.env.VITE_APP_NAME || 'BuwisBuddy';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Welcome and setup are full-screen; everything else sits in the app shell.
    layout: (name) =>
        name === 'welcome' ||
        name === 'data/restore' ||
        name.startsWith('setup/')
            ? null
            : AppLayout,
    progress: {
        color: '#1D4ED8',
    },
});
