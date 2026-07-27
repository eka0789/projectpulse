"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  ArrowLeft,
  CalendarClock,
  Clock,
  LoaderCircle,
  MessageSquare,
  Pencil,
  Trash2,
} from "lucide-react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";

import {
  Field,
  PageHeader,
  ResourceEmpty,
  ResourceError,
} from "@/components/resource-states";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { getApiErrorMessage } from "@/lib/api-client";
import { membersApi, tasksApi } from "@/services/resource-service";
import type { TaskCategory, TaskPriority, TaskStatus } from "@/types/resources";

const statuses: Array<{ value: TaskStatus; label: string }> = [
  { value: "todo", label: "To do" },
  { value: "in_progress", label: "In progress" },
  { value: "review", label: "Review" },
  { value: "done", label: "Done" },
];

const taskSchema = z.object({
  title: z.string().trim().min(2, "Enter a task title.").max(255),
  description: z.string().trim().max(10000),
  category: z.enum(["frontend", "backend", "design", "qa", "devops", "management", "other"]),
  assignee_id: z.coerce.number().int().nonnegative(),
  priority: z.enum(["low", "medium", "high", "urgent"]),
  status: z.enum(["todo", "in_progress", "review", "done"]),
  estimated_hours: z.coerce.number().min(0.1).max(500),
  deadline: z.string(),
});
type TaskFormInput = z.input<typeof taskSchema>;
type TaskForm = z.output<typeof taskSchema>;

const priorityTone: Record<TaskPriority, "slate" | "blue" | "amber" | "red"> = {
  low: "slate",
  medium: "blue",
  high: "amber",
  urgent: "red",
};

