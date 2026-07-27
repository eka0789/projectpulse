import { TasksScreen } from "@/features/tasks/tasks-screen";

export default async function ProjectTasksPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <TasksScreen projectId={id} />;
}
