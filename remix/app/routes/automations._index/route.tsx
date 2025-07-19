import type { LoaderFunctionArgs } from "@remix-run/node";
import {useLoaderData} from "@remix-run/react";
import { requireAuth } from '~/.server/services/auth.server';
import { getAutomations } from "~/.server/services/automations";
import AutomationsTable from "./Table/AutomationsTable";
import Breadcrumbs from "../../components/Layout/Breadcrumbs/Breadcrumbs";
import React from "react";

export const loader = async ({request}: LoaderFunctionArgs) => {
    await requireAuth({ request });

    const url = new URL(request.url)
    const currentPage = url.searchParams.get("currentPage") ?? "1";
    const sort = url.searchParams.get("sort") ?? "";

    const {automations, page} = await getAutomations({request, currentPage, sort});

    return {
        breadcrumbs: [
            { label: 'Co-pilot Automations', to: '/automations' },
        ],
        automations,
        page,
    }
};


const Automations = () => {
    const {automations, page, breadcrumbs} = useLoaderData<typeof loader>();

    return (
        <>
            <Breadcrumbs breadcrumbs={breadcrumbs} large={true}/>
            <div className="mt-2 mb-3">
                <h4 className="font-18">Save time with automations, streamlining operations and boosting efficiency.</h4>
                <h4 className="font-18 fw-semibold mt-4">Your automations</h4>
            </div>
            <AutomationsTable automations={automations} page={page}/>
        </>
    )
}

export default Automations;

