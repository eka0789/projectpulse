"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  ArrowLeft,
  CalendarDays,
  ClipboardList,
  LoaderCircle,
  Pencil,
  Trash2,
} from "lucide-react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { useEffect, useRef, useState } from "react";
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
import { clientsApi, projectsApi } from "@/services/resource-service";
import type { TaskPriority, TaskStatus } from "@/types/resources";

const schema = z.object({
  client_id: z.coerce.number().int().positive("Select a client."),
  name: z.string().trim().min(2, "Enter a project name.").max(255),
  description: z.string().trim().max(10000),
  client_brief: z.string().trim().max(10000),
  start_date: z.string(),
  deadline: z.string().min(1, "Select a deadline."),
  status: z.enum(["draft", "active", "on_hold", "completed", "cancelled"]),
});
type ProjectFormInput = z.input<typeof schema>;
type ProjectForm = z.output<typeof schema>;

const statusTone: Record<
  ProjectForm["status"],
  "slate" | "blue" | "amber" | "green" | "red"
> = {
  draft: "slate",
  active: "blue",
  on_hold: "amber",
  completed: "green",
  cancelled: "red",
};

const priorityTone: Record<TaskPriority, "slate" | "blue" | "amber" | "red"> = {
  low: "slate",
  medium: "blue",
  high: "amber",
  urgent: "red",
};

const taskStatusLabel: Record<TaskStatus, string> = {
  todo: "To do",
  in_progress: "In progress",
  review: "Review",
  done: "Done",
};

