import {currentAccountId, fetchData, postData} from "./auth.server.js";

export const getOrderChannel = async ({ request, params }) => {
    const response = await fetchData(request, `order-channels/${params.id}/order-channel`);

    return response.data;
};

export const updateSourceConfiguration = async ({ request, params , formData }) => {
    return await postData(request, `order-channels/${params.id}/update-source-configuration`, formData);
};

export const recreateOrderChannelWebhooks = async ({ request, params , formData }) => {
    return await postData(request, `order-channels/${params.id}/recreate-order-channel-webhooks`, formData);
};

export const createPackiyoWebhook = async ({ request, params , formData }) => {
    const type = formData.get('object_type');
    const operation = formData.get('operation');

    return await postData(request, `order-channels/${params.id}/create-packiyo-webhooks/${type}/${operation}`, formData);
};

export const scheduleSync = async ({ request, params , formData }) => {
    const operation = formData.get('operation');

    return await postData(request, `order-channels/${params.id}/${operation === '1' ? 'enable-scheduler' : 'disable-scheduler'}`, formData);
};

export const syncProducts = async ({ request, params , formData }) => {
    return await postData(request, `order-channels/${params.id}/sync-products`);
};

export const syncProductById = async ({ request, params , formData }) => {
    const productId = formData.get('product_id');

    return await postData(request, `order-channels/${params.id}/sync-product-by-product-id/${productId}`, formData);
};

export const syncProductBySku = async ({ request, params , formData }) => {
    const productSku = formData.get('product_sku');

    return await postData(request, `order-channels/${params.id}/sync-product-by-product-sku/${productSku}`, formData);
};

export const syncInventories = async ({ request, params , formData }) => {
    return await postData(request, `order-channels/${params.id}/sync-inventories`, formData);
};

export const syncOrderByNumber = async ({ request, params , formData }) => {
    const number = formData.get('order_number');

    return await postData(request, `order-channels/${params.id}/sync-order-by-number/${number}`, formData);
};

export const syncOrdersByDate = async ({ request, params , formData }) => {
    const dateFrom = `${formData.get('date_from')} 00:00:00`;

    return await postData(request, `order-channels/${params.id}/sync-orders-by-date/${dateFrom}`, formData);
};

export const removeOrderChannelWebhook = async ({ request, params , formData }) => {
    const id = formData.get('id');

    return await postData(request, `order-channels/${params.id}/remove-order-channel-webhook/${id}`, formData);
};

export const syncShipments = async ({ request, params , formData }) => {
    const dateFrom = formData.get('date_from');

    return await postData(request, `order-channels/${params.id}/sync-shipments/${dateFrom}`, formData);
};

export const checkConnectionName = async ({ request, params , formData }) => {
    const accountId = await currentAccountId({request});
    const name = formData.get('name');

    const response = await postData(request, `order-channels/check-name/${accountId}/${name}`, formData);

    if (response.success) {
        return {
            success: false,
            message: 'Connection with this name already exists.'
        }
    } else {
        const redirectUrl = await getOauthUrl({ request, params , formData });

        if (redirectUrl) {
            return {
                success: true,
                message: 'Connecting..',
                redirectUrl: redirectUrl
            }
        }

        return {
            success: false,
            message: 'You already connected the shop.'
        }
    }
};

export const getOauthUrl = async ({ request, params , formData }) => {
    const response = await postData(request, `order-channels/get-oauth-url`, formData);

    if (response.success) {
        return response.data.url;
    }

    return null;
};

export const connect = async ({ request, params , formData }) => {
    return await postData(request, `order-channels/connect`, formData);
};
