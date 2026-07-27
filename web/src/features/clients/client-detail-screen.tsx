"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  ArrowLeft,
  Building2,
  FolderKanban,
  LoaderCircle,
  Mail,
  MapPin,
  Pencil,
  Phone,
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
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { getApiErrorMessage } from "@/lib/api-client";
import { clientsApi } from "@/services/resource-service";
import type { ProjectStatus } from "@/types/resources";

const clientSchema = z.object({
  name: z.string().trim().min(2, "Enter the client contact name.").max(255),
  company: z.string().trim().min(2, "Enter the company name.").max(255),
  email: z.union([z.string().trim().email("Enter a valid email."), z.literal("")]),
  phone: z.string().trim().max(50),
  address: z.string().trim().max(2000),
  notes: z.string().trim().max(5000),
});
type ClientForm = z.infer<typeof clientSchema>;

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

export function ClientDetailScreen({ initialEdit = false }: { initialEdit?: boolean }) {
  const params = useParams<{ id: string }>();
  const clientId = Number(params?.id);
  const router = useRouter();
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);
  const openedInitial = useRef(false);

  const client = useQuery({
    queryKey: ["clients", clientId],
    queryFn: () => clientsApi.get(clientId),
    enabled: Number.isInteger(clientId),
  });

  const form = useForm<ClientForm>({
    resolver: zodResolver(clientSchema),
    defaultValues: {
      name: "",
      company: "",
      email: "",
      phone: "",
      address: "",
      notes: "",
    },
  });

  const saveClient = useMutation({
    mutationFn: (values: ClientForm) => {
      const payload = Object.fromEntries(
        Object.entries(values).map(([key, value]) => [key, value || null]),
      );
      return clientsApi.update(clientId, payload);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clients"] });
      toast.success("Client updated successfully.");
      setDialogOpen(false);
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  const removeClient = useMutation({
    mutationFn: () => clientsApi.remove(clientId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clients"] });
      toast.success("Client removed successfully.");
      router.push("/clients");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  function openEdit() {
    if (!client.data) return;
    form.reset({
      name: client.data.name,
      company: client.data.company,
      email: client.data.email ?? "",
      phone: client.data.phone ?? "",
      address: client.data.address ?? "",
      notes: client.data.notes ?? "",
    });
    setDialogOpen(true);
  }

  useEffect(() => {
    if (initialEdit && client.data && !openedInitial.current) {
      openedInitial.current = true;
      openEdit();
    }
    // openEdit intentionally runs once when the requested resource arrives.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialEdit, client.data]);

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <Link
        href="/clients"
        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-950"
      >
        <ArrowLeft className="size-4" />
        Back to clients
      </Link>

      {client.isPending ? (
        <div className="space-y-4">
          <Skeleton className="h-28" />
          <Skeleton className="h-56" />
        </div>
      ) : client.isError ? (
        <ResourceError
          message={getApiErrorMessage(client.error)}
          onRetry={() => client.refetch()}
        />
      ) : (
        <>
          <PageHeader
            eyebrow="Client profile"
            title={client.data.company}
            description={client.data.name}
            action={
              <div className="flex gap-2">
                <Button variant="secondary" onClick={openEdit}>
                  <Pencil className="size-4" />
                  Edit
                </Button>
                <Button
                  variant="ghost"
                  className="text-red-600 hover:bg-red-50 hover:text-red-700"
                  disabled={removeClient.isPending}
                  onClick={() => {
                    if (
                      window.confirm(
                        `Delete ${client.data.company}? This is blocked while projects exist.`,
                      )
                    ) {
                      removeClient.mutate();
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
                <Mail className="size-4 text-slate-400" />
                {client.data.email ?? "No email on file"}
              </div>
              <div className="flex items-center gap-3 text-sm text-slate-700">
                <Phone className="size-4 text-slate-400" />
                {client.data.phone ?? "No phone on file"}
              </div>
              <div className="flex items-center gap-3 text-sm text-slate-700 sm:col-span-2">
                <MapPin className="size-4 text-slate-400" />
                {client.data.address ?? "No address on file"}
              </div>
              {client.data.notes ? (
                <div className="sm:col-span-2">
                  <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Notes
                  </p>
                  <p className="mt-1 text-sm leading-6 text-slate-600">
                    {client.data.notes}
                  </p>
                </div>
              ) : null}
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-5">
              <div className="mb-4 flex items-center gap-2">
                <FolderKanban className="size-5 text-blue-700" />
                <h2 className="font-bold text-slate-950">Projects</h2>
              </div>
              {client.data.projects?.length ? (
                <div className="space-y-2">
                  {client.data.projects.map((project) => (
                    <Link
                      key={project.id}
                      href={`/projects/${project.id}`}
                      className="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50/40"
                    >
                      <div className="flex items-center gap-3">
                        <Building2 className="size-4 text-slate-400" />
                        <span className="font-medium text-slate-900">
                          {project.name}
                        </span>
                      </div>
                      <Badge tone={statusTone[project.status]}>
                        {project.status.replace("_", " ")}
                      </Badge>
                    </Link>
                  ))}
                </div>
              ) : (
                <ResourceEmpty
                  title="No projects yet"
                  description="Projects created for this client will appear here."
                />
              )}
            </CardContent>
          </Card>
        </>
      )}

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogTitle>Edit client</DialogTitle>
          <DialogDescription>
            Client information is shared across project planning and reporting.
          </DialogDescription>
          <form
            className="mt-6 space-y-4"
            onSubmit={form.handleSubmit((values) => saveClient.mutate(values))}
          >
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Contact name" error={form.formState.errors.name?.message}>
                <Input {...form.register("name")} />
              </Field>
              <Field label="Company" error={form.formState.errors.company?.message}>
                <Input {...form.register("company")} />
              </Field>
              <Field label="Email" error={form.formState.errors.email?.message}>
                <Input type="email" {...form.register("email")} />
              </Field>
              <Field label="Phone" error={form.formState.errors.phone?.message}>
                <Input {...form.register("phone")} />
              </Field>
            </div>
            <Field label="Address" error={form.formState.errors.address?.message}>
              <Textarea className="min-h-20" {...form.register("address")} />
            </Field>
            <Field label="Notes" error={form.formState.errors.notes?.message}>
              <Textarea className="min-h-20" {...form.register("notes")} />
            </Field>
            <div className="flex justify-end gap-3 pt-2">
              <Button variant="secondary" onClick={() => setDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={saveClient.isPending}>
                {saveClient.isPending ? (
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
