import { Badge } from '@/components/ui/badge';
import {
    dossierStatusBadgeVariant,
    dossierStatusLabel,
} from '@/features/dossiers/dossier-status';
import type { DossierStatus } from '@/features/dossiers/types';

export default function DossierStatusBadge({
    status,
}: {
    status: DossierStatus;
}) {
    return (
        <Badge variant={dossierStatusBadgeVariant(status)}>
            {dossierStatusLabel(status)}
        </Badge>
    );
}
