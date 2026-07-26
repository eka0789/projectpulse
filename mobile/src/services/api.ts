import axios from "axios";

import { clearSession, getSession } from "./storage";
import type {
  ApiResponse,
  Notification,
  PaginationMeta,
  ProgressNote,
  Session,
  Task,
  TaskStatus,
  TimeLog,
  User,
} from "../types";

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? "http://10.0.2.2:8000/api",
  timeout: 15_000,
  headers: { Accept: "application/json", "Content-Type": "application/json" },
});

api.interceptors.request.use(async (config) => {
  const token = (await getSession())?.token;
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error: unknown) => {
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      await clearSession();
      window.dispatchEvent(new CustomEvent("projectpulse:unauthorized"));
    }
    return Promise.reject(error);
  },
);

export function errorMessage(error: unknown): string {
  if (!axios.isAxiosError(error)) {
    return error instanceof Error ? error.message : "Something went wrong.";
  }
  if (!error.response) return "The API is offline. Check your network and try again.";
  const payload = error.response.data as
    | { message?: string; error?: { details?: Record<string, string[]> } }
    | undefined;
  const validation = payload?.error?.details
    ? Object.values(payload.error.details).flat()[0]
    : undefined;
  return validation ?? payload?.message ?? "The request could not be completed.";
}

export async function login(email: string, password: string): Promise<Session> {
  const response = await api.post<
    ApiResponse<{ token: string; token_type: string; user: User }>
  >("/auth/login", { email, password });
  return { token: response.data.data.token, user: response.data.data.user };
}

export async function logout(): Promise<void> {
  await api.post("/auth/logout");
}

export async function currentUser(): Promise<User> {
  return (await api.get<ApiResponse<User>>("/auth/me")).data.data;
}

export async function listTasks(
  status?: TaskStatus,
): Promise<{ data: Task[]; meta: PaginationMeta }> {
  const response = await api.get<ApiResponse<Task[], PaginationMeta>>("/tasks", {
    params: { status, per_page: 100 },
  });
  return { data: response.data.data, meta: response.data.meta };
}

export async function taskDetail(id: number): Promise<Task> {
  return (await api.get<ApiResponse<Task>>(`/tasks/${id}`)).data.data;
}

export async function updateTaskStatus(id: number, status: TaskStatus): Promise<Task> {
  return (
    await api.patch<ApiResponse<Task>>(`/tasks/${id}/status`, { status })
  ).data.data;
}

export async function addTimeLog(
  taskId: number,
  input: { work_date: string; duration_minutes: number; note?: string },
): Promise<TimeLog> {
  return (
    await api.post<ApiResponse<TimeLog>>(`/tasks/${taskId}/time-logs`, input)
  ).data.data;
}

export async function addProgressNote(taskId: number, note: string): Promise<ProgressNote> {
  return (
    await api.post<ApiResponse<ProgressNote>>(`/tasks/${taskId}/progress-notes`, {
      note,
    })
  ).data.data;
}

export async function listNotifications(): Promise<Notification[]> {
  return (
    await api.get<ApiResponse<Notification[], PaginationMeta>>("/notifications", {
      params: { per_page: 100 },
    })
  ).data.data;
}

export async function markNotificationRead(id: string): Promise<void> {
  await api.patch(`/notifications/${id}/read`);
}

export async function markAllNotificationsRead(): Promise<void> {
  await api.patch("/notifications/read-all");
}

