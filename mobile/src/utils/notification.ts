import type { Notification } from "../types";

export function notificationTaskId(notification: Notification): number | null {
  const value = notification.data?.task_id;
  const id = typeof value === "number" ? value : Number(value);

  return Number.isInteger(id) && id > 0 ? id : null;
}
