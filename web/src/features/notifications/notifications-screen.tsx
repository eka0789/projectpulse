"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Bell, Check, CheckCheck } from "lucide-react";
import { toast } from "sonner";

import { PageHeader, ResourceEmpty, ResourceError } from "@/components/resource-states";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { getApiErrorMessage } from "@/lib/api-client";
import { notificationsApi } from "@/services/resource-service";

export function NotificationsScreen() {
  const queryClient = useQueryClient();
  const notifications = useQuery({
    queryKey: ["notifications"],
    queryFn: notificationsApi.list,
  });
  const markRead = useMutation({
    mutationFn: notificationsApi.markRead,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
      queryClient.invalidateQueries({ queryKey: ["notifications", "unread"] });
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });
  const markAll = useMutation({
    mutationFn: notificationsApi.markAllRead,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
      queryClient.invalidateQueries({ queryKey: ["notifications", "unread"] });
      toast.success("All notifications marked as read.");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <PageHeader
        eyebrow="Activity inbox"
        title="Notifications"
        description="Assignment, discussion, status, and deadline activity for your account."
        action={<Button variant="secondary" onClick={() => markAll.mutate()} disabled={markAll.isPending}><CheckCheck className="size-4" />Mark all read</Button>}
      />
      {notifications.isPending ? (
        <div className="space-y-3">{Array.from({ length: 6 }).map((_, index) => <Skeleton key={index} className="h-24" />)}</div>
      ) : notifications.isError ? (
        <ResourceError message={getApiErrorMessage(notifications.error)} onRetry={() => notifications.refetch()} />
      ) : notifications.data.data.length ? (
        <Card className="divide-y divide-slate-100 overflow-hidden">
          {notifications.data.data.map((notification) => (
            <article key={notification.id} className={`flex gap-4 p-5 ${notification.read_at ? "bg-white" : "bg-blue-50/60"}`}>
              <span className={`grid size-10 shrink-0 place-items-center rounded-full ${notification.read_at ? "bg-slate-100 text-slate-500" : "bg-blue-100 text-blue-700"}`}><Bell className="size-5" /></span>
              <div className="min-w-0 flex-1"><div className="flex flex-col justify-between gap-1 sm:flex-row"><h2 className="font-semibold text-slate-950">{notification.title}</h2><time className="text-xs text-slate-500">{new Date(notification.created_at).toLocaleString()}</time></div><p className="mt-1 text-sm leading-6 text-slate-600">{notification.message}</p></div>
              {!notification.read_at ? <Button variant="ghost" size="icon" onClick={() => markRead.mutate(notification.id)} aria-label={`Mark ${notification.title} as read`}><Check className="size-4" /></Button> : null}
            </article>
          ))}
        </Card>
      ) : <Card><ResourceEmpty title="Inbox is clear" description="New assignments and deadlines will appear here." /></Card>}
    </div>
  );
}

