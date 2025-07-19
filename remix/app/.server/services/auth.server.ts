import { redirect } from "@remix-run/react";
import { prepareErrorResponse, prepareSuccessResponse } from "~/helpers/helpers";
import { commitSession, getSession } from "./../sessions/sessions.ts";
import axios from "./axios.server";

export async function currentToken({request}) {
    const session = await getSession(
        request.headers.get("Cookie")
    );

    return session.get("packiyoAppToken");
}

export async function user({request}) {
    const token = await currentToken({request});

    try {
        const response = await axios.get('/users/me?include=contact_information', {
            headers: {
                "Authorization": "Bearer " + token,
            }
        })

        return response.data;
    } catch (error) {
        return null;
    }
}

export async function requireGuest({request}) {
    if (await user({request})) {
        throw redirect("/logout");
    }

    return null;
}

export async function requireAuth({request}) {
    const token = await currentToken({request});

    if (!token) {
        throw redirect("/logout");
    }

    return null;
}

export async function destroyData(request, path) {
    const token = await currentToken({request});

    try {
        const response = await axios.delete(path, {
            headers: { Authorization: `Bearer ${token}` },
        });

        return response.data;
    } catch (error) {
        return prepareErrorResponse(error);
    }
}

export async function createData(request, path, data) {
    const token = await currentToken({request});

    try {
        const response = await axios.post(path, data,{
            headers: {
                "Content-Type": "application/vnd.api+json",
                "Authorization": "Bearer " + token
            }
        })

        return prepareSuccessResponse(response.data);
    } catch (error) {
        return prepareErrorResponse(error);
    }
}

export async function fetchData(request, path, config?) {
    const token = await currentToken({request});
    const customConfig = config?.isAutomation? { baseURL: config.baseURL} : {};

    try {
        const response = await axios.get(path,{
            headers: {
                "Authorization": "Bearer " + token
            },
            ...customConfig
        })

        return response.data;
    } catch (error) {
        return prepareErrorResponse(error);
    }
}

export async function postData(request, path, data = {}) {
    const token = await currentToken({request});

    try {
        const response = await axios.post(path, data,{
            headers: {
                "Content-Type": "multipart/form-data",
                "Authorization": "Bearer " + token
            }
        })

        return prepareSuccessResponse(response.data);
    } catch (error) {
        return prepareErrorResponse(error);
    }
}

export async function uploadFile(request, path, data) {
    const token = await currentToken({request});

    try {
        const response = await axios.post(path, data,{
            headers: {
                "Content-Type": "multipart/form-data",
                "Authorization": "Bearer " + token
            }
        })

        return prepareSuccessResponse(response.data);
    } catch (error) {
        return prepareErrorResponse(error);
    }
}

export async function updateData(request, path, data, config?) {
    const token = await currentToken({request});
    const customConfig = config?.isAutomation? { baseURL: config.baseURL}:{};

    try {
        const response = await axios.patch(path, data,{
            headers: {
                "Content-Type": "application/vnd.api+json",
                "Authorization": "Bearer " + token
            },
            ...customConfig
        })

        return prepareSuccessResponse(response.data);
    } catch (error) {
        return prepareErrorResponse(error);
    }
}

export async function deleteData(request, path, data, config?) {
    const token = await currentToken({request});
    const customConfig = config?.isAutomation? { baseURL: config.baseURL}:{};

    try {
        const response = await axios.delete(path, {
            headers: {
                "Content-Type": "application/vnd.api+json",
                "Authorization": "Bearer " + token
            },
            data,
            ...customConfig
        })

        return prepareSuccessResponse(response.data);
    } catch (error) {
        return prepareErrorResponse(error);
    }
}

export async function saveToken({request, token}){
    const session = await getSession(
        request.headers.get("Cookie")
    );

    session.set("packiyoAppToken", token);

    return redirect("/settings", {
        headers: {
            "Set-Cookie": await commitSession(session)
        }
    });
}

export async function saveAccountAndUserId({request, newAccountId, newUserId}){
    const session = await getSession(
        request.headers.get("Cookie")
    );

    session.set("accountid", newAccountId);

    if (newUserId) {
        session.set("userid", newUserId);
    }

    return redirect("/settings", {
        headers: {
            "Set-Cookie": await commitSession(session)
        }
    });
}

export async function currentAccountId({request}) {
    const session = await getSession(
        request.headers.get("Cookie")
    );

    return session.get("accountid");
}

export async function currentUserId({request}) {
    const session = await getSession(
        request.headers.get("Cookie")
    );

    return session.get("userid");
}
