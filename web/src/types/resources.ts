import type { AuthUser } from "@/types/auth";

export type Client = {
  id: number;
  name: string;
  company: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  notes: string | null;
  projects_count?: number;
  projects?: Project[];
  created_at: string;
  updated_at: string;
};

export type ProjectStatus =
  | "draft"
  | "active"
  | "on_hold"
  | "completed"
  | "cancelled";

export type Project = {
  id: number;
  client_id: number;
  name: string;
  description: string | null;
  client_brief: string | null;
  start_date: string | null;
  deadline: string;
  status: ProjectStatus;
  client?: Client;
  creator?: AuthUser;
  tasks_count?: number;
  tasks?: Task[];
  created_at: string;
  updated_at: string;
};

export type TaskStatus = "todo" | "in_progress" | "review" | "done";
export type TaskPriority = "low" | "medium" | "high" | "urgent";
export type TaskCategory =
  | "frontend"
  | "backend"
  | "design"
  | "qa"
  | "devops"
  | "management"
  | "other";

export type Task = {
  id: number;
  project_id: number;
  title: string;
  description: string | null;
  category: TaskCategory;
  assignee_id: number | null;
  priority: TaskPriority;
  status: TaskStatus;
  estimated_hours: number | null;
  start_date: string | null;
  deadline: string | null;
  completed_at: string | null;
  source: "manual" | "ai";
  total_logged_minutes?: number;
  project?: Project;
  assignee?: AuthUser;
  creator?: AuthUser;
  time_logs?: TimeLog[];
  progress_notes?: ProgressNote[];
  comments?: TaskComment[];
  created_at: string;
  updated_at: string;
};

export type TimeLog = {
  id: number;
  task_id: number;
  user_id: number;
  work_date: string;
  duration_minutes: number;
  note: string | null;
  user?: AuthUser;
  created_at: string;
  updated_at: string;
};

export type ProgressNote = {
  id: number;
  task_id: number;
  user_id: number;
  note: string;
  status_snapshot: TaskStatus;
  user?: AuthUser;
  created_at: string;
  updated_at: string;
};

export type TaskComment = {
  id: number;
  task_id: number;
  user_id: number;
  body: string;
  edited_at: string | null;
  user?: AuthUser;
  created_at: string;
  updated_at: string;
};

export type Member = AuthUser & {
  is_active: boolean;
  created_at: string;
  updated_at: string;
};

export type AppNotification = {
  id: string;
  type: string;
  title: string;
  message: string;
  data: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string;
};

export type TimeLogReportItem = {
  id: number;
  work_date: string;
  duration_minutes: number;
  note: string | null;
  user: Member;
  task: Task;
};

export type TimeLogReport = {
  total_hours: number;
  total_entries: number;
  time_logs: TimeLogReportItem[];
};

export type AISuggestion = {
  temporary_id: string;
  title: string;
  description: string | null;
  category: TaskCategory;
  estimated_hours: number;
  priority: TaskPriority;
  acceptance_criteria: string[];
};

export type AIGeneration = {
  generation_id: number | null;
  provider: string;
  model: string;
  tasks: AISuggestion[];
  source: "ai" | "demo_fallback";
};

