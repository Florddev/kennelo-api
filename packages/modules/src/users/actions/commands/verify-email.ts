import { api } from "@workspace/common";

export type VerifyEmailInput = {
    id: string;
    hash: string;
    expires: string;
    signature: string;
};

export async function verifyEmail(input: VerifyEmailInput): Promise<void> {
    const response = await api.get(`/verify-email/${input.id}/${input.hash}`, {
        expires: input.expires,
        signature: input.signature,
    });

    if (response.status !== 200) {
        throw new Error("Failed to verify email");
    }
}
