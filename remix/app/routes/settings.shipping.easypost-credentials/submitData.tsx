import {currentAccountId, destroyData, updateData, createData} from "./../../.server/services/auth.server.js"
import {prepareFormData, redirectBack} from "../../helpers/helpers";

export const deleteCredential = async ({ request, formData }) => {
    const id = formData.get('id');

    await destroyData(request, 'easypost-credentials/' + id);

    return redirectBack(request);
};

export const updateCredential = async ({ request, formData }) => {
    const currentAccount = await currentAccountId({ request });

    const id = formData.get('id');

    formData.append('customer_id', currentAccount);

    const data = prepareFormData(formData, "easypost-credentials");

    await updateData(request, 'easypost-credentials/' + id, data);

    return redirectBack(request);
};

export const createCredential = async ({ request, formData }) => {
    const currentAccount = await currentAccountId({ request });

    formData.append('customer_id', currentAccount);

    const data = prepareFormData(formData, "easypost-credentials");

    await createData(request, 'easypost-credentials', data);

    return redirectBack(request);
};
