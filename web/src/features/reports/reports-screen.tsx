"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { Clock3, Download, FileSpreadsheet, FileText, LoaderCircle } from "lucide-react";
import { toast } from "sonner";

import { PageHeader, ResourceEmpty, ResourceError } from "@/components/resource-states";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { getApiErrorMessage } from "@/lib/api-client";
import { reportsApi } from "@/services/resource-service";

export function ReportsScreen() {
  const report = useQuery({
    queryKey: ["reports", "time-logs"],
    queryFn: reportsApi.timeLogs,
  });
  const download = useMutation({
    mutationFn: reportsApi.exportCsv,
    onSuccess: (blob) => {
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement("a");
      anchor.href = url;
      anchor.download = "projectpulse-time-logs.csv";
      anchor.click();
      URL.revokeObjectURL(url);
      toast.success("Report exported successfully.");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });
  const downloadPdf = useMutation({
    mutationFn: reportsApi.exportPdf,
    onSuccess: (blob) => {
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement("a");
      anchor.href = url;
      anchor.download = "projectpulse-time-logs.pdf";
      anchor.click();
      URL.revokeObjectURL(url);
      toast.success("PDF report exported successfully.");
    },
    onError: (error) => toast.error(getApiErrorMessage(error)),
  });

  return (
    <div className="mx-auto max-w-[1480px] space-y-6">
      <PageHeader
        eyebrow="Effort visibility"
        title="Time reports"
        description="Review recorded effort across projects and export the current report as a secured CSV download."
        action={
          <div className="flex gap-2">
            <Button variant="secondary" onClick={() => downloadPdf.mutate()} disabled={downloadPdf.isPending}>
              {downloadPdf.isPending ? <LoaderCircle className="size-4 animate-spin" /> : <FileText className="size-4" />}PDF
            </Button>
            <Button onClick={() => download.mutate()} disabled={download.isPending}>
              {download.isPending ? <LoaderCircle className="size-4 animate-spin" /> : <Download className="size-4" />}CSV
            </Button>
          </div>
        }
      />
      {report.isPending ? (
        <><div className="grid gap-4 sm:grid-cols-2">{[0,1].map((item) => <Skeleton key={item} className="h-32" />)}</div><Skeleton className="h-96" /></>
      ) : report.isError ? (
        <ResourceError message={getApiErrorMessage(report.error)} onRetry={() => report.refetch()} />
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2">
            <Card><CardContent className="flex items-center justify-between p-5"><div><p className="text-sm text-slate-500">Total logged hours</p><p className="mt-2 text-3xl font-bold text-slate-950">{report.data.total_hours}</p></div><span className="grid size-11 place-items-center rounded-xl bg-blue-50 text-blue-700"><Clock3 className="size-5" /></span></CardContent></Card>
            <Card><CardContent className="flex items-center justify-between p-5"><div><p className="text-sm text-slate-500">Time entries</p><p className="mt-2 text-3xl font-bold text-slate-950">{report.data.total_entries}</p></div><span className="grid size-11 place-items-center rounded-xl bg-cyan-50 text-cyan-800"><FileSpreadsheet className="size-5" /></span></CardContent></Card>
          </div>
          <Card>
            <CardHeader><h2 className="font-bold text-slate-950">Recorded effort</h2></CardHeader>
            {report.data.time_logs.length ? (
              <div className="overflow-x-auto">
                <table className="w-full min-w-[840px] text-left text-sm">
                  <thead className="border-y border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-5 py-3">Date</th><th className="px-5 py-3">Member</th><th className="px-5 py-3">Task</th><th className="px-5 py-3">Project</th><th className="px-5 py-3">Duration</th><th className="px-5 py-3">Note</th></tr></thead>
                  <tbody>{report.data.time_logs.map((log) => <tr key={log.id} className="border-b border-slate-100 last:border-0"><td className="px-5 py-4 text-slate-600">{log.work_date}</td><td className="px-5 py-4 font-medium text-slate-900">{log.user?.name ?? "Unknown"}</td><td className="px-5 py-4 text-slate-700">{log.task?.title ?? "Deleted task"}</td><td className="px-5 py-4 text-slate-600">{log.task?.project?.name ?? "Deleted project"}</td><td className="px-5 py-4 font-semibold text-slate-900">{(log.duration_minutes / 60).toFixed(1)}h</td><td className="max-w-xs truncate px-5 py-4 text-slate-500">{log.note ?? "—"}</td></tr>)}</tbody>
                </table>
              </div>
            ) : <ResourceEmpty title="No time logs yet" description="Member time entries will appear here." />}
          </Card>
        </>
      )}
    </div>
  );
}

