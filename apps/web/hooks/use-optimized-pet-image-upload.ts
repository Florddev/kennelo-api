"use client";

import { useCallback } from "react";
import { addPetImages } from "@workspace/modules/pets";
import { type PetImageModel } from "@workspace/modules/pets";
import { useAsyncState } from "@/hooks/use-async-state";
import { imageCompressionService } from "@/services/image-compression.service";

type UploadProgress = {
    stage: "compressing" | "uploading";
    current: number;
    total: number;
    percent: number;
};

const CHUNK_SIZE = 3;
const MAX_RETRIES = 3;

async function uploadChunkWithRetry(
    petId: string,
    chunk: File[],
    onProgress: (percent: number) => void,
    completedChunks: number,
    totalChunks: number,
): Promise<PetImageModel[]> {
    let lastError: Error | null = null;

    for (let attempt = 0; attempt < MAX_RETRIES; attempt++) {
        try {
            const percent = Math.round(50 + ((completedChunks + 1) / totalChunks) * 50);
            onProgress(percent);
            return await addPetImages(petId, chunk);
        } catch (error) {
            lastError = error as Error;
            if (attempt < MAX_RETRIES - 1) {
                await new Promise((resolve) => setTimeout(resolve, 1000 * (attempt + 1)));
            }
        }
    }

    throw lastError;
}

function useOptimizedPetImageUpload() {
    const uploadState = useAsyncState();

    const uploadCompressedImages = useCallback(
        async (petId: string, files: File[], onProgress?: (progress: UploadProgress) => void) =>
            uploadState.execute(async () => {
                if (!files.length) {
                    throw new Error("No files provided");
                }

                files.forEach((file) => {
                    const validation = imageCompressionService.validateImage(file);
                    if (!validation.valid) throw new Error(validation.error);
                });

                onProgress?.({ stage: "compressing", current: 0, total: files.length, percent: 0 });

                const compressedFiles = await imageCompressionService.compressImages(
                    files,
                    {},
                    (current, total) => {
                        onProgress?.({
                            stage: "compressing",
                            current,
                            total,
                            percent: Math.round((current / total) * 50),
                        });
                    },
                );

                const chunks: File[][] = [];
                for (let i = 0; i < compressedFiles.length; i += CHUNK_SIZE) {
                    chunks.push(compressedFiles.slice(i, i + CHUNK_SIZE));
                }

                const uploadedImages: PetImageModel[] = [];
                let completedChunks = 0;

                for (let i = 0; i < chunks.length; i++) {
                    const chunk = chunks[i];
                    if (!chunk || chunk.length === 0) continue;

                    try {
                        const images = await uploadChunkWithRetry(
                            petId,
                            chunk,
                            (percent) =>
                                onProgress?.({
                                    stage: "uploading",
                                    current: completedChunks + 1,
                                    total: chunks.length,
                                    percent,
                                }),
                            completedChunks,
                            chunks.length,
                        );
                        uploadedImages.push(...images);
                        completedChunks += 1;
                    } catch (error) {
                        const remaining = chunks.length - completedChunks - 1;
                        if (remaining === 0) {
                            throw error;
                        }
                    }
                }

                return uploadedImages;
            }),
        [uploadState],
    );

    return {
        ...uploadState,
        uploadCompressedImages,
    };
}

export { useOptimizedPetImageUpload };
export type { UploadProgress };
