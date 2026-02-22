import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './app';

const el = document.getElementById('app');

if (!el) {
    throw new Error('Root element #app not found in the DOM.');
}

createRoot(el).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>,
);
