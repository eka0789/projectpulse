import axios from "axios";

import { clearStoredSession, getStoredSession } from "@/lib/auth-storage";

export const apiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api",
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
  timeout: 15_000,
});

apiClient.interceptors.request.use((config) => {
  const token = getStoredSession()?.token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

let isRedirecting = false;

apiClient.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      clearStoredSession();
      if (
        !isRedirecting &&
        typeof window !== "undefined" &&
        window.location.pathname !== "/login"
      ) {
        isRedirecting = true;
        window.location.assign("/login?reason=session-expired");
      }
    }

    return Promise.reject(error);
  },
);

export function getApiErrorMessage(
  error: unknown,
  fallback = "Something went wrong. Please try again.",
): string {
  if (!axios.isAxiosError(error)) {
    return fallback;
  }

  if (!error.response) {
    return "Unable to reach the API. Check that the backend is running.";
  }

  const response = error.response.data as
    | { message?: string; error?: { details?: Record<string, string[]> } }
    | undefined;
  const firstValidationMessage = response?.error?.details
    ? Object.values(response.error.details).flat()[0]
    : undefined;

  return firstValidationMessage ?? response?.message ?? fallback;
}

