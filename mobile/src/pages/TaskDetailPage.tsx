import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  IonBackButton,
  IonButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonIcon,
  IonInput,
  IonModal,
  IonPage,
  IonSpinner,
  IonTextarea,
  IonTitle,
  IonToast,
  IonToolbar,
} from "@ionic/react";
import {
  addCircleOutline,
  alertCircleOutline,
  checkmarkCircleOutline,
  documentTextOutline,
  timeOutline,
} from "ionicons/icons";
import { useState } from "react";
import { Controller, useForm } from "react-hook-form";
import { useParams } from "react-router-dom";
import { z } from "zod";

import {
  addProgressNote,
  addTimeLog,
  errorMessage,
  taskDetail,
  updateTaskStatus,
} from "../services/api";
import type { TaskStatus } from "../types";

const timeSchema = z.object({
  work_date: z.string().min(1, "Select a work date."),
  hours: z.coerce.number().positive("Hours must be greater than zero.").max(24),
  note: z.string().max(5000),
});
type TimeInput = z.input<typeof timeSchema>;
type TimeValues = z.output<typeof timeSchema>;
const noteSchema = z.object({ note: z.string().trim().min(3).max(5000) });
type NoteValues = z.infer<typeof noteSchema>;

const transitions: Record<TaskStatus, TaskStatus[]> = {
  todo: ["in_progress"],
  in_progress: ["review"],
  review: ["done", "in_progress"],
  done: [],
};

