type BookingInfoRowProps = {
    label: string;
    value: string;
};

export function BookingInfoRow({ label, value }: BookingInfoRowProps) {
    return (
        <div className="flex items-center justify-between">
            <span className="text-sm font-medium text-foreground">{label}</span>
            <span className="text-sm text-muted-foreground">{value}</span>
        </div>
    );
}
