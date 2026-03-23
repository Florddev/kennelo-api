import { defineStepper, type Step, type Stepper, type StepperReturn } from "@stepperize/react";

type BecomeHostSteps = [
    Step<"welcome">,
    Step<"host-type">,
    Step<"establishment-type">,
    Step<"establishment-info">,
    Step<"contact-details">,
    Step<"address">,
    Step<"business-info">,
    Step<"review">,
];

const stepper: StepperReturn<BecomeHostSteps> = defineStepper(
    { id: "welcome" },
    { id: "host-type" },
    { id: "establishment-type" },
    { id: "establishment-info" },
    { id: "contact-details" },
    { id: "address" },
    { id: "business-info" },
    { id: "review" },
);

export const useStepper: (props?: {
    initialStep?: BecomeHostSteps[number]["id"];
}) => Stepper<BecomeHostSteps> = stepper.useStepper;

export const steps: BecomeHostSteps = stepper.steps;
export const Scoped = stepper.Scoped;

export type StepId = BecomeHostSteps[number]["id"];
