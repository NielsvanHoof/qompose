import { Badge } from '@/components/ui/badge';
import {
    dossierStatusBadgeVariant,
    dossierStatusLabel,
} from '@/features/dossiers/status/dossier-status';
import type { DossierStatus } from '@/features/dossiers/types';
import { useTranslation } from '@/hooks/use-translation';

export default function DossierStatusBadge({
    status,
}: {
    status: DossierStatus;
}) {
    const { t } = useTranslation();

    return (
        <Badge variant={dossierStatusBadgeVariant(status)}>
            {dossierStatusLabel(status, t)}
        </Badge>
    );
}
