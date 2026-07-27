"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { ArrowLeft, ArrowRight, Check, LoaderCircle, Sparkles, Trash2 } from "lucide-react";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { toast } from "sonner";

import { Field, PageHeader, ResourceError } from "@/components/resource-states";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { getApiErrorMessage } from "@/lib/api-client";
import { projectWizardSchema, type ProjectWizardValues } from "@/schemas/project";
import { clientsApi, projectsApi } from "@/services/resource-service";
import type { AISuggestion, Project } from "@/types/resources";

const stepNames = ["Project information", "AI suggestions", "Review tasks", "Confirmation"];
const initialValues: ProjectWizardValues = {
  client_id: 0,
  name: "",
  description: "",
  client_brief: "",
  start_date: new Date().toISOString().slice(0, 10),
  deadline: "",
};

export function ProjectWizardScreen() {
  const router = useRouter();
  const [step, setStep] = useState(0);
  const [values, setValues] = useState(initialValues);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [project, setProject] = useState<Project | null>(null);
  const [suggestions, setSuggestions] = useState<AISuggestion[]>([]);
  const clients = useQuery({ queryKey: ["clients", "wizard"], queryFn: () => clientsApi.list({ per_page: 100 }) });

  const generate = useMutation({
    mutationFn: async () => {
      const created = project ?? await projectsApi.create({ ...values, status: "draft" });
      setProject(created);
      return projectsApi.generateTasks(created.id, values.client_brief, 12);
    },
    onSuccess: (result) => {
      setSuggestions(result.tasks);
      setStep(2);
      toast.success(`${result.tasks.length} task suggestions are ready to review.`);
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });
  const save = useMutation({
    mutationFn: async () => {
      if (!project) throw new Error("Create the project before saving tasks.");
      await projectsApi.bulkTasks(project.id, suggestions.map((task) => ({
        title: task.title,
        description: task.description,
        category: task.category,
        priority: task.priority,
        estimated_hours: task.estimated_hours,
        source: "ai",
      })));
      return projectsApi.update(project.id, { status: "active" });
    },
    onSuccess: (saved) => {
      toast.success("Project and reviewed tasks are ready.");
      router.push(`/projects/${saved.id}`);
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  function validateInformation() {
    const result = projectWizardSchema.safeParse(values);
    if (result.success) {
      setErrors({});
      setStep(1);
      return;
    }
    setErrors(Object.fromEntries(result.error.issues.map((issue) => [String(issue.path[0]), issue.message])));
  }

  function setField<K extends keyof ProjectWizardValues>(key: K, value: ProjectWizardValues[K]) {
    setValues((current) => ({ ...current, [key]: value }));
    setErrors((current) => ({ ...current, [key]: "" }));
  }

  if (clients.isError) {
    return <ResourceError message={getApiErrorMessage(clients.error)} onRetry={() => clients.refetch()} />;
  }

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <PageHeader eyebrow="Guided planning" title="Create project" description="Turn a client brief into a reviewed delivery plan in four deliberate steps." />
      <ol className="grid gap-2 sm:grid-cols-4" aria-label="Project creation progress">
        {stepNames.map((name, index) => (
          <li key={name} className={`rounded-xl border px-3 py-3 text-sm font-semibold ${index === step ? "border-blue-600 bg-blue-50 text-blue-800" : index < step ? "border-emerald-200 bg-emerald-50 text-emerald-800" : "border-slate-200 bg-white text-slate-500"}`}>
            <span className="mr-2">{index < step ? <Check className="inline size-4" /> : index + 1}</span>{name}
          </li>
        ))}
      </ol>

      <Card>
        <CardContent className="p-6">
          {step === 0 ? (
            <div className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Client" error={errors.client_id}>
                  <Select value={values.client_id} onChange={(event) => setField("client_id", Number(event.target.value))}>
                    <option value={0}>Select client</option>
                    {clients.data?.data.map((client) => <option key={client.id} value={client.id}>{client.company}</option>)}
                  </Select>
                </Field>
                <Field label="Project name" error={errors.name}><Input value={values.name} onChange={(event) => setField("name", event.target.value)} /></Field>
              </div>
              <Field label="Description" error={errors.description}><Textarea value={values.description} onChange={(event) => setField("description", event.target.value)} /></Field>
              <Field label="Client brief" error={errors.client_brief}><Textarea className="min-h-32" value={values.client_brief} onChange={(event) => setField("client_brief", event.target.value)} /></Field>
              <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Start date" error={errors.start_date}><Input type="date" value={values.start_date} onChange={(event) => setField("start_date", event.target.value)} /></Field>
                <Field label="Deadline" error={errors.deadline}><Input type="date" value={values.deadline} onChange={(event) => setField("deadline", event.target.value)} /></Field>
              </div>
            </div>
          ) : null}

          {step === 1 ? (
            <div className="py-8 text-center">
              <Sparkles className="mx-auto size-10 text-blue-700" />
              <h2 className="mt-4 text-xl font-bold text-slate-950">Generate a first-pass task breakdown</h2>
              <p className="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">The project is created as a draft. AI suggestions remain editable and are not persisted until you confirm them.</p>
              <Button className="mt-6" onClick={() => generate.mutate()} disabled={generate.isPending}>
                {generate.isPending ? <LoaderCircle className="size-4 animate-spin" /> : <Sparkles className="size-4" />}Generate suggestions
              </Button>
            </div>
          ) : null}

          {step === 2 ? (
            <div className="space-y-3">
              {suggestions.map((task, index) => (
                <div key={task.temporary_id} className="rounded-xl border border-slate-200 p-4">
                  <div className="flex gap-3">
                    <Input value={task.title} onChange={(event) => setSuggestions((items) => items.map((item, i) => i === index ? { ...item, title: event.target.value } : item))} aria-label={`Task ${index + 1} title`} />
                    <Button variant="ghost" size="icon" className="text-red-600" onClick={() => setSuggestions((items) => items.filter((_, i) => i !== index))} aria-label={`Remove task ${index + 1}`}><Trash2 className="size-4" /></Button>
                  </div>
                  <Textarea className="mt-3 min-h-20" value={task.description ?? ""} onChange={(event) => setSuggestions((items) => items.map((item, i) => i === index ? { ...item, description: event.target.value } : item))} aria-label={`Task ${index + 1} description`} />
                </div>
              ))}
            </div>
          ) : null}

          {step === 3 ? (
            <div className="space-y-5">
              <div className="rounded-xl bg-slate-50 p-5">
                <p className="text-sm font-semibold text-blue-700">Project</p>
                <h2 className="mt-1 text-xl font-bold text-slate-950">{values.name}</h2>
                <p className="mt-2 text-sm text-slate-600">{values.start_date} to {values.deadline}</p>
              </div>
              <div>
                <h3 className="font-bold text-slate-950">{suggestions.length} reviewed tasks</h3>
                <ul className="mt-3 grid gap-2 sm:grid-cols-2">{suggestions.map((task) => <li key={task.temporary_id} className="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">{task.title}</li>)}</ul>
              </div>
            </div>
          ) : null}

          <div className="mt-6 flex justify-between border-t border-slate-100 pt-5">
            <Button variant="secondary" onClick={() => step === 0 ? router.push("/projects") : setStep((current) => current - 1)}>
              <ArrowLeft className="size-4" />{step === 0 ? "Cancel" : "Back"}
            </Button>
            {step === 0 ? <Button onClick={validateInformation}>Continue<ArrowRight className="size-4" /></Button> : null}
            {step === 2 ? <Button onClick={() => setStep(3)} disabled={!suggestions.length}>Review summary<ArrowRight className="size-4" /></Button> : null}
            {step === 3 ? <Button onClick={() => save.mutate()} disabled={save.isPending}>{save.isPending ? <LoaderCircle className="size-4 animate-spin" /> : <Check className="size-4" />}Confirm and save</Button> : null}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
