"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Building2, LoaderCircle, Pencil, Plus, Search, Trash2 } from "lucide-react";
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
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { getApiErrorMessage } from "@/lib/api-client";
import { clientsApi } from "@/services/resource-service";
import type { Client } from "@/types/resources";

const clientSchema = z.object({
  name: z.string().trim().min(2, "Enter the client contact name.").max(255),
  company: z.string().trim().min(2, "Enter the company name.").max(255),
  email: z.union([z.string().trim().email("Enter a valid email."), z.literal("")]),
  phone: z.string().trim().max(50),
  address: z.string().trim().max(2000),
  notes: z.string().trim().max(5000),
});

type ClientForm = z.infer<typeof clientSchema>;

const emptyForm: ClientForm = {
  name: "",
  company: "",
  email: "",
  phone: "",
  address: "",
  notes: "",
};

export function ClientsScreen() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Client | null>(null);
  const debouncedSearch = useDebouncedValue(search);
  const clients = useQuery({
    queryKey: ["clients", debouncedSearch, page],
    queryFn: () =>
      clientsApi.list({ search: debouncedSearch || undefined, page, per_page: 10 }),
  });
  const form = useForm<ClientForm>({
    resolver: zodResolver(clientSchema),
    defaultValues: emptyForm,
  });

  const saveClient = useMutation({
    mutationFn: (values: ClientForm) => {
      const payload = Object.fromEntries(
        Object.entries(values).map(([key, value]) => [key, value || null]),
      );
      return editing
        ? clientsApi.update(editing.id, payload)
        : clientsApi.create(payload);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clients"] });
      toast.success(editing ? "Client updated successfully." : "Client created successfully.");
      setDialogOpen(false);
      setEditing(null);
      form.reset(emptyForm);
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  const removeClient = useMutation({
    mutationFn: clientsApi.remove,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clients"] });
      toast.success("Client removed successfully.");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  function openCreate() {
    setEditing(null);
    form.reset(emptyForm);
    setDialogOpen(true);
  }

  function openEdit(client: Client) {
    setEditing(client);
    form.reset({
      name: client.name,
      company: client.company,
      email: client.email ?? "",
      phone: client.phone ?? "",
      address: client.address ?? "",
      notes: client.notes ?? "",
    });
    setDialogOpen(true);
  }

  return (
    <div className="mx-auto max-w-[1480px] space-y-6">
      <PageHeader
        eyebrow="Client directory"
        title="Clients"
        description="Manage client contacts and the companies connected to your delivery portfolio."
        action={
          <Button onClick={openCreate}>
            <Plus className="size-4" />
            Add client
          </Button>
        }
      />

      <Card>
        <CardContent className="p-4 sm:p-5">
          <label className="relative block max-w-md">
            <span className="sr-only">Search clients</span>
            <Search className="pointer-events-none absolute left-3.5 top-3.5 size-5 text-slate-400" />
            <Input
              className="pl-11"
              placeholder="Search name, company, or email"
              value={search}
              onChange={(event) => {
                setSearch(event.target.value);
                setPage(1);
              }}
            />
          </label>
        </CardContent>
      </Card>

      {clients.isPending ? (
        <div className="space-y-3">
          {Array.from({ length: 5 }).map((_, index) => (
            <Skeleton key={index} className="h-20" />
          ))}
        </div>
      ) : clients.isError ? (
        <ResourceError
          message={getApiErrorMessage(clients.error)}
          onRetry={() => clients.refetch()}
        />
      ) : (
        <Card>
          {clients.data.data.length ? (
            <>
              <div className="overflow-x-auto">
                <table className="w-full min-w-[760px] text-left text-sm">
                  <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                      <th className="px-5 py-3">Client</th>
                      <th className="px-5 py-3">Contact</th>
                      <th className="px-5 py-3">Projects</th>
                      <th className="px-5 py-3 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {clients.data.data.map((client) => (
                      <tr key={client.id} className="border-b border-slate-100 last:border-0">
                        <td className="px-5 py-4">
                          <Link
                            href={`/clients/${client.id}`}
                            className="flex items-center gap-3"
                          >
                            <span className="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700">
                              <Building2 className="size-5" />
                            </span>
                            <div>
                              <p className="font-semibold text-slate-950 hover:underline">{client.company}</p>
                              <p className="text-xs text-slate-500">{client.name}</p>
                            </div>
                          </Link>
                        </td>
                        <td className="px-5 py-4 text-slate-600">
                          <p>{client.email ?? "No email"}</p>
                          <p className="text-xs">{client.phone ?? "No phone"}</p>
                        </td>
                        <td className="px-5 py-4 text-slate-600">
                          {client.projects_count ?? 0}
                        </td>
                        <td className="px-5 py-4">
                          <div className="flex justify-end gap-1">
                            <Button variant="ghost" size="icon" onClick={() => openEdit(client)} aria-label={`Edit ${client.company}`}>
                              <Pencil className="size-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-red-600 hover:bg-red-50 hover:text-red-700"
                              disabled={removeClient.isPending}
                              onClick={() => {
                                if (window.confirm(`Delete ${client.company}? This is blocked when projects exist.`)) {
                                  removeClient.mutate(client.id);
                                }
                              }}
                              aria-label={`Delete ${client.company}`}
                            >
                              <Trash2 className="size-4" />
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="flex items-center justify-between border-t border-slate-200 px-5 py-4 text-sm text-slate-600">
                <span>{clients.data.meta.total} clients</span>
                <div className="flex gap-2">
                  <Button variant="secondary" size="sm" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>
                    Previous
                  </Button>
                  <Button variant="secondary" size="sm" disabled={page >= clients.data.meta.last_page} onClick={() => setPage((value) => value + 1)}>
                    Next
                  </Button>
                </div>
              </div>
            </>
          ) : (
            <ResourceEmpty title="No clients found" description="Add a client or adjust your search." />
          )}
        </Card>
      )}

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogTitle>{editing ? "Edit client" : "Add client"}</DialogTitle>
          <DialogDescription>
            Client information is shared across project planning and reporting.
          </DialogDescription>
          <form className="mt-6 space-y-4" onSubmit={form.handleSubmit((values) => saveClient.mutate(values))}>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Contact name" error={form.formState.errors.name?.message}>
                <Input {...form.register("name")} aria-invalid={Boolean(form.formState.errors.name)} />
              </Field>
              <Field label="Company" error={form.formState.errors.company?.message}>
                <Input {...form.register("company")} aria-invalid={Boolean(form.formState.errors.company)} />
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
            {saveClient.isError ? (
              <p className="text-sm text-red-600" role="alert">{getApiErrorMessage(saveClient.error)}</p>
            ) : null}
            <div className="flex justify-end gap-3 pt-2">
              <Button variant="secondary" onClick={() => setDialogOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={saveClient.isPending}>
                {saveClient.isPending ? <LoaderCircle className="size-4 animate-spin" /> : null}
                {editing ? "Save changes" : "Create client"}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}

