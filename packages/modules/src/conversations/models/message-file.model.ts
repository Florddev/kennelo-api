import type { MessageFileDto } from "./dtos/message-file.dto";

export class MessageFileModel {
    private constructor(
        public readonly id: string,
        public readonly fileName: string,
        public readonly filePath: string,
        public readonly fileType: string,
        public readonly fileSize: number,
        public readonly mimeType: string,
    ) {}

    static from(dto: MessageFileDto): MessageFileModel {
        return new MessageFileModel(
            dto.id,
            dto.file_name,
            dto.file_path,
            dto.file_type,
            dto.file_size,
            dto.mime_type,
        );
    }
}
