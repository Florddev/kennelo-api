import { z } from "zod";

export const assignCollaboratorRoleSchema = z.object({
    roleId: z.string().uuid(),
});

export type AssignCollaboratorRoleInput = z.infer<typeof assignCollaboratorRoleSchema>;
