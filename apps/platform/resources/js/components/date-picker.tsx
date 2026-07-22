import { CalendarIcon, X } from 'lucide-react';
import { useState } from 'react';
import { enGB, nl } from 'react-day-picker/locale';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type DatePickerProps = {
    id: string;
    name: string;
    defaultValue?: string;
    invalid?: boolean;
};

export default function DatePicker({
    id,
    name,
    defaultValue = '',
    invalid = false,
}: DatePickerProps) {
    const { locale, t } = useTranslation();
    const [isOpen, setIsOpen] = useState(false);
    const [selectedDate, setSelectedDate] = useState<Date | undefined>(() =>
        parseDate(defaultValue),
    );
    const calendarLocale = locale === 'nl' ? nl : enGB;

    function selectDate(date: Date | undefined) {
        setSelectedDate(date);
        setIsOpen(false);
    }

    return (
        <div className="flex gap-2">
            <input
                type="hidden"
                name={name}
                value={selectedDate ? serializeDate(selectedDate) : ''}
                readOnly
            />

            <Popover open={isOpen} onOpenChange={setIsOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        aria-invalid={invalid}
                        className={cn(
                            'min-w-0 flex-1 justify-start text-left font-normal',
                            !selectedDate && 'text-muted-foreground',
                        )}
                    >
                        <CalendarIcon aria-hidden="true" />
                        <span className="truncate">
                            {selectedDate
                                ? selectedDate.toLocaleDateString(locale, {
                                      dateStyle: 'long',
                                  })
                                : t('Choose a date')}
                        </span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="single"
                        selected={selectedDate}
                        defaultMonth={selectedDate}
                        onSelect={selectDate}
                        locale={calendarLocale}
                        autoFocus
                    />
                </PopoverContent>
            </Popover>

            {selectedDate ? (
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={() => setSelectedDate(undefined)}
                >
                    <X aria-hidden="true" />
                    <span className="sr-only">{t('Clear date')}</span>
                </Button>
            ) : null}
        </div>
    );
}

function parseDate(value: string): Date | undefined {
    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return undefined;
    }

    const date = new Date(year, month - 1, day);

    if (
        date.getFullYear() !== year ||
        date.getMonth() !== month - 1 ||
        date.getDate() !== day
    ) {
        return undefined;
    }

    return date;
}

function serializeDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}
