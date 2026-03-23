"use client";

import { useEffect } from "react";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { BecomeHostStepper } from "@/features/become-host/components/become-host-stepper";

export default function BecomeHostPage() {
    const { isAuthenticated, isLoaded } = useAuth();
    const { routes, router } = useNavigation();

    useEffect(() => {
        if (isLoaded && !isAuthenticated) {
            router.push(routes.Login());
        }
    }, [isLoaded, isAuthenticated, router, routes]);

    if (!isLoaded || !isAuthenticated) {
        return null;
    }

    return <BecomeHostStepper />;
}
