import {
  IonButton,
  IonContent,
  IonHeader,
  IonIcon,
  IonPage,
  IonTitle,
  IonToolbar,
} from "@ionic/react";
import {
  briefcaseOutline,
  logOutOutline,
  mailOutline,
  personCircleOutline,
  shieldCheckmarkOutline,
} from "ionicons/icons";
import { useState } from "react";

import { useAuth } from "../auth/auth-context";

export function ProfilePage() {
  const { user, signOut } = useAuth();
  const [signingOut, setSigningOut] = useState(false);

  async function handleSignOut() {
    setSigningOut(true);
    try {
      await signOut();
    } finally {
      setSigningOut(false);
    }
  }

  return (
    <IonPage>
      <IonHeader translucent>
        <IonToolbar>
          <IonTitle>Profile</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen className="page-surface">
        <main className="content-wrap">
          <section className="surface-card profile-hero">
            <span className="profile-avatar">
              {user?.avatar_url ? (
                <img src={user.avatar_url} alt="" />
              ) : (
                <IonIcon icon={personCircleOutline} />
              )}
            </span>
            <h1>{user?.name ?? "Team member"}</h1>
            <p className="project-label">{user?.job_title ?? "Member"}</p>
          </section>

          <section className="surface-card timeline-card">
            <h2>Account details</h2>
            <div className="timeline-item">
              <IonIcon icon={mailOutline} />
              <div>
                <p>{user?.email ?? "No email on file"}</p>
                <span>Email address</span>
              </div>
            </div>
            <div className="timeline-item">
              <IonIcon icon={briefcaseOutline} />
              <div>
                <p>{user?.job_title ?? "Not set"}</p>
                <span>Job title</span>
              </div>
            </div>
            <div className="timeline-item">
              <IonIcon icon={shieldCheckmarkOutline} />
              <div>
                <p className="status-pill status-todo">{user?.role ?? "member"}</p>
                <span>Role</span>
              </div>
            </div>
          </section>

          <section className="surface-card action-card">
            <h2>Session</h2>
            <div className="action-row">
              <IonButton
                fill="outline"
                color="danger"
                onClick={() => void handleSignOut()}
                disabled={signingOut}
              >
                <IonIcon slot="start" icon={logOutOutline} />
                Sign out
              </IonButton>
            </div>
          </section>
        </main>
      </IonContent>
    </IonPage>
  );
}
