import { apiClient } from "@/lib/api-client";
import type {
  ApiResponse,
  PaginatedResponse,
  PaginationMeta,
} from "@/types/api";
import type {
  AIGeneration,
  AppNotification,
  Client,
  Member,
  Project,
  Task,
  TaskStatus,
  TimeLogReport,
} from "@/types/resources";

export type ListParams = {
  search?: string;
  page?: number;
  per_page?: number;
  status?: string;
  project_id?: number;
  assignee_id?: number;
};

async function list<T>(path: string, params?: ListParams) {
  const response = await apiClient.get<PaginatedResponse<T>>(path, { params });
  return response.data;
}

export const clientsApi = {
  list: (params?: ListParams) => list<Client>("/clients", params),
  get: async (id: number) =>
    (await apiClient.get<ApiResponse<Client>>(`/clients/${id}`)).data.data,
  create: async (payload: Record<string, unknown>) =>
    (
      await apiClient.post<ApiResponse<Client>>("/clients", payload)
    ).data.data,
  update: async (id: number, payload: Partial<Client>) =>
    (
      await apiClient.patch<ApiResponse<Client>>(`/clients/${id}`, payload)
    ).data.data,
  remove: async (id: number) => {
    await apiClient.delete(`/clients/${id}`);
  },
};

export const projectsApi = {
  list: (params?: ListParams) => list<Project>("/projects", params),
  get: async (id: number) =>
    (await apiClient.get<ApiResponse<Project>>(`/projects/${id}`)).data.data,
  create: async (payload: Record<string, unknown>) =>
    (
      await apiClient.post<ApiResponse<Project>>("/projects", payload)
    ).data.data,
  update: async (id: number, payload: Partial<Project>) =>
    (
      await apiClient.patch<ApiResponse<Project>>(`/projects/${id}`, payload)
    ).data.data,
  remove: async (id: number) => {
    await apiClient.delete(`/projects/${id}`);
  },
  generateTasks: async (
    projectId: number,
    brief: string,
    maximumTasks: number,
  ) =>
    (
      await apiClient.post<ApiResponse<AIGeneration>>(
        `/projects/${projectId}/tasks/generate`,
        {
          brief,
          preferences: {
            include_qa: true,
            include_devops: true,
            maximum_tasks: maximumTasks,
          },
        },
      )
    ).data.data,
  bulkTasks: async (
    projectId: number,
    tasks: Array<Record<string, unknown>>,
  ) =>
    (
      await apiClient.post<ApiResponse<Task[]>>(
        `/projects/${projectId}/tasks/bulk`,
        { tasks },
      )
    ).data.data,
};

export const tasksApi = {
  list: (params?: ListParams) => list<Task>("/tasks", params),
  get: async (id: number) =>
    (await apiClient.get<ApiResponse<Task>>(`/tasks/${id}`)).data.data,
  create: async (projectId: number, payload: Record<string, unknown>) =>
    (
      await apiClient.post<ApiResponse<Task>>(
        `/projects/${projectId}/tasks`,
        payload,
      )
    ).data.data,
  update: async (id: number, payload: Partial<Task>) =>
    (
      await apiClient.patch<ApiResponse<Task>>(`/tasks/${id}`, payload)
    ).data.data,
  updateStatus: async (id: number, status: TaskStatus) =>
    (
      await apiClient.patch<ApiResponse<Task>>(`/tasks/${id}/status`, {
        status,
      })
    ).data.data,
  remove: async (id: number) => {
    await apiClient.delete(`/tasks/${id}`);
  },
};

export const membersApi = {
  list: (params?: ListParams) => list<Member>("/members", params),
  create: async (payload: Record<string, unknown>) =>
    (
      await apiClient.post<ApiResponse<Member>>("/members", payload)
    ).data.data,
  update: async (id: number, payload: Record<string, unknown>) =>
    (
      await apiClient.patch<ApiResponse<Member>>(`/members/${id}`, payload)
    ).data.data,
  deactivate: async (id: number) => {
    await apiClient.delete(`/members/${id}`);
  },
};

export const reportsApi = {
  timeLogs: async () =>
    (
      await apiClient.get<ApiResponse<TimeLogReport>>("/reports/time-logs")
    ).data.data,
  exportCsv: async () =>
    (
      await apiClient.get<Blob>("/reports/time-logs/export.csv", {
        responseType: "blob",
      })
    ).data,
};

export const notificationsApi = {
  list: async () => {
    const response =
      await apiClient.get<
        ApiResponse<AppNotification[], PaginationMeta>
      >("/notifications");
    return response.data;
  },
  unreadCount: async () =>
    (
      await apiClient.get<ApiResponse<{ unread_count: number }>>(
        "/notifications/unread-count",
      )
    ).data.data.unread_count,
  markRead: async (id: string) =>
    (
      await apiClient.patch<ApiResponse<AppNotification>>(
        `/notifications/${id}/read`,
      )
    ).data.data,
  markAllRead: async () => {
    await apiClient.patch("/notifications/read-all");
  },
};
