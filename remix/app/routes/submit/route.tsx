import {ActionFunction} from "@remix-run/node";
import {destroyData, fetchData, saveAccountAndUserId} from "./../../.server/services/auth.server.js"
import {redirectBack} from "../../helpers/helpers";

export const action: ActionFunction = async ({ request }) => {
    const formData = await request.formData();
    const formType = formData.get("formType");

    if (formType === 'accounts-form') {
        const accountId = formData.get("accountId");

        if (accountId) {
            return await saveAccountAndUserId({request, newAccountId: accountId, newUserId: null});
        }
    }

    if (formType === 'delete-image-form') {
        const imageId = formData.get("image_id");

        await destroyData(request, 'images/' + imageId);
    }

    return redirectBack(request);
};

export default function Submit() {
    return null;
}
