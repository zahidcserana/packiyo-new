import {redirect} from '@remix-run/react';
import {destroySession, getSession} from "../../.server/sessions/sessions";

export const loader = async ({request}) => {
    const session = await getSession(
        request.headers.get("Cookie")
    );

    session.unset('userId');
    session.unset('accountId');
    session.unset('packiyoAppToken');

    return redirect("/settings/login", {
        headers: {
            "Set-Cookie": await destroySession(session)
        }
    });
};
