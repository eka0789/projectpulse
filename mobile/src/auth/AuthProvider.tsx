import { useCallback, useEffect, useMemo, useState } from "react";

import { AuthContext } from "./auth-context";
import {
  api,
  currentUser,
  login as loginRequest,
  logout as logoutRequest,
} from "../services/api";
import { clearSession, getSession, setSession } from "../services/storage";
import type { Session } from "../types";

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [session, setSessionState] = useState<Session | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let active = true;
    async function restore() {
      const stored = await getSession();
      if (!stored) {
        if (active) setReady(true);
        return;
      }
      try {
        const user = await currentUser();
        if (user.role !== "member") throw new Error("Mobile access requires a member account.");
        const refreshed = { ...stored, user };
        await setSession(refreshed);
        if (active) setSessionState(refreshed);
      } catch {
        await clearSession();
      } finally {
        if (active) setReady(true);
      }
    }
    void restore();
    const unauthorized = () => setSessionState(null);
    window.addEventListener("projectpulse:unauthorized", unauthorized);
    return () => {
      active = false;
      window.removeEventListener("projectpulse:unauthorized", unauthorized);
    };
  }, []);

  const signIn = useCallback(async (email: string, password: string) => {
    const next = await loginRequest(email, password);
    if (next.user.role !== "member") {
      await setSession(next);
      try {
        await api.post("/auth/logout");
      } finally {
        await clearSession();
      }
      throw new Error("Administrator accounts use the ProjectPulse web workspace.");
    }
    await setSession(next);
    setSessionState(next);
  }, []);

  const signOut = useCallback(async () => {
    try {
      await logoutRequest();
    } finally {
      await clearSession();
      setSessionState(null);
    }
  }, []);

  const value = useMemo(
    () => ({ user: session?.user ?? null, ready, signIn, signOut }),
    [ready, session?.user, signIn, signOut],
  );
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
