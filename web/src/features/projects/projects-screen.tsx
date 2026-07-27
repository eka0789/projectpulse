"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  CalendarDays,
  LoaderCircle,
  Pencil,
  Plus,
  Search,
  Sparkles,
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
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { getApiErrorMessage } from "@/lib/api-client";
import { clientsApi, projectsApi } from "@/services/resource-service";
import type {
  AISuggestion,
  Project,
  ProjectStatus,
} from "@/types/resources";

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
  ProjectStatus,
  "slate" | "blue" | "amber" | "green" | "red"
> = {
  draft: "slate",
  active: "blue",
  on_hold: "amber",
  completed: "green",
  cancelled: "red",
};

const emptyForm: ProjectForm = {
  client_id: 0,
  name: "",
  description: "",
  client_brief: "",
  start_date: "",
  deadline: "",
  status: "draft",
};

export function ProjectsScreen() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Project | null>(null);
  const [aiProject, setAiProject] = useState<Project | null>(null);
  const [suggestions, setSuggestions] = useState<AISuggestion[]>([]);
  const debouncedSearch = useDebouncedValue(search);
  const projects = useQuery({
    queryKey: ["projects", debouncedSearch],
    queryFn: () =>
      projectsApi.list({ search: debouncedSearch || undefined, per_page: 50 }),
  });
  const clients = useQuery({
    queryKey: ["clients", "project-options"],
    queryFn: () => clientsApi.list({ per_page: 100 }),
  });
  const form = useForm<ProjectFormInput, unknown, ProjectForm>({
    resolver: zodResolver(schema),
    defaultValues: emptyForm,
  });

  const saveProject = useMutation({
    mutationFn: (values: ProjectForm) => {
      const payload = {
        ...values,
        description: values.description || null,
        client_brief: values.client_brief || null,
        start_date: values.start_date || null,
      };
      return editing
        ? projectsApi.update(editing.id, payload)
        : projectsApi.create(payload);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["projects"] });
      toast.success(editing ? "Project updated successfully." : "Project created successfully.");
      setDialogOpen(false);
      setEditing(null);
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });
  const removeProject = useMutation({
    mutationFn: projectsApi.remove,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["projects"] });
      toast.success("Project removed successfully.");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });
  const generate = useMutation({
    mutationFn: async () => {
      if (!aiProject) throw new Error("Select a project.");
      return projectsApi.generateTasks(
        aiProject.id,
        aiProject.client_brief || aiProject.description || "",
        15,
      );
    },
    onSuccess: (data) => setSuggestions(data.tasks),
  });
  const saveSuggestions = useMutation({
    mutationFn: async () => {
      if (!aiProject) throw new Error("Select a project.");
      return projectsApi.bulkTasks(
        aiProject.id,
        suggestions.map((task) => ({
          title: task.title,
          description: [
            task.description,
            task.acceptance_criteria.length
              ? `Acceptance criteria:\n${task.acceptance_criteria.map((item) => `- ${item}`).join("\n")}`
              : null,
          ]
            .filter(Boolean)
            .join("\n\n"),
          category: task.category,
          priority: task.priority,
          estimated_hours: task.estimated_hours,
          source: "ai",
        })),
      );
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["tasks"] });
      await queryClient.invalidateQueries({ queryKey: ["projects"] });
      toast.success(`${suggestions.length} AI-reviewed tasks saved.`);
      setAiProject(null);
      setSuggestions([]);
    },
  });

  function openEdit(project: Project) {
    setEditing(project);
    form.reset({
      client_id: project.client_id,
      name: project.name,
      description: project.description ?? "",
      client_brief: project.client_brief ?? "",
      start_date: project.start_date ?? "",
      deadline: project.deadline,
      status: project.status,
    });
    setDialogOpen(true);
  }

  return (
    <div className="mx-auto max-w-[1480px] space-y-6">
      <PageHeader
        eyebrow="Delivery portfolio"
        title="Projects"
        description="Plan client engagements, manage deadlines, and review AI-assisted task breakdowns before saving."
        action={
          <Link
            href="/projects/new"
            className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
          >
            <Plus className="size-4" />New project
          </Link>
        }
      />
      <Card>
        <CardContent className="p-5">
          <label className="relative block max-w-md">
            <span className="sr-only">Search projects</span>
            <Search className="absolute left-3.5 top-3.5 size-5 text-slate-400" />
            <Input className="pl-11" placeholder="Search projects" value={search} onChange={(event) => setSearch(event.target.value)} />
          </label>
        </CardContent>
      </Card>

      {projects.isPending ? (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {Array.from({ length: 6 }).map((_, index) => <Skeleton key={index} className="h-56" />)}
        </div>
      ) : projects.isError ? (
        <ResourceError message={getApiErrorMessage(projects.error)} onRetry={() => projects.refetch()} />
      ) : projects.data.data.length ? (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {projects.data.data.map((project) => (
            <Card key={project.id} className="flex flex-col">
              <CardContent className="flex flex-1 flex-col p-5">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">
                      {project.client?.company ?? "Client"}
                    </p>
                    <h2 className="mt-1 text-lg font-bold text-slate-950">
                      <Link href={`/projects/${project.id}`} className="hover:underline">
                        {project.name}
                      </Link>
                    </h2>
                  </div>
                  <Badge tone={statusTone[project.status]}>{project.status.replace("_", " ")}</Badge>
                </div>
                <p className="mt-3 line-clamp-3 min-h-15 text-sm leading-5 text-slate-600">
                  {project.description || "No project description yet."}
                </p>
                <div className="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-sm text-slate-500">
                  <span className="flex items-center gap-2"><CalendarDays className="size-4" />{project.deadline}</span>
                  <span>{project.tasks_count ?? 0} tasks</span>
                </div>
                <div className="mt-4 flex gap-2">
                  <Button
                    variant="secondary"
                    className="flex-1"
                    disabled={!project.client_brief && !project.description}
                    onClick={() => {
                      setAiProject(project);
                      setSuggestions([]);
                      generate.reset();
                    }}
                  >
                    <Sparkles className="size-4" />AI breakdown
                  </Button>
                  <Button variant="ghost" size="icon" onClick={() => openEdit(project)} aria-label={`Edit ${project.name}`}>
                    <Pencil className="size-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="text-red-600 hover:bg-red-50"
                    onClick={() => {
                      if (window.confirm(`Delete ${project.name} and its tasks?`)) removeProject.mutate(project.id);
                    }}
                    aria-label={`Delete ${project.name}`}
                  >
                    <Trash2 className="size-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <Card><ResourceEmpty title="No projects found" description="Create your first client project to begin planning." /></Card>
      )}

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogTitle>{editing ? "Edit project" : "Create project"}</DialogTitle>
          <DialogDescription>Define scope, ownership, and delivery timing.</DialogDescription>
          <form className="mt-6 space-y-4" onSubmit={form.handleSubmit((values) => saveProject.mutate(values))}>
            <Field label="Client" error={form.formState.errors.client_id?.message}>
              <Select {...form.register("client_id")}>
                <option value={0}>Select client</option>
                {clients.data?.data.map((client) => <option key={client.id} value={client.id}>{client.company}</option>)}
              </Select>
            </Field>
            <Field label="Project name" error={form.formState.errors.name?.message}><Input {...form.register("name")} /></Field>
            <Field label="Description" error={form.formState.errors.description?.message}><Textarea {...form.register("description")} /></Field>
            <Field label="Client brief" error={form.formState.errors.client_brief?.message}><Textarea {...form.register("client_brief")} /></Field>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Start date" error={form.formState.errors.start_date?.message}><Input type="date" {...form.register("start_date")} /></Field>
              <Field label="Deadline" error={form.formState.errors.deadline?.message}><Input type="date" {...form.register("deadline")} /></Field>
            </div>
            <Field label="Status" error={form.formState.errors.status?.message}>
              <Select {...form.register("status")}>
                <option value="draft">Draft</option><option value="active">Active</option><option value="on_hold">On hold</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option>
              </Select>
            </Field>
            {saveProject.isError ? <p className="text-sm text-red-600">{getApiErrorMessage(saveProject.error)}</p> : null}
            <div className="flex justify-end gap-3"><Button variant="secondary" onClick={() => setDialogOpen(false)}>Cancel</Button><Button type="submit" disabled={saveProject.isPending}>{saveProject.isPending ? <LoaderCircle className="size-4 animate-spin" /> : null}Save project</Button></div>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(aiProject)} onOpenChange={(open) => !open && setAiProject(null)}>
        <DialogContent className="max-w-3xl">
          <DialogTitle>AI task review</DialogTitle>
          <DialogDescription>
            Generate suggestions for {aiProject?.name}, then edit or remove every item before saving.
          </DialogDescription>
          {!suggestions.length ? (
            <div className="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
              <Sparkles className="mx-auto size-8 text-blue-700" />
              <p className="mt-3 text-sm text-slate-600">The brief is sent to the configured backend provider. Suggestions are not saved automatically.</p>
              {generate.isError ? <p className="mt-3 text-sm text-red-600">{getApiErrorMessage(generate.error)}</p> : null}
              <Button className="mt-5" onClick={() => generate.mutate()} disabled={generate.isPending}>
                {generate.isPending ? <LoaderCircle className="size-4 animate-spin" /> : <Sparkles className="size-4" />}
                Generate suggestions
              </Button>
            </div>
          ) : (
            <div className="mt-6 space-y-4">
              {suggestions.map((task, index) => (
                <div key={task.temporary_id} className="rounded-2xl border border-slate-200 p-4">
                  <div className="flex gap-3">
                    <Input value={task.title} onChange={(event) => setSuggestions((items) => items.map((item, itemIndex) => itemIndex === index ? { ...item, title: event.target.value } : item))} aria-label={`Suggestion ${index + 1} title`} />
                    <Button variant="ghost" size="icon" className="text-red-600" onClick={() => setSuggestions((items) => items.filter((_, itemIndex) => itemIndex !== index))} aria-label={`Remove suggestion ${index + 1}`}><Trash2 className="size-4" /></Button>
                  </div>
                  <Textarea className="mt-3 min-h-20" value={task.description ?? ""} onChange={(event) => setSuggestions((items) => items.map((item, itemIndex) => itemIndex === index ? { ...item, description: event.target.value } : item))} aria-label={`Suggestion ${index + 1} description`} />
                  <div className="mt-3 grid gap-3 sm:grid-cols-3">
                    <Select value={task.category} onChange={(event) => setSuggestions((items) => items.map((item, itemIndex) => itemIndex === index ? { ...item, category: event.target.value as AISuggestion["category"] } : item))} aria-label="Category">
                      {["frontend","backend","design","qa","devops","management","other"].map((value) => <option key={value} value={value}>{value}</option>)}
                    </Select>
                    <Select value={task.priority} onChange={(event) => setSuggestions((items) => items.map((item, itemIndex) => itemIndex === index ? { ...item, priority: event.target.value as AISuggestion["priority"] } : item))} aria-label="Priority">
                      {["low","medium","high","urgent"].map((value) => <option key={value} value={value}>{value}</option>)}
                    </Select>
                    <Input type="number" min="0.5" max="500" step="0.5" value={task.estimated_hours} onChange={(event) => setSuggestions((items) => items.map((item, itemIndex) => itemIndex === index ? { ...item, estimated_hours: Number(event.target.value) } : item))} aria-label="Estimated hours" />
                  </div>
                </div>
              ))}
              {saveSuggestions.isError ? <p className="text-sm text-red-600">{getApiErrorMessage(saveSuggestions.error)}</p> : null}
              <div className="flex justify-end gap-3"><Button variant="secondary" onClick={() => setAiProject(null)}>Cancel</Button><Button onClick={() => saveSuggestions.mutate()} disabled={!suggestions.length || saveSuggestions.isPending}>{saveSuggestions.isPending ? <LoaderCircle className="size-4 animate-spin" /> : null}Save {suggestions.length} tasks</Button></div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
