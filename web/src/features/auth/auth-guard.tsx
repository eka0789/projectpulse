"use client";

import { LoaderCircle } from "lucide-react";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";

import { useAuth } from "@/features/auth/auth-provider";

export function AuthGuard({ children }: { children: React.ReactNode }) {
  const { user, isReady } = useAuth();
  const pathname = usePathname();
  const router = useRouter();

  useEffect(() => {
    if (isReady && !user) {
      router.replace(
        `/login?next=${encodeURIComponent(pathname ?? "/dashboard")}`,
      );
    }
  }, [isReady, pathname, router, user]);

  if (!isReady || !user) {
    return (
      <div
        className="grid min-h-screen place-items-center bg-slate-50"
        role="status"
        aria-label="Checking your session"
      >
        <LoaderCircle className="size-7 animate-spin text-blue-700 motion-reduce:animate-none" />
      </div>
    );
  }

  if (user.role !== "admin") {
    return (
      <main className="grid min-h-screen place-items-center bg-slate-50 p-6">
        <div className="max-w-md text-center">
          <p className="text-sm font-semibold uppercase tracking-widest text-red-600">
            Access denied
          </p>
          <h1 className="mt-3 text-3xl font-bold text-slate-950">
            Admin workspace only
          </h1>
          <p className="mt-3 text-slate-600">
            Member accounts use the ProjectPulse mobile application.
          </p>
        </div>
      </main>
    );
  }

  return children;
}
