// ── Task ──────────────────────────────────────────────────────────────────────

export type TaskStatus = 'pending' | 'in_progress' | 'completed';

export interface Task {
    id: number;
    title: string;
    description: string | null;
    status: TaskStatus;
    status_label: string;
    is_deleted: boolean;
    created_at: string;
}

// ── User ──────────────────────────────────────────────────────────────────────

export interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

// ── API response envelopes ────────────────────────────────────────────────────

export interface AuthResponse {
    data: User;
    token: string;
}

export interface TaskResponse {
    data: Task;
}

export interface TasksListResponse {
    data: Task[];
}

export interface MeResponse {
    data: User;
}
