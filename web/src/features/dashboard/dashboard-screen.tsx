"use client";

import { useQuery } from "@tanstack/react-query";
import {
  AlertTriangle,
  CalendarClock,
  CheckCircle2,
  FolderKanban,
  RefreshCw,
  TrendingUp,
} from "lucide-react";
import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { useAuth } from "@/features/auth/auth-provider";
import { getApiErrorMessage } from "@/lib/api-client";
import { getDashboardSummary } from "@/services/dashboard-service";
import type { RecentProject } from "@/types/dashboard";

const projectTone: Record<
  RecentProject["status"],
  "slate" | "blue" | "amber" | "green" | "red"
> = {
  draft: "slate",
  active: "blue",
  on_hold: "amber",
  completed: "green",
  cancelled: "red",
};

function formatDate(value: string | null) {
  if (!value) return "No deadline";
  return new Intl.DateTimeFormat("en", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(new Date(`${value}T00:00:00`));
}

function DashboardSkeleton() {
  return (
    <div className="space-y-6" aria-label="Loading dashboard" role="status">
      <div>
        <Skeleton className="h-8 w-64" />
        <Skeleton className="mt-3 h-4 w-80 max-w-full" />
      </div>
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {Array.from({ length: 4 }).map((_, index) => (
          <Skeleton key={index} className="h-32" />
        ))}
      </div>
      <div className="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
        <Skeleton className="h-80" />
        <Skeleton className="h-80" />
      </div>
    </div>
  );
}

export function DashboardScreen() {
  const { user } = useAuth();
  const summary = useQuery({
    queryKey: ["dashboard", "summary"],
    queryFn: getDashboardSummary,
  });

  if (summary.isPending) return <DashboardSkeleton />;

  if (summary.isError) {
    return (
      <Card className="mx-auto mt-12 max-w-xl border-red-200">
        <CardContent className="p-8 text-center">
          <div className="mx-auto grid size-12 place-items-center rounded-full bg-red-50 text-red-700">
            <AlertTriangle className="size-6" aria-hidden="true" />
          </div>
          <h1 className="mt-4 text-xl font-bold text-slate-950">
            Dashboard data is unavailable
          </h1>
          <p className="mt-2 text-sm leading-6 text-slate-600">
            {getApiErrorMessage(summary.error)}
          </p>
          <Button className="mt-5" onClick={() => summary.refetch()}>
            <RefreshCw className="size-4" aria-hidden="true" />
            Try again
          </Button>
        </CardContent>
      </Card>
    );
  }

  const data = summary.data;
  const taskChart = [
    { name: "To do", tasks: data.task_status_distribution.todo },
    { name: "In progress", tasks: data.task_status_distribution.in_progress },
    { name: "Review", tasks: data.task_status_distribution.review },
    { name: "Done", tasks: data.task_status_distribution.done },
  ];
  const metrics = [
    {
      label: "Active projects",
      value: data.active_projects,
      detail: `${data.completed_projects} completed`,
      icon: FolderKanban,
      tone: "bg-blue-50 text-blue-700",
    },
    {
      label: "Tasks due today",
      value: data.tasks_due_today,
      detail: `${data.tasks_due_this_week} due this week`,
      icon: CalendarClock,
      tone: "bg-cyan-50 text-cyan-800",
    },
    {
      label: "Overdue tasks",
      value: data.overdue_tasks,
      detail: data.overdue_tasks ? "Needs attention" : "Everything on track",
      icon: AlertTriangle,
      tone: "bg-red-50 text-red-700",
    },
    {
      label: "Completed tasks",
      value: data.task_status_distribution.done,
      detail: "Across all projects",
      icon: CheckCircle2,
      tone: "bg-emerald-50 text-emerald-700",
    },
  ];

  return (
    <div className="mx-auto max-w-[1480px] space-y-6">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
          <p className="text-sm font-semibold text-blue-700">
            Delivery overview
          </p>
          <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
            Welcome back, {user?.name.split(" ")[0]}
          </h1>
          <p className="mt-2 max-w-2xl text-sm text-slate-600">
            Here is the latest delivery health across your projects and team.
          </p>
        </div>
        <div className="flex items-center gap-2 text-sm text-slate-500">
          <span className="relative flex size-2">
            <span className="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75 motion-reduce:animate-none" />
            <span className="relative inline-flex size-2 rounded-full bg-emerald-500" />
          </span>
          Live API data
        </div>
      </div>

      <section
        className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        aria-label="Key metrics"
      >
        {metrics.map((metric) => {
          const Icon = metric.icon;
          return (
            <Card key={metric.label}>
              <CardContent className="flex items-start justify-between p-5">
                <div>
                  <p className="text-sm font-medium text-slate-500">
                    {metric.label}
                  </p>
                  <p className="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                    {metric.value}
                  </p>
                  <p className="mt-2 text-xs font-medium text-slate-500">
                    {metric.detail}
                  </p>
                </div>
                <div
                  className={`grid size-10 place-items-center rounded-xl ${metric.tone}`}
                >
                  <Icon className="size-5" aria-hidden="true" />
                </div>
              </CardContent>
            </Card>
          );
        })}
      </section>

      <section className="grid gap-6 xl:grid-cols-[1.35fr_1fr]">
        <Card>
          <CardHeader className="flex-row items-start justify-between">
            <div>
              <h2 className="text-base font-bold text-slate-950">Task flow</h2>
              <p className="mt-1 text-sm text-slate-500">
                Current workload by delivery stage
              </p>
            </div>
            <TrendingUp className="size-5 text-blue-700" aria-hidden="true" />
          </CardHeader>
          <CardContent>
            <div className="h-64 min-h-64 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart
                  data={taskChart}
                  margin={{ top: 8, right: 0, bottom: 0, left: -24 }}
                >
                  <CartesianGrid vertical={false} stroke="#e2e8f0" />
                  <XAxis
                    dataKey="name"
                    axisLine={false}
                    tickLine={false}
                    tick={{ fill: "#64748b", fontSize: 12 }}
                  />
                  <YAxis
                    allowDecimals={false}
                    axisLine={false}
                    tickLine={false}
                    tick={{ fill: "#64748b", fontSize: 12 }}
                  />
                  <Tooltip
                    cursor={{ fill: "#f8fafc" }}
                    contentStyle={{
                      borderRadius: 12,
                      border: "1px solid #e2e8f0",
                      boxShadow: "0 8px 24px rgba(15, 23, 42, .08)",
                    }}
                  />
                  <Bar
                    dataKey="tasks"
                    fill="#1d4ed8"
                    radius={[6, 6, 0, 0]}
                    maxBarSize={52}
                  />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <h2 className="text-base font-bold text-slate-950">
              Team workload
            </h2>
            <p className="mt-1 text-sm text-slate-500">
              Active assignments and logged effort
            </p>
          </CardHeader>
          <CardContent className="space-y-4">
            {data.member_workloads.length ? (
              data.member_workloads.slice(0, 5).map((member) => (
                <div
                  key={member.user_id}
                  className="flex items-center gap-3 border-b border-slate-100 pb-4 last:border-0 last:pb-0"
                >
                  <div className="grid size-10 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                    {member.name
                      .split(" ")
                      .map((part) => part[0])
                      .join("")
                      .slice(0, 2)
                      .toUpperCase()}
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-slate-950">
                      {member.name}
                    </p>
                    <p className="truncate text-xs text-slate-500">
                      {member.active_tasks} active · {member.logged_hours}h logged
                    </p>
                  </div>
                  {member.overdue_tasks ? (
                    <Badge tone="red">{member.overdue_tasks} overdue</Badge>
                  ) : (
                    <Badge tone="green">On track</Badge>
                  )}
                </div>
              ))
            ) : (
              <div className="py-10 text-center">
                <p className="font-semibold text-slate-800">
                  No active members
                </p>
                <p className="mt-1 text-sm text-slate-500">
                  Team workload will appear after members are added.
                </p>
              </div>
            )}
          </CardContent>
        </Card>
      </section>

      <Card>
        <CardHeader>
          <h2 className="text-base font-bold text-slate-950">
            Recent projects
          </h2>
          <p className="mt-1 text-sm text-slate-500">
            Newly created work and upcoming deadlines
          </p>
        </CardHeader>
        <CardContent className="overflow-x-auto p-0 pt-4">
          {data.recent_projects.length ? (
            <table className="w-full min-w-[720px] text-left text-sm">
              <thead>
                <tr className="border-y border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                  <th className="px-5 py-3">Project</th>
                  <th className="px-5 py-3">Client</th>
                  <th className="px-5 py-3">Status</th>
                  <th className="px-5 py-3">Tasks</th>
                  <th className="px-5 py-3">Deadline</th>
                </tr>
              </thead>
              <tbody>
                {data.recent_projects.map((project) => (
                  <tr
                    key={project.id}
                    className="border-b border-slate-100 last:border-0 hover:bg-slate-50/70"
                  >
                    <td className="px-5 py-4 font-semibold text-slate-950">
                      {project.name}
                    </td>
                    <td className="px-5 py-4">
                      <p className="font-medium text-slate-700">
                        {project.client_name}
                      </p>
                      <p className="text-xs text-slate-500">{project.company}</p>
                    </td>
                    <td className="px-5 py-4">
                      <Badge tone={projectTone[project.status]}>
                        {project.status.replaceAll("_", " ")}
                      </Badge>
                    </td>
                    <td className="px-5 py-4 text-slate-600">
                      {project.task_count}
                    </td>
                    <td className="px-5 py-4 text-slate-600">
                      {formatDate(project.deadline)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <div className="px-5 py-12 text-center">
              <FolderKanban className="mx-auto size-8 text-slate-300" />
              <p className="mt-3 font-semibold text-slate-800">
                No projects yet
              </p>
              <p className="mt-1 text-sm text-slate-500">
                New projects will appear here after they are created.
              </p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

