import { useQuery } from "@tanstack/react-query";
import {
  IonButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonIcon,
  IonPage,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
  IonTitle,
  IonToolbar,
  type RefresherEventDetail,
} from "@ionic/react";
import { alertCircleOutline, calendarOutline, logOutOutline, timeOutline } from "ionicons/icons";
import { useState } from "react";
import { useHistory } from "react-router-dom";

import { useAuth } from "../auth/auth-context";
import { errorMessage, listTasks } from "../services/api";
import type { TaskStatus } from "../types";

const filters: Array<{ label: string; value?: TaskStatus }> = [
  { label: "All" },
  { label: "To do", value: "todo" },
  { label: "In progress", value: "in_progress" },
  { label: "Review", value: "review" },
];

export function TasksPage({ historyMode = false }: { historyMode?: boolean }) {
  const history = useHistory();
  const { signOut } = useAuth();
  const [status, setStatus] = useState<TaskStatus | undefined>(
    historyMode ? "done" : undefined,
  );
  const tasks = useQuery({
    queryKey: ["mobile-tasks", historyMode ? "done" : status ?? "all"],
    queryFn: () => listTasks(historyMode ? "done" : status),
  });

  async function refresh(event: CustomEvent<RefresherEventDetail>) {
    await tasks.refetch();
    event.detail.complete();
  }

  return (
    <IonPage>
      <IonHeader translucent>
        <IonToolbar>
          <IonTitle>{historyMode ? "Completed" : "My tasks"}</IonTitle>
          <IonButtons slot="end">
            <IonButton aria-label="Sign out" onClick={() => void signOut()}>
              <IonIcon icon={logOutOutline} />
            </IonButton>
          </IonButtons>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen className="page-surface">
        <IonRefresher slot="fixed" onIonRefresh={refresh}><IonRefresherContent /></IonRefresher>
        <main className="content-wrap">
          {!historyMode ? (
            <div className="filter-row" role="group" aria-label="Filter tasks by status">
              {filters.map((filter) => (
                <button
                  key={filter.label}
                  className={status === filter.value ? "filter-chip active" : "filter-chip"}
                  onClick={() => setStatus(filter.value)}
                  aria-pressed={status === filter.value}
                >
                  {filter.label}
                </button>
              ))}
            </div>
          ) : null}

          {tasks.isPending ? (
            <div className="task-list">{[0,1,2,3].map((item) => <div className="surface-card task-card" key={item}><IonSkeletonText animated style={{ width: "72%", height: 20 }} /><IonSkeletonText animated style={{ width: "44%", height: 14 }} /><IonSkeletonText animated style={{ width: "100%", height: 42 }} /></div>)}</div>
          ) : tasks.isError ? (
            <div className="surface-card state-card"><IonIcon icon={alertCircleOutline} className="state-icon error" /><h2>Could not load tasks</h2><p>{errorMessage(tasks.error)}</p><IonButton fill="outline" onClick={() => tasks.refetch()}>Try again</IonButton></div>
          ) : tasks.data.data.length ? (
            <div className="task-list">
              {tasks.data.data.map((task) => (
                <button className="surface-card task-card" key={task.id} onClick={() => history.push(`/app/tasks/${task.id}`)}>
                  <div className="task-card-head"><span className={`priority-dot ${task.priority}`} /><div><p className="project-label">{task.project?.name ?? "Project"}</p><h2>{task.title}</h2></div><span className={`status-pill status-${task.status}`}>{task.status.replace("_", " ")}</span></div>
                  <p className="task-description">{task.description || "No description provided."}</p>
                  <div className="task-meta"><span><IonIcon icon={calendarOutline} />{task.deadline ?? "No deadline"}</span><span><IonIcon icon={timeOutline} />{task.estimated_hours ?? 0}h estimate</span></div>
                </button>
              ))}
            </div>
          ) : (
            <div className="surface-card state-card"><IonIcon icon={timeOutline} className="state-icon" /><h2>{historyMode ? "No completed tasks" : "You are all caught up"}</h2><p>{historyMode ? "Finished tasks will appear here." : "No tasks match the selected filter."}</p></div>
          )}
        </main>
      </IonContent>
    </IonPage>
  );
}
