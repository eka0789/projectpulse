import { describe, expect, it } from "vitest";

import { notificationTaskId } from "./notification";
import type { Notification } from "../types";

const base: Notification = {
  id: "notice",
  type: "TaskAssigned",
  title: "Assigned",
  message: "A task was assigned.",
  data: null,
  read_at: null,
  created_at: "2026-07-27T00:00:00Z",
};

describe("notificationTaskId", () => {
  it("returns a valid task id", () => {
    expect(notificationTaskId({ ...base, data: { task_id: 42 } })).toBe(42);
  });

  it("ignores missing or invalid task ids", () => {
    expect(notificationTaskId(base)).toBeNull();
    expect(notificationTaskId({ ...base, data: { task_id: "bad" } })).toBeNull();
  });
});
