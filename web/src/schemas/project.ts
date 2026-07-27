import { z } from "zod";

export const projectWizardSchema = z.object({
  client_id: z.number().int().positive("Select a client."),
  name: z.string().trim().min(2, "Project name is required.").max(255),
  description: z.string().trim().max(10_000),
  client_brief: z.string().trim().min(20, "Add a brief of at least 20 characters.").max(10_000),
  start_date: z.string().min(1, "Start date is required."),
  deadline: z.string().min(1, "Deadline is required."),
}).refine((value) => value.deadline >= value.start_date, {
  message: "Deadline must be on or after the start date.",
  path: ["deadline"],
});

export type ProjectWizardValues = z.infer<typeof projectWizardSchema>;
