import { useEffect, useState } from 'react';
import AuthScreen from './components/AuthScreen';
import TasksScreen from './components/TasksScreen';

export default function App() {
    const [token, setToken] = useState<string | null>(
        () => localStorage.getItem('auth_token'),
    );

    // Listen for 401-triggered logouts emitted by apiClient
    useEffect(() => {
        const handleForceLogout = () => setToken(null);
        window.addEventListener('auth:logout', handleForceLogout);
        return () => window.removeEventListener('auth:logout', handleForceLogout);
    }, []);

    const handleLogin = (newToken: string) => {
        localStorage.setItem('auth_token', newToken);
        setToken(newToken);
    };

    const handleLogout = () => {
        localStorage.removeItem('auth_token');
        setToken(null);
    };

    if (!token) {
        return <AuthScreen onLogin={handleLogin} />;
    }

    return <TasksScreen onLogout={handleLogout} />;
}
