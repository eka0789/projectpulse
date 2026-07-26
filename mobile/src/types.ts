export type User = {
  id: number;
  name: string;
  email: string;
  role: "admin" | "member";
  job_title: string | null;
  avatar_url: string | null;
  is_active?: boolean;
};

export type Project = {
  id: number;
  name: string;
  status: string;
  client?: { id: number; company: string; name: string };
};

export type TaskStatus = "todo" | "in_progress" | "review" | "done";

export type TimeLog = {
  id: number;
  work_date: string;
  duration_minutes: number;
  note: string | null;
  user?: User;
};

export type ProgressNote = {
  id: number;
  note: string;
  status_snapshot: TaskStatus;
  created_at: string;
  user?: User;
};

export type Task = {
  id: number;
  project_id: number;
  title: string;
  description: string | null;
  category: string;
  assignee_id: number | null;
  priority: "low" | "medium" | "high" | "urgent";
  status: TaskStatus;
  estimated_hours: number | null;
  deadline: string | null;
  completed_at: string | null;
  total_logged_minutes?: number;
  project?: Project;
  assignee?: User;
  time_logs?: TimeLog[];
  progress_notes?: ProgressNote[];
};

export type Notification = {
  id: string;
  type: string;
  title: string;
  message: string;
  data: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string;
};

export type ApiResponse<T, TMeta = Record<string, unknown> | null> = {
  success: boolean;
  message: string;
  data: T;
  meta: TMeta;
};

export type PaginationMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type Session = {
  token: string;
  user: User;
};