export function TaskDetailScreen() {
  const params = useParams<{ id: string }>();
  const taskId = Number(params?.id);
  const router = useRouter();
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);

  const task = useQuery({
    queryKey: ["tasks", taskId],
    queryFn: () => tasksApi.get(taskId),
    enabled: Number.isInteger(taskId),
  });
  const members = useQuery({
    queryKey: ["members", "task-options"],
    queryFn: () => membersApi.list({ per_page: 100 }),
  });

  const form = useForm<TaskFormInput, unknown, TaskForm>({
    resolver: zodResolver(taskSchema),
    defaultValues: {
      title: "",
      description: "",
      category: "other",
      assignee_id: 0,
      priority: "medium",
      status: "todo",
      estimated_hours: 4,
      deadline: "",
    },
  });

  const saveTask = useMutation({
    mutationFn: (values: TaskForm) =>
      tasksApi.update(taskId, {
        ...values,
        assignee_id: values.assignee_id || null,
        description: values.description || null,
        deadline: values.deadline || null,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["tasks"] });
      await queryClient.invalidateQueries({ queryKey: ["dashboard"] });
      toast.success("Task saved successfully.");
      setDialogOpen(false);
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  const removeTask = useMutation({
    mutationFn: () => tasksApi.remove(taskId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["tasks"] });
      toast.success("Task deleted successfully.");
      router.push("/tasks");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  function openEdit() {
    if (!task.data) return;
    form.reset({
      title: task.data.title,
      description: task.data.description ?? "",
      category: task.data.category,
      assignee_id: task.data.assignee_id ?? 0,
      priority: task.data.priority,
      status: task.data.status,
      estimated_hours: task.data.estimated_hours ?? 4,
      deadline: task.data.deadline ?? "",
    });
    setDialogOpen(true);
  }

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <Link
        href="/tasks"
        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-950"
      >
        <ArrowLeft className="size-4" />
        Back to tasks
      </Link>

      {task.isPending ? (
        <div className="space-y-4">
          <Skeleton className="h-28" />
          <Skeleton className="h-56" />
        </div>
      ) : task.isError ? (
        <ResourceError
          message={getApiErrorMessage(task.error)}
          onRetry={() => task.refetch()}
        />
      ) : (
        <>
          <PageHeader
            eyebrow={task.data.project?.name ?? "Project"}
            title={task.data.title}
            description={task.data.description || "No task description yet."}
            action={
              <div className="flex gap-2">
                <Button variant="secondary" onClick={openEdit}>
                  <Pencil className="size-4" />
                  Edit
                </Button>
                <Button
                  variant="ghost"
                  className="text-red-600 hover:bg-red-50 hover:text-red-700"
                  disabled={removeTask.isPending}
                  onClick={() => {
                    if (window.confirm(`Delete ${task.data.title}?`)) {
                      removeTask.mutate();
                    }
                  }}
                >
                  <Trash2 className="size-4" />
                  Delete
                </Button>
              </div>
            }
          />

          <Card>
            <CardContent className="grid gap-4 p-5 sm:grid-cols-2">
              <div className="text-sm text-slate-700">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  Status
                </p>
                <p className="mt-1 capitalize">{task.data.status.replace("_", " ")}</p>
              </div>
              <div className="text-sm text-slate-700">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  Priority
                </p>
                <Badge tone={priorityTone[task.data.priority]} className="mt-1">
                  {task.data.priority}
                </Badge>
              </div>
              <div className="text-sm text-slate-700">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  Assignee
                </p>
                <p className="mt-1">{task.data.assignee?.name ?? "Unassigned"}</p>
              </div>
              <div className="flex items-center gap-2 text-sm text-slate-700">
                <CalendarClock className="size-4 text-slate-400" />
                {task.data.deadline ?? "No deadline"}
              </div>
              <div className="text-sm text-slate-700">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  Estimated hours
                </p>
                <p className="mt-1">{task.data.estimated_hours ?? "—"}</p>
              </div>
              <div className="flex items-center gap-2 text-sm text-slate-700">
                <Clock className="size-4 text-slate-400" />
                {task.data.total_logged_minutes
                  ? `${(task.data.total_logged_minutes / 60).toFixed(1)}h logged`
                  : "No time logged"}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-5">
              <div className="mb-4 flex items-center gap-2">
                <MessageSquare className="size-5 text-blue-700" />
                <h2 className="font-bold text-slate-950">Comments</h2>
              </div>
              {task.data.comments?.length ? (
                <div className="space-y-3">
                  {task.data.comments.map((comment) => (
                    <div key={comment.id} className="rounded-xl border border-slate-200 p-3">
                      <p className="text-xs font-semibold text-slate-600">
                        {comment.user?.name ?? "Team member"}
                      </p>
                      <p className="mt-1 text-sm text-slate-700">{comment.body}</p>
                    </div>
                  ))}
                </div>
              ) : (
                <ResourceEmpty
                  title="No comments yet"
                  description="Comments left on this task will appear here."
                />
              )}
            </CardContent>
          </Card>
        </>
      )}

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogTitle>Edit task</DialogTitle>
          <DialogDescription>Set scope, assignment, priority, and deadline.</DialogDescription>
          <form
            className="mt-6 space-y-4"
            onSubmit={form.handleSubmit((values) => saveTask.mutate(values))}
          >
            <Field label="Title" error={form.formState.errors.title?.message}>
              <Input {...form.register("title")} />
            </Field>
            <Field label="Description" error={form.formState.errors.description?.message}>
              <Textarea {...form.register("description")} />
            </Field>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Category" error={form.formState.errors.category?.message}>
                <Select {...form.register("category")}>
                  {(
                    [
                      "frontend",
                      "backend",
                      "design",
                      "qa",
                      "devops",
                      "management",
                      "other",
                    ] satisfies TaskCategory[]
                  ).map((value) => (
                    <option key={value} value={value}>
                      {value}
                    </option>
                  ))}
                </Select>
              </Field>
              <Field label="Assignee" error={form.formState.errors.assignee_id?.message}>
                <Select {...form.register("assignee_id")}>
                  <option value={0}>Unassigned</option>
                  {members.data?.data
                    .filter((member) => member.role === "member" && member.is_active)
                    .map((member) => (
                      <option key={member.id} value={member.id}>
                        {member.name}
                      </option>
                    ))}
                </Select>
              </Field>
              <Field label="Priority" error={form.formState.errors.priority?.message}>
                <Select {...form.register("priority")}>
                  {(["low", "medium", "high", "urgent"] satisfies TaskPriority[]).map(
                    (value) => (
                      <option key={value} value={value}>
                        {value}
                      </option>
                    ),
                  )}
                </Select>
              </Field>
              <Field label="Status" error={form.formState.errors.status?.message}>
                <Select {...form.register("status")}>
                  {statuses.map((item) => (
                    <option key={item.value} value={item.value}>
                      {item.label}
                    </option>
                  ))}
                </Select>
              </Field>
              <Field
                label="Estimated hours"
                error={form.formState.errors.estimated_hours?.message}
              >
                <Input type="number" min="0.1" max="500" step="0.5" {...form.register("estimated_hours")} />
              </Field>
              <Field label="Deadline" error={form.formState.errors.deadline?.message}>
                <Input type="date" {...form.register("deadline")} />
              </Field>
            </div>
            <div className="flex justify-end gap-3 pt-2">
              <Button variant="secondary" onClick={() => setDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={saveTask.isPending}>
                {saveTask.isPending ? (
                  <LoaderCircle className="size-4 animate-spin" />
                ) : null}
                Save changes
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
