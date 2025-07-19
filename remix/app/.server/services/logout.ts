import {destroySession, getSession} from "../sessions/sessions.js";
import {redirect} from "@remix-run/react";

export async function logout({request}) {
    const session = await getSession(
        request.headers.get("Cookie")
    );

    return redirect("/settings", {
        headers: {
            "Set-Cookie": await destroySession(session)
        }
    });
}
