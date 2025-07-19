import { createCookieSessionStorage } from "@remix-run/node";

type SessionData = {
    userId: string;
};

type SessionFlashData = {
    error: string;
};

const { getSession, commitSession, destroySession } =
    createCookieSessionStorage<SessionData, SessionFlashData>(
        {
            cookie: {
                name: "__session",
                httpOnly: true,
                maxAge: 60 * 60 * 24 * 30,
                path: "/",
                sameSite: "lax",
                secrets: ['test'],
                secure: true,
            },
        }
    );

export { getSession, commitSession, destroySession };
