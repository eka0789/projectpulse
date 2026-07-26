"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";

import {
  clearStoredSession,
  getStoredSession,
  storeSession,
} from "@/lib/auth-storage";
import {
  getCurrentUser,
  login as loginRequest,
  logout as logoutRequest,
} from "@/services/auth-service";
import type { AuthSession, AuthUser, LoginPayload } from "@/types/auth";

type AuthContextValue = {
  user: AuthUser | null;
  isReady: boolean;
  login: (payload: LoginPayload) => Promise<AuthUser>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [session, setSession] = useState<AuthSession | null>(null);
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    let active = true;
    const storedSession = getStoredSession();

    async function restoreSession() {
      await Promise.resolve();

      if (!storedSession) {
        if (active) setIsReady(true);
        return;
      }

      if (active) setSession(storedSession);

      try {
        const user = await getCurrentUser();
        const refreshed = { ...storedSession, user };
        storeSession(refreshed);
        if (active) setSession(refreshed);
      } catch {
        clearStoredSession();
        if (active) setSession(null);
      } finally {
        if (active) setIsReady(true);
      }
    }

    void restoreSession();
    return () => {
      active = false;
    };
  }, []);

  const login = useCallback(async (payload: LoginPayload) => {
    const nextSession = await loginRequest(payload);
    storeSession(nextSession);
    setSession(nextSession);
    return nextSession.user;
  }, []);

  const logout = useCallback(async () => {
    try {
      await logoutRequest();
    } finally {
      clearStoredSession();
      setSession(null);
    }
  }, []);

  const value = useMemo(
    () => ({ user: session?.user ?? null, isReady, login, logout }),
    [isReady, login, logout, session?.user],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within AuthProvider.");
  }

  return context;
}
