/** Uploaded file attached to a media-library document request. */
export type MediaUploadedDocument = {
    id: number;
    original_filename: string;
    size_bytes: number;
    uploaded_at: string;
};

/** Document request row in the media library. */
export type MediaDocument = {
    id: number;
    title: string;
    status: string;
    updated_at: string | null;
    client_name: string;
    dossier: {
        id: number;
        title: string;
        reference: string | null;
    };
    uploaded_document: MediaUploadedDocument | null;
};
