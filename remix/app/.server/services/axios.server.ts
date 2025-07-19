import axios from "axios";

const client = axios.create({
    baseURL: import.meta.env.VITE_API_HOST,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        "Content-Type": "application/json",
    },
    withCredentials: true
});

client.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            return Promise.reject({ isAuthError: true });
        }

        return Promise.reject(error)
    },
)

export default client;
