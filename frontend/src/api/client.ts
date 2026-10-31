export class ApiError extends Error {
  status: number;
  data: any;
  errors?: Record<string, string[]>;

  constructor(status: number, data: any) {
    const message = data?.message || (status === 401 ? 'Unauthenticated' : `Request failed with status ${status}`);
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
    if (data?.errors) {
      this.errors = data.errors;
    }
  }

  getFirstError(): string {
    if (this.errors) {
      const firstKey = Object.keys(this.errors)[0];
      if (firstKey && this.errors[firstKey]?.length) {
        return this.errors[firstKey][0];
      }
    }
    return this.message;
  }
}

export function getXsrfToken(): string | null {
  if (typeof document === 'undefined') return null;
  const match = document.cookie.match(/(^|;)\s*XSRF-TOKEN\s*=\s*([^;]+)/);
  return match ? decodeURIComponent(match[2]) : null;
}

export async function ensureCsrfToken(): Promise<void> {
  if (!getXsrfToken()) {
    try {
      await fetch('/api/me', {
        method: 'GET',
        credentials: 'include',
        headers: { Accept: 'application/json' },
      });
    } catch {
      // Ignore failure, subsequent request will proceed
    }
  }
}

export async function apiFetch<T = any>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const method = (options.method || 'GET').toUpperCase();
  const isMutation = method !== 'GET' && method !== 'HEAD';

  if (isMutation) {
    await ensureCsrfToken();
  }

  const url = endpoint.startsWith('http')
    ? endpoint
    : endpoint.startsWith('/api')
      ? endpoint
      : `/api${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;

  const headers: Record<string, string> = {
    Accept: 'application/json',
    ...(options.headers as Record<string, string> || {}),
  };

  const xsrf = getXsrfToken();
  if (xsrf) {
    headers['X-XSRF-TOKEN'] = xsrf;
  }

  if (options.body && !(options.body instanceof FormData) && typeof options.body === 'object') {
    headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(options.body);
  }

  const response = await fetch(url, {
    ...options,
    method,
    headers,
    credentials: 'include',
  });

  if (!response.ok) {
    let errorData: any = null;
    try {
      errorData = await response.json();
    } catch {
      errorData = { message: response.statusText || `HTTP ${response.status}` };
    }
    throw new ApiError(response.status, errorData);
  }

  if (response.status === 204) {
    return null as T;
  }

  const text = await response.text();
  if (!text || text.trim() === '' || text.trim() === 'null') {
    return null as T;
  }

  try {
    return JSON.parse(text) as T;
  } catch {
    return text as unknown as T;
  }
}

export const api = {
  get: <T = any>(url: string, options?: RequestInit) => apiFetch<T>(url, { ...options, method: 'GET' }),
  post: <T = any>(url: string, body?: any, options?: RequestInit) => apiFetch<T>(url, { ...options, method: 'POST', body }),
  put: <T = any>(url: string, body?: any, options?: RequestInit) => apiFetch<T>(url, { ...options, method: 'PUT', body }),
  delete: <T = any>(url: string, options?: RequestInit) => apiFetch<T>(url, { ...options, method: 'DELETE' }),
};
