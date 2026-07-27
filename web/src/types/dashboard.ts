import type { ProjectStatus, TaskStatus } from "@/types/resources";

export type MemberWorkload = {
  user_id: number;
  name: string;
  avatar_url: string | null;
  job_title: string | null;
  active_tasks: number;
  estimated_hours: number;
  logged_hours: number;
  overdue_tasks: number;
};

export type RecentProject = {
  id: number;
  name: string;
  client_name: string;
  company: string;
  status: ProjectStatus;
  deadline: string | null;
  task_count: number;
};

export type DashboardSummary = {
  active_projects: number;
  completed_projects: number;
  overdue_tasks: number;
  tasks_due_today: number;
  tasks_due_this_week: number;
  task_status_distribution: Record<TaskStatus, number>;
  member_workloads: MemberWorkload[];
  recent_projects: RecentProject[];
};

