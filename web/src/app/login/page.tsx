import { Suspense } from "react";

import { LoginForm } from "@/features/auth/login-form";

export default function LoginPage() {
  return (
    <main className="grid min-h-screen bg-slate-950 lg:grid-cols-[1.08fr_.92fr]">
      <section className="relative hidden overflow-hidden p-10 lg:flex lg:flex-col lg:justify-between xl:p-16">
        <div
          className="absolute inset-0 opacity-40"
          style={{
            backgroundImage:
              "radial-gradient(circle at 18% 18%, #0891b2 0, transparent 27%), radial-gradient(circle at 82% 72%, #1d4ed8 0, transparent 32%)",
          }}
        />
        <div className="relative z-10 flex items-center gap-3 text-white">
          <span className="grid size-10 place-items-center rounded-xl bg-white/10 ring-1 ring-white/20">
            <span className="size-3 rounded-full border-[3px] border-cyan-300" />
          </span>
          <span className="text-lg font-bold">ProjectPulse</span>
        </div>
        <div className="relative z-10 max-w-xl">
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-300">
            One view. Clear delivery.
          </p>
          <h1 className="mt-5 text-4xl font-bold leading-tight tracking-tight text-white xl:text-5xl">
            Keep every client project moving with confidence.
          </h1>
          <p className="mt-6 max-w-lg text-lg leading-8 text-slate-300">
            Plan work, spot delivery risk, and balance your team from one
            focused operations workspace.
          </p>
        </div>
        <p className="relative z-10 text-xs text-slate-500">
          Built for internal project delivery teams.
        </p>
      </section>

      <section className="flex min-h-screen items-center justify-center bg-slate-50 px-5 py-10 sm:px-8">
        <div className="w-full max-w-md">
          <div className="mb-8 flex items-center gap-3 lg:hidden">
            <span className="grid size-10 place-items-center rounded-xl bg-blue-700">
              <span className="size-3 rounded-full border-[3px] border-cyan-200" />
            </span>
            <span className="font-bold text-slate-950">ProjectPulse</span>
          </div>
          <p className="text-sm font-semibold text-blue-700">Admin access</p>
          <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">
            Sign in to your workspace
          </h2>
          <p className="mt-3 text-sm leading-6 text-slate-600">
            Use your ProjectPulse administrator credentials to continue.
          </p>
          <Suspense
            fallback={
              <div className="mt-8 h-72 animate-pulse rounded-2xl bg-slate-200 motion-reduce:animate-none" />
            }
          >
            <LoginForm />
          </Suspense>
          <p className="mt-6 text-center text-xs text-slate-500">
            Demo: admin@projectpulse.test / password
          </p>
        </div>
      </section>
    </main>
  );
}

