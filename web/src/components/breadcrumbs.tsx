"use client";

import { ChevronRight, Home } from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";

const labels: Record<string, string> = {
  dashboard: "Overview",
  clients: "Clients",
  projects: "Projects",
  tasks: "Tasks",
  members: "Team",
  reports: "Time reports",
  notifications: "Notifications",
  settings: "Settings",
  new: "New",
  edit: "Edit",
};

export function Breadcrumbs() {
  const pathname = usePathname();
  const safePathname = pathname ?? "/";
  const segments = safePathname.split("/").filter(Boolean);

  if (!segments.length || safePathname === "/dashboard") return null;

  return (
    <nav aria-label="Breadcrumb" className="px-4 pt-5 sm:px-6 lg:px-8">
      <ol className="flex flex-wrap items-center gap-1 text-sm text-slate-500">
        <li>
          <Link href="/dashboard" className="grid size-8 place-items-center rounded-lg hover:bg-slate-100 hover:text-slate-900" aria-label="Overview">
            <Home className="size-4" />
          </Link>
        </li>
        {segments.map((segment, index) => {
          const href = `/${segments.slice(0, index + 1).join("/")}`;
          const current = index === segments.length - 1;
          const label = labels[segment] ?? (/^\d+$/.test(segment) ? "Details" : decodeURIComponent(segment));
          return (
            <li key={href} className="flex items-center gap-1">
              <ChevronRight className="size-4 text-slate-300" aria-hidden="true" />
              {current ? (
                <span className="px-2 font-semibold text-slate-800" aria-current="page">{label}</span>
              ) : (
                <Link href={href} className="rounded-lg px-2 py-1.5 hover:bg-slate-100 hover:text-slate-900">{label}</Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
