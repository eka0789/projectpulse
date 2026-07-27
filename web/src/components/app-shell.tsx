"use client";

import {
  Bell,
  BriefcaseBusiness,
  ChevronDown,
  CircleGauge,
  Clock3,
  FolderKanban,
  LayoutList,
  LogOut,
  Menu,
  Settings,
  Users,
  X,
} from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useState } from "react";

import { Button } from "@/components/ui/button";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { useAuth } from "@/features/auth/auth-provider";
import { cn } from "@/lib/utils";
import { notificationsApi } from "@/services/resource-service";

const navigation = [
  { label: "Overview", href: "/dashboard", icon: CircleGauge },
  { label: "Projects", href: "/projects", icon: FolderKanban },
  { label: "Tasks", href: "/tasks", icon: LayoutList },
  { label: "Clients", href: "/clients", icon: BriefcaseBusiness },
  { label: "Team", href: "/members", icon: Users },
  { label: "Time reports", href: "/reports", icon: Clock3 },
  { label: "Settings", href: "/settings", icon: Settings },
];

function Brand() {
  return (
    <Link
      href="/dashboard"
      className="flex min-h-11 items-center gap-3 rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-cyan-300"
    >
      <span className="grid size-9 place-items-center rounded-xl bg-blue-700 text-white shadow-sm shadow-blue-900/20">
        <span className="size-3 rounded-full border-[3px] border-cyan-300" />
      </span>
      <span>
        <span className="block text-base font-bold tracking-tight text-white">
          ProjectPulse
        </span>
        <span className="block text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">
          Operations
        </span>
      </span>
    </Link>
  );
}

export function AppShell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const { user, logout } = useAuth();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [isLoggingOut, setIsLoggingOut] = useState(false);
  const unread = useQuery({
    queryKey: ["notifications", "unread"],
    queryFn: notificationsApi.unreadCount,
    refetchInterval: 60_000,
  });

  async function handleLogout() {
    setIsLoggingOut(true);
    await logout();
    router.replace("/login");
  }

  const sidebar = (
    <div className="flex h-full flex-col bg-slate-950 px-4 py-5 text-slate-300">
      <div className="px-2">
        <Brand />
      </div>
      <nav className="mt-8 flex-1 space-y-1" aria-label="Primary navigation">
        {navigation.map((item) => {
          const active =
            pathname === item.href ||
            (item.href !== "/dashboard" &&
              Boolean(pathname?.startsWith(item.href)));
          const Icon = item.icon;
          return (
            <Link
              key={item.href}
              href={item.href}
              onClick={() => setMobileOpen(false)}
              className={cn(
                "flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium outline-none transition-colors focus-visible:ring-2 focus-visible:ring-cyan-300",
                active
                  ? "bg-blue-700 text-white shadow-sm"
                  : "hover:bg-slate-900 hover:text-white",
              )}
            >
              <Icon className="size-5" aria-hidden="true" />
              {item.label}
            </Link>
          );
        })}
      </nav>
      <div className="border-t border-slate-800 pt-4">
        <div className="mb-3 flex items-center gap-3 rounded-xl bg-slate-900 p-3">
          <div className="grid size-9 shrink-0 place-items-center rounded-full bg-cyan-100 text-sm font-bold text-cyan-900">
            {user?.name
              .split(" ")
              .map((part) => part[0])
              .join("")
              .slice(0, 2)
              .toUpperCase()}
          </div>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-semibold text-white">
              {user?.name}
            </p>
            <p className="truncate text-xs text-slate-400">
              {user?.job_title ?? "Administrator"}
            </p>
          </div>
          <ChevronDown className="size-4 text-slate-500" aria-hidden="true" />
        </div>
        <Button
          variant="ghost"
          className="w-full justify-start text-slate-400 hover:bg-slate-900 hover:text-white"
          onClick={handleLogout}
          disabled={isLoggingOut}
        >
          <LogOut className="size-4" aria-hidden="true" />
          {isLoggingOut ? "Signing out…" : "Sign out"}
        </Button>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-slate-50 lg:grid lg:grid-cols-[248px_1fr]">
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-[248px] lg:block">
        {sidebar}
      </aside>

      {mobileOpen ? (
        <div className="fixed inset-0 z-50 lg:hidden">
          <button
            type="button"
            className="absolute inset-0 cursor-default bg-slate-950/60"
            onClick={() => setMobileOpen(false)}
            aria-label="Close navigation"
          />
          <aside className="relative h-full w-[min(86vw,288px)] shadow-2xl">
            {sidebar}
            <Button
              variant="ghost"
              size="icon"
              className="absolute right-2 top-3 text-slate-300 hover:bg-slate-800 hover:text-white"
              onClick={() => setMobileOpen(false)}
              aria-label="Close navigation"
            >
              <X className="size-5" />
            </Button>
          </aside>
        </div>
      ) : null}

      <div className="lg:col-start-2">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
          <div className="flex items-center gap-3">
            <Button
              variant="ghost"
              size="icon"
              className="lg:hidden"
              onClick={() => setMobileOpen(true)}
              aria-label="Open navigation"
            >
              <Menu className="size-5" />
            </Button>
            <div>
              <p className="text-sm font-semibold text-slate-950">
                Admin workspace
              </p>
              <p className="hidden text-xs text-slate-500 sm:block">
                Live operational overview
              </p>
            </div>
          </div>
          <Link
            href="/notifications"
            className="relative grid size-11 place-items-center rounded-xl text-slate-600 outline-none transition hover:bg-slate-100 hover:text-slate-950 focus-visible:ring-2 focus-visible:ring-blue-600"
            aria-label={`${unread.data ?? 0} unread notifications`}
          >
            <Bell className="size-5" />
            {unread.data ? (
              <span className="absolute right-1.5 top-1.5 min-w-4 rounded-full bg-red-600 px-1 text-center text-[10px] font-bold leading-4 text-white">
                {unread.data > 9 ? "9+" : unread.data}
              </span>
            ) : null}
          </Link>
        </header>
        <Breadcrumbs />
        <main className="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">{children}</main>
      </div>
    </div>
  );
}
