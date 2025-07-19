import { Card, FormLabel, FormSelect, FormGroup } from 'react-bootstrap';
import {Form, Link} from "@remix-run/react";
import {useRootLoaderData} from "../../../root";

const AccountsMenu = () => {
    const {
        accounts,
        current_account_id: currentAccountId,
        is3pl
    } = useRootLoaderData();

    return (
        <div className="accounts-menu">
            <Card>
                <Card.Header className={is3pl ? 'bg-white py-2 d-flex justify-content-between align-items-center' : 'bg-white py-3 d-flex justify-content-between align-items-center'} as="h6">
                    Accounts
                    {is3pl && <Link className="btn btn-secondary" to="/settings/account/create">
                        Add Account
                    </Link>}
                </Card.Header>
                <Card.Body>
                    <Form action="/submit" method="post">
                        <input name="formType" type="hidden" value="accounts-form"/>
                        <FormGroup>
                            <FormLabel htmlFor="accounts">Select Account</FormLabel>
                            <FormSelect
                                name="accountId"
                                value={currentAccountId}
                                onChange={(e) => {
                                    e.currentTarget.form.submit();
                                }}
                            >
                                {accounts?.map((account, index) => (
                                    <option key={index} value={account.id}>
                                        {account.name}
                                    </option>
                                ))}
                            </FormSelect>
                        </FormGroup>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
};

export default AccountsMenu;
