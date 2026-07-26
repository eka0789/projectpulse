import { apiClient } from "@/lib/api-client";
import type { ApiResponse } from "@/types/api";
import type { AuthSession, AuthUser, LoginPayload } from "@/types/auth";

type LoginResponse = {
  token: string;
  token_type: "Bearer";
  user: AuthUser;
};

export async function login(payload: LoginPayload): Promise<AuthSession> {
  const response = await apiClient.post<ApiResponse<LoginResponse>>(
    "/auth/login",
    payload,
  );

  return {
    token: response.data.data.token,
    tokenType: response.data.data.token_type,
    user: response.data.data.user,
  };
}

export async function logout(): Promise<void> {
  await apiClient.post("/auth/logout");
}

export async function getCurrentUser(): Promise<AuthUser> {
  const response =
    await apiClient.get<ApiResponse<AuthUser>>("/auth/me");
  return response.data.data;
}

