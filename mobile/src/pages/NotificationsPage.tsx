import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  IonButton,
  IonContent,
  IonHeader,
  IonIcon,
  IonPage,
  IonSkeletonText,
  IonTitle,
  IonToolbar,
} from "@ionic/react";
import { checkmarkDoneOutline, notificationsOutline } from "ionicons/icons";

import { errorMessage, listNotifications, markAllNotificationsRead, markNotificationRead } from "../services/api";

export function NotificationsPage() {
  const queryClient = useQueryClient();
  const notifications = useQuery({ queryKey: ["mobile-notifications"], queryFn: listNotifications });
  const read = useMutation({
    mutationFn: markNotificationRead,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["mobile-notifications"] }),
  });
  const readAll = useMutation({
    mutationFn: markAllNotificationsRead,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["mobile-notifications"] }),
  });

  return (
    <IonPage>
      <IonHeader translucent>
        <IonToolbar>
          <IonTitle>Notifications</IonTitle>
          <IonButton slot="end" fill="clear" onClick={() => readAll.mutate()} disabled={readAll.isPending} aria-label="Mark all notifications read"><IonIcon icon={checkmarkDoneOutline} /></IonButton>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen className="page-surface">
        <main className="content-wrap">
          {notifications.isPending ? (
            <div className="notification-list">{[0,1,2,3].map((item) => <div key={item} className="surface-card notification-card"><IonSkeletonText animated style={{ width: "60%" }} /><IonSkeletonText animated style={{ width: "90%" }} /></div>)}</div>
          ) : notifications.isError ? (
            <div className="surface-card state-card"><h2>Could not load notifications</h2><p>{errorMessage(notifications.error)}</p><IonButton fill="outline" onClick={() => notifications.refetch()}>Try again</IonButton></div>
          ) : notifications.data.length ? (
            <div className="notification-list">{notifications.data.map((item) => <button key={item.id} className={`surface-card notification-card ${item.read_at ? "" : "unread"}`} onClick={() => !item.read_at && read.mutate(item.id)}><IonIcon icon={notificationsOutline} /><div><div className="notification-title"><h2>{item.title}</h2><time>{new Date(item.created_at).toLocaleDateString()}</time></div><p>{item.message}</p></div></button>)}</div>
          ) : (
            <div className="surface-card state-card"><IonIcon icon={notificationsOutline} className="state-icon" /><h2>Inbox is clear</h2><p>Assignments and deadline reminders will appear here.</p></div>
          )}
        </main>
      </IonContent>
    </IonPage>
  );
}

