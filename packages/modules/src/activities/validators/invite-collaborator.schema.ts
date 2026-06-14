import { z } from "zod";

export const inviteCollaboratorSchema = z.object({
    email: z.string().min(1, "Email is required").email("Invalid email address"),
});

export type InviteCollaboratorInput = z.infer<typeof inviteCollaboratorSchema>;
