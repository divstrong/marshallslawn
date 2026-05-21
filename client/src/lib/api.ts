/**
 * Thin typed client over the Laravel API. A single bearer token is held
 * in memory and attached to every request; `setAuthToken` is called by
 * the auth context whenever the session changes.
 */

import { API_BASE_URL } from '@/constants/config';
import type {
  ChatMessage,
  Customer,
  Employee,
  Job,
  JobMedia,
  JobMessage,
  Property,
  Quote,
  Role,
  SchedulePayload,
  ServiceOption,
  TimeLog,
  TimeLogsPayload,
  QuotePayload,
} from '@/lib/types';

let authToken: string | null = null;

export function setAuthToken(token: string | null): void {
  authToken = token;
}

/** Error carrying the HTTP status and any field-level validation errors. */
export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

type Query = Record<string, string | number | boolean | undefined | null>;

function buildUrl(path: string, query?: Query): string {
  const url = `${API_BASE_URL}${path}`;
  if (!query) {
    return url;
  }
  const params = Object.entries(query)
    .filter(([, value]) => value !== undefined && value !== null && value !== '')
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);
  return params.length > 0 ? `${url}?${params.join('&')}` : url;
}

async function request<T>(
  method: string,
  path: string,
  options: { query?: Query; body?: unknown } = {},
): Promise<T> {
  let response: Response;
  try {
    response = await fetch(buildUrl(path, options.query), {
      method,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      },
      body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    });
  } catch {
    throw new ApiError('Could not reach the server. Check your connection.', 0);
  }

  if (response.status === 204) {
    return undefined as T;
  }

  let payload: any = null;
  try {
    payload = await response.json();
  } catch {
    payload = null;
  }

  if (!response.ok) {
    const message: string = payload?.message ?? `Request failed (${response.status}).`;
    throw new ApiError(message, response.status, payload?.errors ?? {});
  }

  return payload as T;
}

/** Endpoints return `{ data: ... }` for resources; unwrap that here. */
function unwrap<T>(payload: { data: T }): T {
  return payload.data;
}

export const api = {
  // --- Auth ---
  login(email: string, password: string) {
    return request<{ token: string; employee: Employee }>('POST', '/login', {
      body: { email, password },
    });
  },
  devLogin(role: Role) {
    return request<{ token: string; employee: Employee }>('POST', '/dev/login', {
      body: { role },
    });
  },
  me() {
    return request<{ data: Employee }>('GET', '/me').then(unwrap);
  },
  logout() {
    return request<{ message: string }>('POST', '/logout');
  },

  // --- Jobs ---
  jobs(filter: string = 'all') {
    return request<{ data: Job[] }>('GET', '/jobs', { query: { filter } }).then(unwrap);
  },
  job(id: number) {
    return request<{ data: Job }>('GET', `/jobs/${id}`).then(unwrap);
  },
  startJob(id: number) {
    return request<{ data: Job }>('POST', `/jobs/${id}/start`).then(unwrap);
  },
  completeJob(id: number) {
    return request<{ data: Job }>('POST', `/jobs/${id}/complete`).then(unwrap);
  },
  addJobNote(id: number, body: string) {
    return request<{ data: JobMessage }>('POST', `/jobs/${id}/notes`, { body: { body } }).then(
      unwrap,
    );
  },
  async uploadJobPhoto(
    jobId: number,
    file: { uri: string; name: string; type: string },
  ): Promise<JobMedia> {
    // Multipart upload — let fetch set the Content-Type/boundary itself.
    const form = new FormData();
    form.append('photo', { uri: file.uri, name: file.name, type: file.type } as any);

    let response: Response;
    try {
      response = await fetch(buildUrl(`/jobs/${jobId}/media`), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
        },
        body: form,
      });
    } catch {
      throw new ApiError('Could not reach the server. Check your connection.', 0);
    }

    let payload: any = null;
    try {
      payload = await response.json();
    } catch {
      payload = null;
    }

    if (!response.ok) {
      throw new ApiError(
        payload?.message ?? `Upload failed (${response.status}).`,
        response.status,
        payload?.errors ?? {},
      );
    }
    return payload.data as JobMedia;
  },
  deleteJobPhoto(jobId: number, mediaId: number) {
    return request<{ deleted: boolean }>('DELETE', `/jobs/${jobId}/media/${mediaId}`);
  },

  // --- Chat (foreman <-> office) ---
  chat() {
    return request<{ data: ChatMessage[] }>('GET', '/chat').then(unwrap);
  },
  chatUnread() {
    return request<{ count: number }>('GET', '/chat/unread');
  },
  async sendChatMessage(input: {
    body?: string;
    file?: { uri: string; name: string; type: string };
  }): Promise<ChatMessage> {
    const form = new FormData();
    if (input.body) {
      form.append('body', input.body);
    }
    if (input.file) {
      form.append('attachment', {
        uri: input.file.uri,
        name: input.file.name,
        type: input.file.type,
      } as any);
    }

    let response: Response;
    try {
      response = await fetch(buildUrl('/chat'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
        },
        body: form,
      });
    } catch {
      throw new ApiError('Could not reach the server. Check your connection.', 0);
    }

    let payload: any = null;
    try {
      payload = await response.json();
    } catch {
      payload = null;
    }

    if (!response.ok) {
      throw new ApiError(
        payload?.message ?? `Send failed (${response.status}).`,
        response.status,
        payload?.errors ?? {},
      );
    }
    return payload.data as ChatMessage;
  },

  // --- Push notifications ---
  registerPushToken(token: string, platform: string) {
    return request<{ registered: boolean }>('POST', '/push-token', {
      body: { token, platform },
    });
  },
  removePushToken(token: string) {
    return request<{ removed: boolean }>('DELETE', '/push-token', { body: { token } });
  },

  // --- Schedule ---
  schedule(date?: string) {
    return request<SchedulePayload>('GET', '/schedule', { query: { date } });
  },

  // --- Time logs ---
  timeLogs() {
    return request<TimeLogsPayload>('GET', '/time-logs');
  },
  clockIn() {
    return request<{ shift: TimeLog }>('POST', '/time-logs/clock-in');
  },
  clockOut() {
    return request<{ shift: TimeLog }>('POST', '/time-logs/clock-out');
  },

  // --- Quotes ---
  quotes(status?: string) {
    return request<{ data: Quote[] }>('GET', '/quotes', { query: { status } }).then(unwrap);
  },
  quote(id: number) {
    return request<{ data: Quote }>('GET', `/quotes/${id}`).then(unwrap);
  },
  createQuote(payload: QuotePayload) {
    return request<{ data: Quote }>('POST', '/quotes', { body: payload }).then(unwrap);
  },
  updateQuote(id: number, payload: QuotePayload) {
    return request<{ data: Quote }>('PATCH', `/quotes/${id}`, { body: payload }).then(unwrap);
  },

  // --- Lookups ---
  customers(query?: string) {
    return request<{ data: Customer[] }>('GET', '/customers', { query: { q: query } }).then(
      unwrap,
    );
  },
  customerProperties(customerId: number) {
    return request<{ data: Property[] }>('GET', `/customers/${customerId}/properties`).then(
      unwrap,
    );
  },
  services() {
    return request<{ data: ServiceOption[] }>('GET', '/services').then(unwrap);
  },
};
