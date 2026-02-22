import { useCallback, useEffect, useState } from 'react';
import { apiClient, ApiError } from '../lib/apiClient';
import {
    MeResponse,
    Task,
    TaskResponse,
    TasksListResponse,
    TaskStatus,
    User,
} from '../types';
import TaskModal from './TaskModal';

interface Props {
    onLogout: () => void;
}

const STATUS_PILL: Record<TaskStatus, string> = {
    pending:     'bg-amber-100 text-amber-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed:   'bg-green-100 text-green-700',
};

const STATUS_DOT: Record<TaskStatus, string> = {
    pending:     'bg-amber-400',
    in_progress: 'bg-blue-500',
    completed:   'bg-green-500',
};

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric',
    });
}

export default function TasksScreen({ onLogout }: Props) {
    const [tasks, setTasks]       = useState<Task[]>([]);
    const [me, setMe]             = useState<User | null>(null);
    const [loadingTasks, setLoadingTasks] = useState(true);
    const [loadingMe, setLoadingMe]       = useState(true);
    const [listError, setListError]       = useState<string | null>(null);

    const [modalOpen, setModalOpen]       = useState(false);
    const [editingTask, setEditingTask]   = useState<Task | null>(null);
    const [modalLoading, setModalLoading] = useState(false);
    const [modalError, setModalError]     = useState<string | null>(null);

    const [deletingId, setDeletingId] = useState<number | null>(null);

    const fetchTasks = useCallback(async () => {
        setLoadingTasks(true);
        setListError(null);
        try {
            const res = await apiClient.get<TasksListResponse>('/tasks');
            setTasks(res.data);
        } catch (err) {
            setListError(err instanceof ApiError ? err.message : 'Failed to load tasks.');
        } finally {
            setLoadingTasks(false);
        }
    }, []);

    const fetchMe = useCallback(async () => {
        setLoadingMe(true);
        try {
            const res = await apiClient.get<MeResponse>('/auth/me');
            setMe(res.data);
        } catch {
            // Non-critical; silently degrade
        } finally {
            setLoadingMe(false);
        }
    }, []);

    useEffect(() => {
        fetchTasks();
        fetchMe();
    }, [fetchTasks, fetchMe]);

    const handleLogout = async () => {
        try {
            await apiClient.post('/auth/logout');
        } catch {
            // Ignore API errors — log out locally regardless
        }
        onLogout();
    };

    const openCreate = () => {
        setEditingTask(null);
        setModalError(null);
        setModalOpen(true);
    };

    const openEdit = (task: Task) => {
        setEditingTask(task);
        setModalError(null);
        setModalOpen(true);
    };

    const closeModal = () => setModalOpen(false);

    const handleSave = async (title: string, description: string, status: TaskStatus) => {
        setModalLoading(true);
        setModalError(null);

        try {
            if (editingTask) {
                // PATCH — partial update, keeps server values for any omitted field
                const res = await apiClient.patch<TaskResponse>(`/tasks/${editingTask.id}`, {
                    title,
                    description: description || null,
                    status,
                });
                setTasks((prev) => prev.map((t) => (t.id === editingTask.id ? res.data : t)));
            } else {
                const res = await apiClient.post<TaskResponse>('/tasks', {
                    title,
                    description: description || null,
                    status,
                });
                // Prepend so the new task appears at the top
                setTasks((prev) => [res.data, ...prev]);
            }
            setModalOpen(false);
        } catch (err) {
            setModalError(err instanceof ApiError ? err.message : 'Failed to save task.');
        } finally {
            setModalLoading(false);
        }
    };

    const handleDelete = async (id: number) => {
        if (!window.confirm('Delete this task? This cannot be undone.')) return;

        setDeletingId(id);
        try {
            await apiClient.delete(`/tasks/${id}`);
            setTasks((prev) => prev.filter((t) => t.id !== id));
        } catch (err) {
            window.alert(err instanceof ApiError ? err.message : 'Failed to delete task.');
        } finally {
            setDeletingId(null);
        }
    };

    return (
        <div className="min-h-screen bg-slate-50">
            <header className="sticky top-0 z-10 bg-white border-b border-slate-200">
                <div className="mx-auto max-w-5xl px-4 py-3 flex items-center justify-between">
                    <span className="text-lg font-bold text-slate-800">🗂 SuperTask</span>
                    <button
                        onClick={handleLogout}
                        className="rounded-lg border border-slate-200 px-4 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 transition"
                    >
                        Logout
                    </button>
                </div>
            </header>
            <main className="mx-auto max-w-5xl px-4 py-8 grid grid-cols-1 md:grid-cols-[1fr_280px] gap-6 items-start">
                <section>
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xl font-semibold text-slate-800">Tasks</h2>
                        <button
                            onClick={openCreate}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition"
                        >
                            + New Task
                        </button>
                    </div>
                    {listError && (
                        <div className="mb-4 flex items-center justify-between rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            <span>{listError}</span>
                            <button
                                onClick={fetchTasks}
                                className="ml-4 underline text-red-600 hover:text-red-800"
                            >
                                Retry
                            </button>
                        </div>
                    )}
                    {loadingTasks ? (
                        <ul className="space-y-3">
                            {[1, 2, 3].map((i) => (
                                <li
                                    key={i}
                                    className="h-20 rounded-xl bg-white border border-slate-100 animate-pulse"
                                />
                            ))}
                        </ul>
                    ) : tasks.length === 0 ? (
                        <div className="rounded-xl bg-white border border-slate-100 p-12 text-center text-slate-400 text-sm">
                            No tasks yet — create your first one!
                        </div>
                    ) : (
                        <ul className="space-y-3">
                            {tasks.map((task) => (
                                <li
                                    key={task.id}
                                    className="flex gap-3 items-start rounded-xl bg-white border border-slate-100 p-4 shadow-sm"
                                >
                                    <span
                                        className={`mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ${STATUS_DOT[task.status]}`}
                                    />
                                    <div className="flex-1 min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span
                                                className={`text-sm font-medium leading-snug ${
                                                    task.status === 'completed'
                                                        ? 'line-through text-slate-400'
                                                        : 'text-slate-800'
                                                }`}
                                            >
                                                {task.title}
                                            </span>
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_PILL[task.status]}`}
                                            >
                                                {task.status_label}
                                            </span>
                                        </div>
                                        {task.description && (
                                            <p className="mt-1 text-xs text-slate-500 truncate">
                                                {task.description}
                                            </p>
                                        )}
                                        <p className="mt-1 text-xs text-slate-400">
                                            {formatDate(task.created_at)}
                                        </p>
                                    </div>
                                    <div className="flex gap-1 shrink-0">
                                        <button
                                            onClick={() => openEdit(task)}
                                            className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            onClick={() => handleDelete(task.id)}
                                            disabled={deletingId === task.id}
                                            className="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-50 transition"
                                        >
                                            {deletingId === task.id ? '…' : 'Delete'}
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
                <aside>
                    <h2 className="text-xl font-semibold text-slate-800 mb-4">Me</h2>
                    <div className="rounded-xl bg-white border border-slate-100 p-5 shadow-sm">
                        {loadingMe ? (
                            <div className="space-y-3">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-full bg-slate-100 animate-pulse" />
                                    <div className="flex-1 space-y-2">
                                        <div className="h-3 w-3/4 rounded bg-slate-100 animate-pulse" />
                                        <div className="h-3 w-1/2 rounded bg-slate-100 animate-pulse" />
                                    </div>
                                </div>
                            </div>
                        ) : me ? (
                            <div>
                                <div className="flex items-center gap-3 mb-4">
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white text-sm font-semibold">
                                        {me.name.slice(0, 2).toUpperCase()}
                                    </span>
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-slate-800 truncate">
                                            {me.name}
                                        </p>
                                        <p className="text-xs text-slate-500 truncate">
                                            {me.email}
                                        </p>
                                    </div>
                                </div>
                                <div className="border-t border-slate-100 pt-3">
                                    <p className="text-xs text-slate-400 mb-0.5">Member since</p>
                                    <p className="text-sm text-slate-700">{formatDate(me.created_at)}</p>
                                </div>
                            </div>
                        ) : (
                            <p className="text-sm text-slate-400">Could not load profile.</p>
                        )}
                    </div>
                </aside>
            </main>
            {modalOpen && (
                <TaskModal
                    task={editingTask}
                    loading={modalLoading}
                    error={modalError}
                    onSave={handleSave}
                    onClose={closeModal}
                />
            )}
        </div>
    );
}
