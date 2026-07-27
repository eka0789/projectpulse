"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { LoaderCircle, Pencil, Plus, Search, UserMinus, Users } from "lucide-react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";

import { Field, PageHeader, ResourceEmpty, ResourceError } from "@/components/resource-states";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Dialog, DialogContent, DialogDescription, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { getApiErrorMessage } from "@/lib/api-client";
import { membersApi } from "@/services/resource-service";
import type { Member } from "@/types/resources";

const schema = z.object({
  name: z.string().trim().min(2).max(255),
  email: z.string().trim().email(),
  password: z.string().max(255),
  role: z.enum(["admin", "member"]),
  job_title: z.string().trim().min(2).max(255),
  avatar_url: z.union([z.string().trim().url(), z.literal("")]),
  is_active: z.enum(["true", "false"]),
});
type MemberForm = z.infer<typeof schema>;

const emptyForm: MemberForm = {
  name: "",
  email: "",
  password: "",
  role: "member",
  job_title: "",
  avatar_url: "",
  is_active: "true",
};

export function MembersScreen() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");
  const [editing, setEditing] = useState<Member | null>(null);
  const [open, setOpen] = useState(false);
  const debouncedSearch = useDebouncedValue(search);
  const members = useQuery({
    queryKey: ["members", debouncedSearch],
    queryFn: () => membersApi.list({ search: debouncedSearch || undefined, per_page: 50 }),
  });
  const form = useForm<MemberForm>({ resolver: zodResolver(schema), defaultValues: emptyForm });
  const save = useMutation({
    mutationFn: (values: MemberForm) => {
      if (!editing && values.password.length < 8) {
        throw new Error("A new account password must contain at least 8 characters.");
      }
      const payload: Record<string, unknown> = {
        name: values.name,
        email: values.email,
        role: values.role,
        job_title: values.job_title,
        avatar_url: values.avatar_url || null,
        is_active: values.is_active === "true",
      };
      if (values.password) payload.password = values.password;
      return editing ? membersApi.update(editing.id, payload) : membersApi.create(payload);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["members"] });
      setOpen(false);
      toast.success("Team member saved successfully.");
    },
    onError: (error) => toast.error(error instanceof Error ? error.message : getApiErrorMessage(error)),
  });
  const deactivate = useMutation({
    mutationFn: membersApi.deactivate,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["members"] });
      toast.success("Account deactivated and its tokens revoked.");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  function createMember() {
    setEditing(null);
    form.reset(emptyForm);
    setOpen(true);
  }
  function editMember(member: Member) {
    setEditing(member);
    form.reset({
      name: member.name,
      email: member.email,
      password: "",
      role: member.role,
      job_title: member.job_title ?? "",
      avatar_url: member.avatar_url ?? "",
      is_active: String(member.is_active) as "true" | "false",
    });
    setOpen(true);
  }

  return (
    <div className="mx-auto max-w-[1480px] space-y-6">
      <PageHeader eyebrow="People and access" title="Team members" description="Create accounts, manage roles, and deactivate access. Backend rules prevent removal of the last administrator." action={<Button onClick={createMember}><Plus className="size-4" />Add member</Button>} />
      <Card className="p-4">
        <label className="relative block max-w-md"><span className="sr-only">Search members</span><Search className="absolute left-3.5 top-3.5 size-5 text-slate-400" /><Input className="pl-11" placeholder="Search name, email, or job title" value={search} onChange={(event) => setSearch(event.target.value)} /></label>
      </Card>
      {members.isPending ? (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">{Array.from({ length: 6 }).map((_, index) => <Skeleton key={index} className="h-40" />)}</div>
      ) : members.isError ? (
        <ResourceError message={getApiErrorMessage(members.error)} onRetry={() => members.refetch()} />
      ) : members.data.data.length ? (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {members.data.data.map((member) => (
            <Card key={member.id} className="p-5">
              <div className="flex items-start gap-4">
                <span className="grid size-12 place-items-center rounded-full bg-blue-50 text-sm font-bold text-blue-800">{member.name.split(" ").map((word) => word[0]).join("").slice(0,2).toUpperCase()}</span>
                <div className="min-w-0 flex-1"><h2 className="truncate font-bold text-slate-950">{member.name}</h2><p className="truncate text-sm text-slate-500">{member.job_title}</p><p className="mt-1 truncate text-xs text-slate-500">{member.email}</p></div>
                <Badge tone={member.is_active ? "green" : "red"}>{member.is_active ? "Active" : "Inactive"}</Badge>
              </div>
              <div className="mt-5 flex items-center justify-between border-t border-slate-100 pt-3">
                <Badge tone={member.role === "admin" ? "blue" : "slate"}>{member.role}</Badge>
                <div className="flex gap-1"><Button variant="ghost" size="icon" onClick={() => editMember(member)} aria-label={`Edit ${member.name}`}><Pencil className="size-4" /></Button>{member.is_active ? <Button variant="ghost" size="icon" className="text-red-600 hover:bg-red-50" onClick={() => { if (window.confirm(`Deactivate ${member.name}?`)) deactivate.mutate(member.id); }} aria-label={`Deactivate ${member.name}`}><UserMinus className="size-4" /></Button> : null}</div>
              </div>
            </Card>
          ))}
        </div>
      ) : <Card><ResourceEmpty title="No team members found" description="Add a member or adjust your search." /></Card>}

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogTitle>{editing ? "Edit team member" : "Create team member"}</DialogTitle>
          <DialogDescription>Roles and account state are enforced by the backend on every request.</DialogDescription>
          <form className="mt-6 space-y-4" onSubmit={form.handleSubmit((values) => save.mutate(values))}>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Name" error={form.formState.errors.name?.message}><Input {...form.register("name")} /></Field>
              <Field label="Email" error={form.formState.errors.email?.message}><Input type="email" {...form.register("email")} /></Field>
              <Field label={editing ? "New password (optional)" : "Password"} error={form.formState.errors.password?.message}><Input type="password" autoComplete="new-password" {...form.register("password")} /></Field>
              <Field label="Job title" error={form.formState.errors.job_title?.message}><Input {...form.register("job_title")} /></Field>
              <Field label="Role" error={form.formState.errors.role?.message}><Select {...form.register("role")}><option value="member">Member</option><option value="admin">Admin</option></Select></Field>
              <Field label="Account status" error={form.formState.errors.is_active?.message}><Select {...form.register("is_active")}><option value="true">Active</option><option value="false">Inactive</option></Select></Field>
            </div>
            <Field label="Avatar URL (optional)" error={form.formState.errors.avatar_url?.message}><Input type="url" {...form.register("avatar_url")} /></Field>
            {save.isError ? <p className="text-sm text-red-600">{save.error instanceof Error ? save.error.message : getApiErrorMessage(save.error)}</p> : null}
            <div className="flex justify-end gap-3"><Button variant="secondary" onClick={() => setOpen(false)}>Cancel</Button><Button type="submit" disabled={save.isPending}>{save.isPending ? <LoaderCircle className="size-4 animate-spin" /> : <Users className="size-4" />}Save member</Button></div>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}

