import {
    Link,
    Links,
    Meta,
    Outlet,
    Scripts,
    ScrollRestoration, useNavigate,
    useRouteError, useRouteLoaderData,
} from "@remix-run/react";

import { Toaster } from "react-hot-toast";
import Favicon from "./components/Layout/Favicon/Favicon";
import Layout from "./components/Layout/Layout.tsx";
import "./styles/tailwind.css";
import "./styles/theme.scss";

import { LinksFunction } from "@remix-run/node";
import { useEffect } from "react";
import { Card, Container } from "react-bootstrap";
import { useLocation } from "react-router";
import { getUser } from "./.server/services/user.js";
import LoadingBar from "./components/Layout/LoadingBar/LoadingBar";
import faviconImage from "./images/favicon.png";

export const loader = async ({ request }) => {
    return await getUser({ request });
};

export function useRootLoaderData() {
    return useRouteLoaderData<typeof loader>("root")
}

export default function App() {
    return (
        <Document>
            <LoadingBar/>
            <Toaster />
            <Layout>
                <Outlet/>
            </Layout>
        </Document>
    );
}

export function ErrorBoundary() {
    const error = useRouteError();

    return (
        <html>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
            <link rel="preconnect" href="https://fonts.googleapis.com"/>
            <link rel="preconnect" href="https://fonts.gstatic.com"/>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet"/>
            <Meta/>
            <Favicon src={faviconImage}/>
            <Links/>
        </head>
        <body>
            <Container className="vh-100 d-flex align-items-center justify-content-center ">
                    <Card className="mx-4">
                        <Card.Header as="h6" className="py-3 bg-white">
                            Oops!
                        </Card.Header>
                        <Card.Body>
                            <Card.Text>
                                {error?.isAuthError ? 'Unauthenticated.' : error.message ?? error.data}
                            </Card.Text>
                            <Link className="btn btn-primary" to={"/"}>Go Back to the Home Page</Link>
                        </Card.Body>
                    </Card>
                </Container>
            <ScrollRestoration/>
            <Scripts/>
        </body>
        </html>
    )
}

export const links: LinksFunction = () => [
    {
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap',
    },
  ]

function Document({children}) {
    const user = useRootLoaderData();
    const navigate = useNavigate();
    const location = useLocation();

    useEffect(() => {
        if (!user.id && location.pathname !== '/settings/login') {
            navigate("/logout");
        }
    }, [location]);

    return (
        <html>
            <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
                <Meta/>
                <Favicon src={user.favicon_src}/>
                <Links/>
            </head>
            <body>
                {children}
                <ScrollRestoration/>
                <Scripts />
            </body>
        </html>
    );
}