export function ProjectDetailScreen({ initialEdit = false }: { initialEdit?: boolean }) {
  const params = useParams<{ id: string }>();
  const projectId = Number(params?.id);
  const router = useRouter();
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);
  const openedInitial = useRef(false);

  const project = useQuery({
    queryKey: ["projects", projectId],
    queryFn: () => projectsApi.get(projectId),
    enabled: Number.isInteger(projectId),
  });
  const clients = useQuery({
    queryKey: ["clients", "project-options"],
    queryFn: () => clientsApi.list({ per_page: 100 }),
  });

  const form = useForm<ProjectFormInput, unknown, ProjectForm>({
    resolver: zodResolver(schema),
    defaultValues: {
      client_id: 0,
      name: "",
      description: "",
      client_brief: "",
      start_date: "",
      deadline: "",
      status: "draft",
    },
  });

  const saveProject = useMutation({
    mutationFn: (values: ProjectForm) =>
      projectsApi.update(projectId, {
        ...values,
        description: values.description || null,
        client_brief: values.client_brief || null,
        start_date: values.start_date || null,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["projects"] });
      toast.success("Project updated successfully.");
      setDialogOpen(false);
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  const removeProject = useMutation({
    mutationFn: () => projectsApi.remove(projectId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["projects"] });
      toast.success("Project removed successfully.");
      router.push("/projects");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  function openEdit() {
    if (!project.data) return;
    form.reset({
      client_id: project.data.client_id,
      name: project.data.name,
      description: project.data.description ?? "",
      client_brief: project.data.client_brief ?? "",
      start_date: project.data.start_date ?? "",
      deadline: project.data.deadline,
      status: project.data.status,
    });
    setDialogOpen(true);
  }

  useEffect(() => {
    if (initialEdit && project.data && !openedInitial.current) {
      openedInitial.current = true;
      openEdit();
    }
    // openEdit intentionally runs once when the requested resource arrives.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialEdit, project.data]);

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <Link
        href="/projects"
        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-950"
      >
        <ArrowLeft className="size-4" />
        Back to projects
      </Link>

      {!Number.isInteger(projectId) ? (
        <ResourceError message="Invalid project ID." />
      ) : project.isPending ? (
        <div className="space-y-4">
          <Skeleton className="h-28" />
          <Skeleton className="h-56" />
        </div>
      ) : project.isError ? (
        <ResourceError
          message={getApiErrorMessage(project.error)}
          onRetry={() => project.refetch()}
        />
      ) : (
        <>
          <PageHeader
            eyebrow={project.data.client?.company ?? "Client"}
            title={project.data.name}
            description={project.data.description || "No project description yet."}
            action={
              <div className="flex gap-2">
                <Button variant="secondary" onClick={openEdit}>
                  <Pencil className="size-4" />
                  Edit
                </Button>
                <Button
                  variant="ghost"
                  className="text-red-600 hover:bg-red-50 hover:text-red-700"
                  disabled={removeProject.isPending}
                  onClick={() => {
                    if (window.confirm(`Delete ${project.data.name} and its tasks?`)) {
                      removeProject.mutate();
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
              <div className="flex items-center gap-3 text-sm text-slate-700">
                <CalendarDays className="size-4 text-slate-400" />
                Deadline {project.data.deadline}
              </div>
              <div className="flex items-center gap-3 text-sm text-slate-700">
                <Badge tone={statusTone[project.data.status]}>
                  {project.data.status.replaceAll("_", " ")}
                </Badge>
              </div>
              {project.data.client_brief ? (
                <div className="sm:col-span-2">
                  <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Client brief
                  </p>
                  <p className="mt-1 text-sm leading-6 text-slate-600">
                    {project.data.client_brief}
                  </p>
                </div>
              ) : null}
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-5">
              <div className="mb-4 flex items-center gap-2">
                <ClipboardList className="size-5 text-blue-700" />
                <h2 className="font-bold text-slate-950">Tasks</h2>
              </div>
              {project.data.tasks?.length ? (
                <div className="space-y-2">
                  {project.data.tasks.map((task) => (
                    <Link
                      key={task.id}
                      href={`/tasks/${task.id}`}
                      className="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50/40"
                    >
                      <div>
                        <p className="font-medium text-slate-900">{task.title}</p>
                        <p className="text-xs text-slate-500">
                          {task.assignee?.name ?? "Unassigned"} ·{" "}
                          {taskStatusLabel[task.status]}
                        </p>
                      </div>
                      <Badge tone={priorityTone[task.priority]}>{task.priority}</Badge>
                    </Link>
                  ))}
                </div>
              ) : (
                <ResourceEmpty
                  title="No tasks yet"
                  description="Tasks created for this project will appear here."
                />
              )}
            </CardContent>
          </Card>
        </>
      )}

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogTitle>Edit project</DialogTitle>
          <DialogDescription>Define scope, ownership, and delivery timing.</DialogDescription>
          <form
            className="mt-6 space-y-4"
            onSubmit={form.handleSubmit((values) => saveProject.mutate(values))}
          >
            <Field label="Client" error={form.formState.errors.client_id?.message}>
              <Select {...form.register("client_id")}>
                <option value={0}>Select client</option>
                {clients.data?.data.map((client) => (
                  <option key={client.id} value={client.id}>
                    {client.company}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label="Project name" error={form.formState.errors.name?.message}>
              <Input {...form.register("name")} />
            </Field>
            <Field label="Description" error={form.formState.errors.description?.message}>
              <Textarea {...form.register("description")} />
            </Field>
            <Field label="Client brief" error={form.formState.errors.client_brief?.message}>
              <Textarea {...form.register("client_brief")} />
            </Field>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Start date" error={form.formState.errors.start_date?.message}>
                <Input type="date" {...form.register("start_date")} />
              </Field>
              <Field label="Deadline" error={form.formState.errors.deadline?.message}>
                <Input type="date" {...form.register("deadline")} />
              </Field>
            </div>
            <Field label="Status" error={form.formState.errors.status?.message}>
              <Select {...form.register("status")}>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="on_hold">On hold</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </Select>
            </Field>
            <div className="flex justify-end gap-3 pt-2">
              <Button variant="secondary" onClick={() => setDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={saveProject.isPending}>
                {saveProject.isPending ? (
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
