import { getAccountImages } from "~/.server/services/settings";
import faviconImage from "./../../images/favicon.png";
import logoImage from "./../../images/logo.png";
import { currentAccountId, currentToken, saveAccountAndUserId, user } from "./auth.server.js";

interface UserData {
    id: string;
    name: string;
    email: string;
    picture: string;
    accounts: any[];
    customer_user_roles: any[];
    favicon_src: any;
    logo_src: any;
    current_account_id: string | undefined;
    backend_domain: string;
    is_super_admin: boolean;
    is_admin: boolean;
    is_3pl: boolean;
    is_standalone: boolean;
}

export const getUser = async ({ request }) => {
    const backendDomain = import.meta.env.VITE_BACKEND_DOMAIN;

    const [accountId, token] = await Promise.all([
        currentAccountId({ request }),
        currentToken({ request }),
    ]);

    const userData: UserData = {
        id: '',
        name: '',
        email: '',
        picture: '',
        accounts: [],
        customer_user_roles: [],
        favicon_src: faviconImage,
        logo_src: logoImage,
        current_account_id: accountId,
        backend_domain: backendDomain,
        is_super_admin: false,
        is_admin: false,
        is_3pl: false,
        is_standalone: false,
    };

    if (token) {
        const userResponse = await user({ request });

        if (userResponse) {
            const { data: userDataResponse } = userResponse;

            userData.id = userDataResponse.id;
            userData.email = userDataResponse.attributes.email;
            userData.picture = userDataResponse.attributes.picture;
            userData.is_super_admin = !!userDataResponse.attributes.is_super_admin;
            userData.is_admin = !!userDataResponse.attributes.is_admin;

            const customers = userDataResponse.attributes.accounts;
            const contactInformations = userResponse.included?.filter((data) => data.type === 'contact-informations') ?? [];

            userData.name = processUserName(contactInformations);
            userData.accounts = processCustomerAccounts(customers);
            userData.customer_user_roles = userDataResponse.attributes.customer_user_roles;
        }
    }

    if (userData.id) {
        if (!accountId && userData.accounts.length > 0) {
            const newUserId = userData.id
            const newAccountId = userData.accounts[0].id
            userData.current_account_id = newAccountId;
            return await saveAccountAndUserId({ request, newAccountId, newUserId });
        }

        if (accountId && !userData.is_admin) {
            userData.is_admin = await processUserAdminStatus(
                accountId,
                userData.customer_user_roles,
                userData.accounts
            )
        }

        const {account_logo, account_favicon} = await getAccountImages({request});

        userData.favicon_src = account_favicon?.source ?? faviconImage;
        userData.logo_src = account_logo?.source ?? logoImage;

        userData.is_3pl = !!userData.accounts.find(
            (account) => account.id == accountId && account.isThreePl
        );

        userData.is_standalone = !!userData.accounts.find(
            (account) => account.id == accountId && account.isStandalone
        );
    }

    return userData;
};

function processUserName(contactInformations) {
    const contactInformation = contactInformations[0];
    return contactInformation?.attributes?.name ?? '';
}

function processCustomerAccounts(customers) {
    return customers.map(customer => ({
        id: customer.id,
        parentId: customer.parent_id,
        name: customer.contact_information.name,
        isThreePlChild: !!customer.parent_id,
        isStandalone: !customer.allow_child_customers && !customer.parent_id,
        isThreePl: !!customer.allow_child_customers,
    }));
}

interface CustomerUserRole {
    id: number;
    customer_id: number;
    user_id: number;
    role_id: number;
}

async function processUserAdminStatus(currentAccountId, customerUserRoles, accounts) {
    const userIsAdminInCustomers = [];

    for (const customerUserRole: CustomerUserRole of customerUserRoles) {
        if (customerUserRole.role_id == 1) {
            userIsAdminInCustomers.push(customerUserRole.customer_id);
        }
    }

    const currentAccount = accounts.find(account => account.id == currentAccountId);

    return userIsAdminInCustomers.includes(currentAccount.id) || userIsAdminInCustomers.includes(currentAccount.parentId)
}
