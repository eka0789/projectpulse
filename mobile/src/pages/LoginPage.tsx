import { zodResolver } from "@hookform/resolvers/zod";
import {
  IonButton,
  IonContent,
  IonIcon,
  IonInput,
  IonPage,
  IonSpinner,
} from "@ionic/react";
import { arrowForwardOutline, pulseOutline } from "ionicons/icons";
import { Controller, useForm } from "react-hook-form";
import { Redirect } from "react-router-dom";
import { z } from "zod";

import { useAuth } from "../auth/auth-context";
import { errorMessage } from "../services/api";

const schema = z.object({
  email: z.string().trim().email("Enter a valid email address."),
  password: z.string().min(8, "Password must contain at least 8 characters."),
});
type FormValues = z.infer<typeof schema>;

export function LoginPage() {
  const { user, signIn } = useAuth();
  const form = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      email: "member@projectpulse.test",
      password: "password",
    },
  });

  async function onSubmit(values: FormValues) {
    form.clearErrors("root");

    try {
      await signIn(values.email, values.password);
    } catch (error) {
      form.setError("root", { message: errorMessage(error) });
    }
  }

  if (user) return <Redirect to="/app/tasks" />;

  return (
    <IonPage>
      <IonContent fullscreen className="page-surface">
        <main className="login-shell">
          <section className="login-brand" aria-label="ProjectPulse">
            <div className="brand-mark"><IonIcon icon={pulseOutline} /></div>
            <p>ProjectPulse</p>
            <h1>Your workday, clearly prioritized.</h1>
            <span>Track assigned work, progress, and time from anywhere.</span>
          </section>
          <section className="login-form-card">
            <p className="eyebrow">Member access</p>
            <h2>Welcome back</h2>
            <p className="supporting">Sign in with your team account to view assigned tasks.</p>
            <form onSubmit={form.handleSubmit(onSubmit)} noValidate>
              <label>Email address</label>
              <Controller
                control={form.control}
                name="email"
                render={({ field }) => (
                  <IonInput
                    type="email"
                    value={field.value}
                    onIonInput={(event) => field.onChange(event.detail.value ?? "")}
                    autocomplete="email"
                    fill="outline"
                    aria-label="Email address"
                  />
                )}
              />
              {form.formState.errors.email ? <p className="form-error">{form.formState.errors.email.message}</p> : null}
              <label>Password</label>
              <Controller
                control={form.control}
                name="password"
                render={({ field }) => (
                  <IonInput
                    type="password"
                    value={field.value}
                    onIonInput={(event) => field.onChange(event.detail.value ?? "")}
                    autocomplete="current-password"
                    fill="outline"
                    aria-label="Password"
                  />
                )}
              />
              {form.formState.errors.password ? <p className="form-error">{form.formState.errors.password.message}</p> : null}
              {form.formState.errors.root ? <p className="login-error" role="alert">{form.formState.errors.root.message}</p> : null}
              <IonButton
                expand="block"
                type="submit"
                disabled={form.formState.isSubmitting}
              >
                {form.formState.isSubmitting ? <IonSpinner name="crescent" /> : <>Sign in <IonIcon slot="end" icon={arrowForwardOutline} /></>}
              </IonButton>
            </form>
            <p className="demo-note">Demo: member@projectpulse.test / password</p>
          </section>
        </main>
      </IonContent>
    </IonPage>
  );
}
