type IntroStepProps = {
    label: string;
    title: string;
    description: string;
};

export function IntroStep({ label, title, description }: IntroStepProps) {
    return (
        <div className="flex flex-col gap-10 h-full justify-center">
            <div className="flex flex-col gap-4 max-w-3xl">
                <span className="text-base font-semibold text-primary">{label}</span>
                <h1 className="text-4xl md:text-5xl font-semibold font-heading tracking-tight">
                    {title}
                </h1>
                <p className="text-lg text-muted-foreground max-w-xl">{description}</p>
            </div>
        </div>
    );
}
