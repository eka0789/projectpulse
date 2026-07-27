import { Bell, Bot, ShieldCheck } from "lucide-react";

import { PageHeader } from "@/components/resource-states";
import { Card, CardContent, CardHeader } from "@/components/ui/card";

const sections = [
  {
    icon: ShieldCheck,
    title: "Access and security",
    description: "Authentication, role enforcement, and active-account checks are managed by the secured API.",
  },
  {
    icon: Bot,
    title: "AI task generation",
    description: "Provider, model, timeout, and demo fallback are configured through backend environment variables.",
  },
  {
    icon: Bell,
    title: "Notifications",
    description: "In-app task events and the daily deadline reminder run from the backend scheduler.",
  },
];

export default function SettingsPage() {
  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <PageHeader
        eyebrow="Workspace configuration"
        title="Settings"
        description="Review the operational settings controlled by this deployment. Secrets and provider credentials remain server-side."
      />
      <div className="grid gap-4 md:grid-cols-3">
        {sections.map(({ icon: Icon, title, description }) => (
          <Card key={title}>
            <CardHeader>
              <span className="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700"><Icon className="size-5" /></span>
              <h2 className="mt-4 font-bold text-slate-950">{title}</h2>
            </CardHeader>
            <CardContent><p className="text-sm leading-6 text-slate-600">{description}</p></CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
