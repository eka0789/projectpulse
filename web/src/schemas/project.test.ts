import { describe, expect, it } from "vitest";

import { projectWizardSchema } from "./project";

describe("projectWizardSchema", () => {
  it("accepts a complete project brief", () => {
    expect(projectWizardSchema.safeParse({
      client_id: 1,
      name: "Customer portal",
      description: "",
      client_brief: "Build a secure customer portal with reporting.",
      start_date: "2026-08-01",
      deadline: "2026-09-01",
    }).success).toBe(true);
  });

  it("rejects a deadline before the start date", () => {
    expect(projectWizardSchema.safeParse({
      client_id: 1,
      name: "Customer portal",
      description: "",
      client_brief: "Build a secure customer portal with reporting.",
      start_date: "2026-09-01",
      deadline: "2026-08-01",
    }).success).toBe(false);
  });
});