export function TaskDetailPage() {
  const { id } = useParams<{ id: string }>();
  const taskId = Number(id);
  const queryClient = useQueryClient();
  const [timeOpen, setTimeOpen] = useState(false);
  const [noteOpen, setNoteOpen] = useState(false);
  const [toast, setToast] = useState<string | null>(null);
  const task = useQuery({
    queryKey: ["mobile-task", taskId],
    queryFn: () => taskDetail(taskId),
    enabled: Number.isInteger(taskId),
  });
  const timeForm = useForm<TimeInput, unknown, TimeValues>({
    resolver: zodResolver(timeSchema),
    defaultValues: {
      work_date: new Date().toISOString().slice(0, 10),
      hours: 1,
      note: "",
    },
  });
  const noteForm = useForm<NoteValues>({
    resolver: zodResolver(noteSchema),
    defaultValues: { note: "" },
  });

  function refreshTask() {
    queryClient.invalidateQueries({ queryKey: ["mobile-task", taskId] });
    queryClient.invalidateQueries({ queryKey: ["mobile-tasks"] });
  }

  const statusMutation = useMutation({
    mutationFn: (status: TaskStatus) => updateTaskStatus(taskId, status),
    onSuccess: (_data, status) => {
      refreshTask();
      setToast(`Task moved to ${status.replace("_", " ")}.`);
    },
    onError: (error) => setToast(errorMessage(error)),
  });
  const timeMutation = useMutation({
    mutationFn: (values: TimeValues) =>
      addTimeLog(taskId, {
        work_date: values.work_date,
        duration_minutes: Math.round(values.hours * 60),
        note: values.note || undefined,
      }),
    onSuccess: () => {
      refreshTask();
      setTimeOpen(false);
      timeForm.reset({
        work_date: new Date().toISOString().slice(0, 10),
        hours: 1,
        note: "",
      });
      setToast("Time entry added.");
    },
  });
  const noteMutation = useMutation({
    mutationFn: (values: NoteValues) => addProgressNote(taskId, values.note),
    onSuccess: () => {
      refreshTask();
      setNoteOpen(false);
      noteForm.reset();
      setToast("Progress note added.");
    },
  });

  return (
    <IonPage>
      <IonHeader translucent>
        <IonToolbar>
          <IonButtons slot="start"><IonBackButton defaultHref="/app/tasks" /></IonButtons>
          <IonTitle>Task detail</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen className="page-surface">
        <main className="content-wrap">
          {task.isPending ? (
            <div className="surface-card state-card"><IonSpinner name="crescent" /><p>Loading task…</p></div>
          ) : task.isError ? (
            <div className="surface-card state-card"><IonIcon icon={alertCircleOutline} className="state-icon error" /><h2>Task unavailable</h2><p>{errorMessage(task.error)}</p><IonButton fill="outline" onClick={() => task.refetch()}>Try again</IonButton></div>
          ) : (
            <div className="detail-stack">
              <section className="surface-card detail-hero">
                <p className="project-label">{task.data.project?.name ?? "Project"}</p>
                <div className="detail-title"><h1>{task.data.title}</h1><span className={`status-pill status-${task.data.status}`}>{task.data.status.replace("_", " ")}</span></div>
                <p>{task.data.description || "No description provided."}</p>
                <div className="detail-grid"><div><span>Priority</span><strong>{task.data.priority}</strong></div><div><span>Deadline</span><strong>{task.data.deadline ?? "Not set"}</strong></div><div><span>Estimate</span><strong>{task.data.estimated_hours ?? 0} hours</strong></div><div><span>Logged</span><strong>{((task.data.total_logged_minutes ?? 0) / 60).toFixed(1)} hours</strong></div></div>
              </section>

              {transitions[task.data.status].length ? (
                <section className="surface-card action-card"><h2>Update status</h2><div className="action-row">{transitions[task.data.status].map((status) => <IonButton key={status} fill={status === "done" ? "solid" : "outline"} onClick={() => statusMutation.mutate(status)} disabled={statusMutation.isPending}>{status === "done" ? <IonIcon slot="start" icon={checkmarkCircleOutline} /> : null}{status.replace("_", " ")}</IonButton>)}</div></section>
              ) : null}

              <section className="surface-card action-card">
                <h2>Record progress</h2>
                <div className="action-row"><IonButton fill="outline" onClick={() => setTimeOpen(true)}><IonIcon slot="start" icon={timeOutline} />Log time</IonButton><IonButton fill="outline" onClick={() => setNoteOpen(true)}><IonIcon slot="start" icon={addCircleOutline} />Add note</IonButton></div>
              </section>

              <section className="surface-card timeline-card">
                <h2>Progress notes</h2>
                {task.data.progress_notes?.length ? task.data.progress_notes.map((note) => <article key={note.id} className="timeline-item"><IonIcon icon={documentTextOutline} /><div><p>{note.note}</p><span>{note.user?.name ?? "Team member"} · {new Date(note.created_at).toLocaleString()}</span></div></article>) : <p className="empty-inline">No progress notes yet.</p>}
              </section>
              <section className="surface-card timeline-card">
                <h2>Time entries</h2>
                {task.data.time_logs?.length ? task.data.time_logs.map((log) => <article key={log.id} className="timeline-item"><IonIcon icon={timeOutline} /><div><p>{(log.duration_minutes / 60).toFixed(1)}h · {log.note || "Work logged"}</p><span>{log.work_date}</span></div></article>) : <p className="empty-inline">No time recorded yet.</p>}
              </section>
            </div>
          )}
        </main>
      </IonContent>

      <IonModal isOpen={timeOpen} onDidDismiss={() => setTimeOpen(false)} initialBreakpoint={0.72} breakpoints={[0, 0.72, 1]}>
        <IonHeader><IonToolbar><IonTitle>Log time</IonTitle><IonButtons slot="end"><IonButton onClick={() => setTimeOpen(false)}>Close</IonButton></IonButtons></IonToolbar></IonHeader>
        <IonContent className="ion-padding">
          <form className="mobile-form" onSubmit={timeForm.handleSubmit((values) => timeMutation.mutate(values))}>
            <label>Work date</label><Controller control={timeForm.control} name="work_date" render={({ field }) => <IonInput type="date" fill="outline" value={field.value} onIonInput={(event) => field.onChange(event.detail.value ?? "")} />} />{timeForm.formState.errors.work_date ? <p className="form-error">{timeForm.formState.errors.work_date.message}</p> : null}
            <label>Hours</label><Controller control={timeForm.control} name="hours" render={({ field }) => <IonInput type="number" min="0.1" max="24" step="0.1" fill="outline" value={String(field.value)} onIonInput={(event) => field.onChange(event.detail.value ?? "")} />} />{timeForm.formState.errors.hours ? <p className="form-error">{timeForm.formState.errors.hours.message}</p> : null}
            <label>Note</label><Controller control={timeForm.control} name="note" render={({ field }) => <IonTextarea fill="outline" value={field.value} onIonInput={(event) => field.onChange(event.detail.value ?? "")} />} />
            {timeMutation.isError ? <p className="form-error">{errorMessage(timeMutation.error)}</p> : null}
            <IonButton expand="block" type="submit" disabled={timeMutation.isPending}>{timeMutation.isPending ? <IonSpinner name="crescent" /> : "Save time entry"}</IonButton>
          </form>
        </IonContent>
      </IonModal>

      <IonModal isOpen={noteOpen} onDidDismiss={() => setNoteOpen(false)} initialBreakpoint={0.6} breakpoints={[0, 0.6, 1]}>
        <IonHeader><IonToolbar><IonTitle>Add progress note</IonTitle><IonButtons slot="end"><IonButton onClick={() => setNoteOpen(false)}>Close</IonButton></IonButtons></IonToolbar></IonHeader>
        <IonContent className="ion-padding">
          <form className="mobile-form" onSubmit={noteForm.handleSubmit((values) => noteMutation.mutate(values))}>
            <label>What changed?</label><Controller control={noteForm.control} name="note" render={({ field }) => <IonTextarea autoGrow fill="outline" value={field.value} onIonInput={(event) => field.onChange(event.detail.value ?? "")} />} />{noteForm.formState.errors.note ? <p className="form-error">{noteForm.formState.errors.note.message}</p> : null}
            {noteMutation.isError ? <p className="form-error">{errorMessage(noteMutation.error)}</p> : null}
            <IonButton expand="block" type="submit" disabled={noteMutation.isPending}>{noteMutation.isPending ? <IonSpinner name="crescent" /> : "Save progress note"}</IonButton>
          </form>
        </IonContent>
      </IonModal>
      <IonToast isOpen={Boolean(toast)} message={toast ?? ""} duration={2600} onDidDismiss={() => setToast(null)} />
    </IonPage>
  );
}

