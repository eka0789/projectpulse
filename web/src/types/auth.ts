export type UserRole = "admin" | "member";

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  job_title: string | null;
  avatar_url: string | null;
};

export type AuthSession = {
  token: string;
  tokenType: "Bearer";
  user: AuthUser;
};

export type LoginPayload = {
  email: string;
  password: string;
};

