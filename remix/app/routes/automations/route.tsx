import { Outlet } from "@remix-run/react";
import { ActionFunction } from "@remix-run/node";
import {
    updateAutomationPosition,
    updateAutomationStatus,
} from "~/.server/services/automations";
import { Toaster } from "~/components/ui/toaster";

export const action: ActionFunction = async ({ request, params }) => {
    const formData = await request.formData();

    switch (formData.get("action")) {
        case "update-automation-status": {
            return updateAutomationStatus({ request, formData });
        }
        case "update-automation-position": {
            return updateAutomationPosition({ request, formData });
        }
        default: {
            throw new Error("Unknown action");
        }
    }
};

const Automations = () => {
    return (
        <>
            <Outlet />
            <Toaster />
        </>
    );
};

export default Automations;
