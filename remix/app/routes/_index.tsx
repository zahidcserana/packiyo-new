import {Card} from "react-bootstrap";

import {requireAuth} from "./../.server/services/auth.server.js";

export const loader = async ({request, params}) => {
    await requireAuth({request});

    return null;
};

export default function Index() {
    return (
        <Card className="mb-4">
            <Card.Header as="h6" className="py-3 bg-white">
                Welcome!
            </Card.Header>
            <Card.Body>
                <Card.Text>
                    Here you can adjust the settings with ease.
                </Card.Text>
            </Card.Body>
        </Card>
    );
}
