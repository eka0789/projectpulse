import { QueryClient, QueryClientProvider, useQuery } from "@tanstack/react-query";
import {
  IonApp,
  IonBadge,
  IonIcon,
  IonLabel,
  IonLoading,
  IonRouterOutlet,
  IonTabBar,
  IonTabButton,
  IonTabs,
} from "@ionic/react";
import { IonReactRouter } from "@ionic/react-router";
import {
  checkmarkDoneOutline,
  listOutline,
  notificationsOutline,
  personCircleOutline,
} from "ionicons/icons";
import { useState } from "react";
import { Redirect, Route } from "react-router-dom";

import { AuthProvider } from "./auth/AuthProvider";
import { useAuth } from "./auth/auth-context";
import { LoginPage } from "./pages/LoginPage";
import { NotificationsPage } from "./pages/NotificationsPage";
import { ProfilePage } from "./pages/ProfilePage";
import { TaskDetailPage } from "./pages/TaskDetailPage";
import { TasksPage } from "./pages/TasksPage";
import { listNotifications } from "./services/api";

function MemberTabs() {
  const notifications = useQuery({
    queryKey: ["mobile-notifications"],
    queryFn: listNotifications,
    refetchInterval: 60_000,
  });
  const unread = notifications.data?.filter((item) => !item.read_at).length ?? 0;

  return (
    <IonTabs>
      <IonRouterOutlet>
        <Route exact path="/app/tasks" component={TasksPage} />
        <Route exact path="/app/tasks/:id" component={TaskDetailPage} />
        <Route exact path="/app/history" render={() => <TasksPage historyMode />} />
        <Route exact path="/app/notifications" component={NotificationsPage} />
        <Route exact path="/app/profile" component={ProfilePage} />
        <Redirect exact from="/app" to="/app/tasks" />
      </IonRouterOutlet>
      <IonTabBar slot="bottom">
        <IonTabButton tab="tasks" href="/app/tasks"><IonIcon icon={listOutline} /><IonLabel>Tasks</IonLabel></IonTabButton>
        <IonTabButton tab="history" href="/app/history"><IonIcon icon={checkmarkDoneOutline} /><IonLabel>History</IonLabel></IonTabButton>
        <IonTabButton tab="notifications" href="/app/notifications"><IonIcon icon={notificationsOutline} />{unread > 0 ? <IonBadge>{unread > 99 ? "99+" : unread}</IonBadge> : null}<IonLabel>Alerts</IonLabel></IonTabButton>
        <IonTabButton tab="profile" href="/app/profile"><IonIcon icon={personCircleOutline} /><IonLabel>Profile</IonLabel></IonTabButton>
      </IonTabBar>
    </IonTabs>
  );
}

function Routes() {
  const { user, ready } = useAuth();
  if (!ready) return <IonLoading isOpen message="Restoring your session…" />;
  return (
    <IonReactRouter>
      <IonRouterOutlet>
        <Route exact path="/login" component={LoginPage} />
        <Route path="/app" render={() => user ? <MemberTabs /> : <Redirect to="/login" />} />
        <Redirect exact from="/" to={user ? "/app/tasks" : "/login"} />
      </IonRouterOutlet>
    </IonReactRouter>
  );
}

export default function App() {
  const [queryClient] = useState(() => new QueryClient({
    defaultOptions: {
      queries: { retry: 1, staleTime: 20_000, refetchOnWindowFocus: false },
      mutations: { retry: false },
    },
  }));
  return (
    <IonApp>
      <QueryClientProvider client={queryClient}>
        <AuthProvider><Routes /></AuthProvider>
      </QueryClientProvider>
    </IonApp>
  );
}
