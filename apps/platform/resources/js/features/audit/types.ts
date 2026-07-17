/** Subject morph attached to an activity log entry. */
export type ActivityLogSubject = {
    type: string;
    id: number;
    name: string | null;
};

/** Compact request context shown on each activity row. */
export type ActivityLogProperties = {
    ip: string | null;
    route: string | null;
};

/** Spatie attribute_changes payload for model-driven logs. */
export type ActivityLogAttributeChanges = {
    attributes: Record<string, unknown>;
    old: Record<string, unknown>;
};

/** One row in the tenant activity / audit log. */
export type ActivityLogEntry = {
    id: number;
    event: string | null;
    label: string;
    description: string;
    causer_name: string | null;
    subject: ActivityLogSubject | null;
    created_at: string | null;
    properties: ActivityLogProperties;
    attribute_changes: ActivityLogAttributeChanges | null;
};
