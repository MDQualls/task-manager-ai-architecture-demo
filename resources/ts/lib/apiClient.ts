const BASE_URL = '/api/v1';

type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

// ── Error class ───────────────────────────────────────────────────────────────

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly errors?: Record<string, string[]>,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

// ── Core request ──────────────────────────────────────────────────────────────

async function request<T = unknown>(
    method: Method,
    path: string,
    body?: unknown,
): Promise<T> {
    const token = localStorage.getItem('auth_token');

    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const res = await fetch(`${BASE_URL}${path}`, {
        method,
        headers,
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    // 401 → clear stored token and signal the app to go back to login
    if (res.status === 401) {
        localStorage.removeItem('auth_token');
        window.dispatchEvent(new CustomEvent('auth:logout'));
        throw new ApiError('Session expired. Please log in again.', 401);
    }

    // 204 No Content — nothing to parse
    if (res.status === 204) {
        return undefined as T;
    }

    // Try to parse JSON; fall back to empty object so error handling below works
    const text = await res.text();
    const json = text ? (JSON.parse(text) as Record<string, unknown>) : {};

    if (!res.ok) {
        throw new ApiError(
            (json['message'] as string | undefined) ?? `HTTP ${res.status}`,
            res.status,
            json['errors'] as Record<string, string[]> | undefined,
        );
    }

    return json as T;
}

// ── Public helpers ────────────────────────────────────────────────────────────

export const apiClient = {
    get:    <T>(path: string)                  => request<T>('GET',    path),
    post:   <T>(path: string, body?: unknown)  => request<T>('POST',   path, body),
    put:    <T>(path: string, body: unknown)   => request<T>('PUT',    path, body),
    patch:  <T>(path: string, body: unknown)   => request<T>('PATCH',  path, body),
    delete: <T>(path: string)                  => request<T>('DELETE', path),
};
