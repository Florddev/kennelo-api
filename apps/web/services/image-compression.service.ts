type CompressionConfig = {
    maxWidth?: number;
    maxHeight?: number;
    quality?: number;
    format?: "webp" | "jpeg";
};

type CompressionResult = {
    blob: Blob;
    originalSize: number;
    compressedSize: number;
    ratio: number;
};

const DEFAULT_CONFIG: CompressionConfig = {
    maxWidth: 1920,
    maxHeight: 1920,
    quality: 0.75,
    format: "webp",
};

function loadImageFromDataUrl(
    dataUrl: string,
    file: File,
    config: Required<CompressionConfig>,
    resolve: (result: CompressionResult) => void,
    reject: (err: Error) => void,
) {
    const img = new Image();
    img.onload = () => resizeAndEncode(img, file, config, resolve, reject);
    img.onerror = () => reject(new Error("Failed to load image"));
    img.src = dataUrl;
}

function resizeAndEncode(
    img: HTMLImageElement,
    file: File,
    config: Required<CompressionConfig>,
    resolve: (result: CompressionResult) => void,
    reject: (err: Error) => void,
) {
    try {
        const canvas = document.createElement("canvas");
        let { width, height } = img;

        if (width > config.maxWidth || height > config.maxHeight) {
            const ratio = Math.min(config.maxWidth / width, config.maxHeight / height);
            width = Math.round(width * ratio);
            height = Math.round(height * ratio);
        }

        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext("2d");
        if (!ctx) throw new Error("Failed to get canvas context");

        ctx.drawImage(img, 0, 0, width, height);

        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    reject(new Error("Canvas conversion failed"));
                    return;
                }
                resolve({
                    blob,
                    originalSize: file.size,
                    compressedSize: blob.size,
                    ratio: blob.size / file.size,
                });
            },
            `image/${config.format}`,
            config.quality,
        );
    } catch (error) {
        reject(error as Error);
    }
}

function createImageCompressionService() {
    async function compressImage(
        file: File,
        config: CompressionConfig = {},
    ): Promise<CompressionResult> {
        const finalConfig = { ...DEFAULT_CONFIG, ...config } as Required<CompressionConfig>;

        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onload = (event) => {
                loadImageFromDataUrl(
                    event.target?.result as string,
                    file,
                    finalConfig,
                    resolve,
                    reject,
                );
            };

            reader.onerror = () => reject(new Error("Failed to read file"));
            reader.readAsDataURL(file);
        });
    }

    async function compressImages(
        files: File[],
        config: CompressionConfig = {},
        onProgress?: (current: number, total: number) => void,
    ): Promise<File[]> {
        const results: File[] = [];

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file) continue;

            const compressed = await compressImage(file, config);

            const format = config.format ?? "webp";
            const baseName = file.name.replace(/\.[^.]+$/, "");
            const compressedFile = new File([compressed.blob], `${baseName}.${format}`, {
                type: `image/${format}`,
            });

            results.push(compressedFile);
            onProgress?.(i + 1, files.length);
        }

        return results;
    }

    function validateImage(file: File): { valid: boolean; error?: string } {
        if (!file.type.startsWith("image/")) {
            return { valid: false, error: "File is not an image" };
        }

        const maxSizeAfterCompression = 5 * 1024 * 1024;
        if (file.size > maxSizeAfterCompression * 10) {
            return {
                valid: false,
                error: "Image is too large (max ~50MB before compression)",
            };
        }

        return { valid: true };
    }

    return {
        compressImage,
        compressImages,
        validateImage,
    };
}

export const imageCompressionService = createImageCompressionService();
export type { CompressionConfig, CompressionResult };
