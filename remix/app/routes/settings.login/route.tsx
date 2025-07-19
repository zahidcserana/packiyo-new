import {requireGuest, saveToken} from '../../.server/services/auth.server.js';
import {Link, useLoaderData, useSubmit} from '@remix-run/react';
import {Button, Card} from 'react-bootstrap';
import React, {useEffect, useState} from "react";
import axios from "axios";
import {toast} from "react-hot-toast";
import Page from "../../components/Layout/Page/Page";

export function meta() {
    return [
        {
            title: 'Login',
            description: '',
        },
    ];
}

export const loader = async ({request}) => {
    const apiHost = import.meta.env.VITE_API_HOST;
    const backendDomain = import.meta.env.VITE_BACKEND_DOMAIN;

    if (!apiHost) {
        throw new Error("API_HOST environment variable is not defined");
    }

    if (!backendDomain) {
        throw new Error("BACKEND_DOMAIN environment variable is not defined");
    }

    return {
        breadcrumbs: [
            { label: 'Login', to: '' }
        ],
        apiHost: apiHost,
        backendDomain: backendDomain
    }
};

export let action = async ({request}) => {

    const formData = await request.formData();
    const token = formData.get("token");

    return await saveToken({request, token});
};

const Login = () => {
    const {breadcrumbs, apiHost, backendDomain} = useLoaderData();
    const [token, setToken] = useState('');
    const submit = useSubmit();

    const fetchData = async () => {
        try {
            let response = await axios.get(apiHost + '/users/token', {
                withCredentials: true
            });

            setToken(response.data.meta.plain_text_token);
        } catch (error) {
            toast.error('Please login in to your dashboard and refresh page.', {position: 'top-right'});
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    useEffect(() => {
        if (token) {
            const formData = new FormData();
            formData.append("token", token);
            submit(formData, { method: "post" });
        }
    }, [token]);

    const refreshPage = () => {
        return fetchData();
    }

    return (
        <Page breadcrumbs={breadcrumbs}>
            <Card.Text>
                Login into your <Link to={backendDomain}>dashboard</Link> and refresh page.
            </Card.Text>
            <Button onClick={() => refreshPage()}>Refresh Page</Button>
        </Page>
    );
};

export default Login;
