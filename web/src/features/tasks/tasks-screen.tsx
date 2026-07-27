"use client";

/* eslint-disable react-hooks/refs -- dnd-kit exposes callback refs and reactive drag state as its supported API. */

import {
  DndContext,
  PointerSensor,
  closestCorners,
  type DragEndEvent,
  useDraggable,
  useDroppable,
  useSensor,
  useSensors,
} from "@dnd-kit/core";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  CalendarClock,
  GripVertical,
  LoaderCircle,
  Pencil,
  Plus,
  Search,
  Trash2,
} from "lucide-react";
import Link from "next/link";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";

import {
  Field,
  PageHeader,
  ResourceError,
} from "@/components/resource-states";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
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
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { getApiErrorMessage } from "@/lib/api-client";
import {
  membersApi,
  projectsApi,
  tasksApi,
} from "@/services/resource-service";
import type { PaginatedResponse } from "@/types/api";
import type {
  Task,
  TaskCategory,
  TaskPriority,
  TaskStatus,
} from "@/types/resources";

const statuses: Array<{ value: TaskStatus; label: string }> = [
  { value: "todo", label: "To do" },
  { value: "in_progress", label: "In progress" },
  { value: "review", label: "Review" },
  { value: "done", label: "Done" },
];

const taskSchema = z.object({
  project_id: z.coerce.number().int().positive("Select a project."),
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

const emptyForm: TaskForm = {
  project_id: 0,
  title: "",
  description: "",
  category: "other",
  assignee_id: 0,
  priority: "medium",
  status: "todo",
  estimated_hours: 4,
  deadline: "",
};

const priorityTone: Record<TaskPriority, "slate" | "blue" | "amber" | "red"> = {
  low: "slate",
  medium: "blue",
  high: "amber",
  urgent: "red",
};

function TaskCard({
  task,
  onEdit,
  onDelete,
}: {
  task: Task;
  onEdit: () => void;
  onDelete: () => void;
}) {
  const draggable = useDraggable({ id: task.id, data: { task } });
  const style = draggable.transform
    ? { transform: `translate3d(${draggable.transform.x}px, ${draggable.transform.y}px, 0)` }
    : undefined;

  return (
    <article
      ref={draggable.setNodeRef}
      style={style}
      className={`rounded-xl border border-slate-200 bg-white p-4 shadow-sm ${
        draggable.isDragging ? "z-50 opacity-70 shadow-xl" : ""
      }`}
    >
      <div className="flex items-start gap-2">
        <button
          ref={draggable.setActivatorNodeRef}
          {...draggable.listeners}
          {...draggable.attributes}
          className="grid size-8 shrink-0 cursor-grab place-items-center rounded-lg text-slate-400 hover:bg-slate-100 active:cursor-grabbing"
          aria-label={`Drag ${task.title}`}
        >
          <GripVertical className="size-4" />
        </button>
        <div className="min-w-0 flex-1">
          <h3 className="font-semibold leading-5 text-slate-950">
            <Link href={`/tasks/${task.id}`} className="hover:underline">
              {task.title}
            </Link>
          </h3>
          <p className="mt-1 truncate text-xs text-slate-500">
            {task.project?.name ?? "Project"}
          </p>
        </div>
        <Badge tone={priorityTone[task.priority]}>{task.priority}</Badge>
      </div>
      <div className="mt-4 flex items-center justify-between text-xs text-slate-500">
        <span>{task.assignee?.name ?? "Unassigned"}</span>
        <span className="flex items-center gap-1">
          <CalendarClock className="size-3.5" />
          {task.deadline ?? "No date"}
        </span>
      </div>
      <div className="mt-3 flex justify-end gap-1 border-t border-slate-100 pt-2">
        <Button variant="ghost" size="icon" onClick={onEdit} aria-label={`Edit ${task.title}`}>
          <Pencil className="size-4" />
        </Button>
        <Button variant="ghost" size="icon" className="text-red-600 hover:bg-red-50" onClick={onDelete} aria-label={`Delete ${task.title}`}>
          <Trash2 className="size-4" />
        </Button>
      </div>
    </article>
  );
}

function KanbanColumn({
  status,
  label,
  tasks,
  onEdit,
  onDelete,
}: {
  status: TaskStatus;
  label: string;
  tasks: Task[];
  onEdit: (task: Task) => void;
  onDelete: (task: Task) => void;
}) {
  const droppable = useDroppable({ id: status });
  return (
    <section
      ref={droppable.setNodeRef}
      className={`min-h-[420px] rounded-2xl border p-3 transition ${
        droppable.isOver
          ? "border-blue-400 bg-blue-50"
          : "border-slate-200 bg-slate-100/70"
      }`}
      aria-label={`${label} tasks`}
    >
      <div className="mb-3 flex items-center justify-between px-1">
        <h2 className="text-sm font-bold text-slate-800">{label}</h2>
        <span className="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">
          {tasks.length}
        </span>
      </div>
      <div className="space-y-3">
        {tasks.map((task) => (
          <TaskCard
            key={task.id}
            task={task}
            onEdit={() => onEdit(task)}
            onDelete={() => onDelete(task)}
          />
        ))}
        {!tasks.length ? (
          <div className="rounded-xl border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500">
            Drop a task here
          </div>
        ) : null}
      </div>
    </section>
  );
}

export function TasksScreen({ projectId }: { projectId?: string } = {}) {
  const queryClient = useQueryClient();
  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 8 } }));
  const [search, setSearch] = useState("");
  const [projectFilter, setProjectFilter] = useState(projectId ? Number(projectId) : 0);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Task | null>(null);
  const debouncedSearch = useDebouncedValue(search);
  const queryKey = ["tasks", "kanban", debouncedSearch, projectFilter] as const;
  const tasks = useQuery({
    queryKey,
    queryFn: () =>
      tasksApi.list({
        search: debouncedSearch || undefined,
        project_id: projectFilter || undefined,
        per_page: 100,
      }),
  });
  const projects = useQuery({
    queryKey: ["projects", "task-options"],
    queryFn: () => projectsApi.list({ per_page: 100 }),
  });
  const members = useQuery({
    queryKey: ["members", "task-options"],
    queryFn: () => membersApi.list({ per_page: 100 }),
  });
  const form = useForm<TaskFormInput, unknown, TaskForm>({
    resolver: zodResolver(taskSchema),
    defaultValues: emptyForm,
  });

  const moveTask = useMutation({
    mutationFn: ({ id, status, updatedAt }: { id: number; status: TaskStatus; updatedAt: string }) =>
      tasksApi.updateStatus(id, status, updatedAt),
    onMutate: async ({ id, status }) => {
      await queryClient.cancelQueries({ queryKey });
      const previous = queryClient.getQueryData<PaginatedResponse<Task>>(queryKey);
      queryClient.setQueryData<PaginatedResponse<Task>>(queryKey, (current) =>
        current
          ? {
              ...current,
              data: current.data.map((task) =>
                task.id === id ? { ...task, status } : task,
              ),
            }
          : current,
      );
      return { previous };
    },
    onError: (error, _variables, context) => {
      if (context?.previous) queryClient.setQueryData(queryKey, context.previous);
      toast.error(getApiErrorMessage(error));
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: ["tasks"] }),
  });
  const saveTask = useMutation({
    mutationFn: (values: TaskForm) => {
      const payload = {
        ...values,
        assignee_id: values.assignee_id || null,
        description: values.description || null,
        deadline: values.deadline || null,
      };
      return editing
        ? tasksApi.update(editing.id, { ...payload, updated_at: editing.updated_at })
        : tasksApi.create(values.project_id, payload);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["tasks"] });
      await queryClient.invalidateQueries({ queryKey: ["dashboard"] });
      setDialogOpen(false);
      setEditing(null);
      toast.success("Task saved successfully.");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });
  const removeTask = useMutation({
    mutationFn: tasksApi.remove,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["tasks"] });
      toast.success("Task deleted successfully.");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  function handleDragEnd(event: DragEndEvent) {
    const task = event.active.data.current?.task as Task | undefined;
    const status = event.over?.id as TaskStatus | undefined;
    if (task && status && statuses.some((item) => item.value === status) && task.status !== status) {
      moveTask.mutate({ id: task.id, status, updatedAt: task.updated_at });
    }
  }

  function openCreate() {
    setEditing(null);
    form.reset({ ...emptyForm, project_id: projectFilter });
    setDialogOpen(true);
  }

  function openEdit(task: Task) {
    setEditing(task);
    form.reset({
      project_id: task.project_id,
      title: task.title,
      description: task.description ?? "",
      category: task.category,
      assignee_id: task.assignee_id ?? 0,
      priority: task.priority,
      status: task.status,
      estimated_hours: task.estimated_hours ?? 4,
      deadline: task.deadline ?? "",
    });
    setDialogOpen(true);
  }

  return (
    <div className="mx-auto max-w-[1680px] space-y-6">
      <PageHeader
        eyebrow="Execution board"
        title="Tasks"
        description="Coordinate assignments and drag work between stages. Updates are optimistic and roll back if the API rejects them."
        action={<Button onClick={openCreate}><Plus className="size-4" />New task</Button>}
      />
      <div className="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_260px]">
        <label className="relative">
          <span className="sr-only">Search tasks</span>
          <Search className="absolute left-3.5 top-3.5 size-5 text-slate-400" />
          <Input className="pl-11" placeholder="Search task title" value={search} onChange={(event) => setSearch(event.target.value)} />
        </label>
        <Select value={projectFilter} onChange={(event) => setProjectFilter(Number(event.target.value))} aria-label="Filter by project">
          <option value={0}>All projects</option>
          {projects.data?.data.map((project) => <option key={project.id} value={project.id}>{project.name}</option>)}
        </Select>
      </div>

      {tasks.isPending ? (
        <div className="grid gap-4 xl:grid-cols-4">{Array.from({ length: 4 }).map((_, index) => <Skeleton key={index} className="h-[480px]" />)}</div>
      ) : tasks.isError ? (
        <ResourceError message={getApiErrorMessage(tasks.error)} onRetry={() => tasks.refetch()} />
      ) : (
        <DndContext sensors={sensors} collisionDetection={closestCorners} onDragEnd={handleDragEnd}>
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {statuses.map((column) => (
              <KanbanColumn
                key={column.value}
                status={column.value}
                label={column.label}
                tasks={tasks.data.data.filter((task) => task.status === column.value)}
                onEdit={openEdit}
                onDelete={(task) => {
                  if (window.confirm(`Delete ${task.title}?`)) removeTask.mutate(task.id);
                }}
              />
            ))}
          </div>
        </DndContext>
      )}

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogTitle>{editing ? "Edit task" : "Create task"}</DialogTitle>
          <DialogDescription>Set scope, assignment, priority, and deadline.</DialogDescription>
          <form className="mt-6 space-y-4" onSubmit={form.handleSubmit((values) => saveTask.mutate(values))}>
            <Field label="Project" error={form.formState.errors.project_id?.message}>
              <Select {...form.register("project_id")} disabled={Boolean(editing)}>
                <option value={0}>Select project</option>
                {projects.data?.data.map((project) => <option key={project.id} value={project.id}>{project.name}</option>)}
              </Select>
            </Field>
            <Field label="Title" error={form.formState.errors.title?.message}><Input {...form.register("title")} /></Field>
            <Field label="Description" error={form.formState.errors.description?.message}><Textarea {...form.register("description")} /></Field>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Category" error={form.formState.errors.category?.message}>
                <Select {...form.register("category")}>{(["frontend","backend","design","qa","devops","management","other"] satisfies TaskCategory[]).map((value) => <option key={value} value={value}>{value}</option>)}</Select>
              </Field>
              <Field label="Assignee" error={form.formState.errors.assignee_id?.message}>
                <Select {...form.register("assignee_id")}><option value={0}>Unassigned</option>{members.data?.data.filter((member) => member.role === "member" && member.is_active).map((member) => <option key={member.id} value={member.id}>{member.name}</option>)}</Select>
              </Field>
              <Field label="Priority" error={form.formState.errors.priority?.message}>
                <Select {...form.register("priority")}>{(["low","medium","high","urgent"] satisfies TaskPriority[]).map((value) => <option key={value} value={value}>{value}</option>)}</Select>
              </Field>
              <Field label="Status" error={form.formState.errors.status?.message}>
                <Select {...form.register("status")}>{statuses.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</Select>
              </Field>
              <Field label="Estimated hours" error={form.formState.errors.estimated_hours?.message}><Input type="number" min="0.1" max="500" step="0.5" {...form.register("estimated_hours")} /></Field>
              <Field label="Deadline" error={form.formState.errors.deadline?.message}><Input type="date" {...form.register("deadline")} /></Field>
            </div>
            {saveTask.isError ? <p className="text-sm text-red-600">{getApiErrorMessage(saveTask.error)}</p> : null}
            <div className="flex justify-end gap-3"><Button variant="secondary" onClick={() => setDialogOpen(false)}>Cancel</Button><Button type="submit" disabled={saveTask.isPending}>{saveTask.isPending ? <LoaderCircle className="size-4 animate-spin" /> : null}Save task</Button></div>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
