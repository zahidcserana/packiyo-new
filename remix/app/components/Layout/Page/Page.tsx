import { PropsWithChildren } from "react";
import { Button, Card, Col, Row } from "react-bootstrap";
import Breadcrumbs from "../Breadcrumbs/Breadcrumbs";

interface PageProps extends PropsWithChildren{
    breadcrumbs: Record<string, string>[];
    headerClasses?: string;
    bodyClasses?: string;
    handleCreateClick?: (event: React.MouseEvent<HTMLButtonElement>) => void;
}

const Page = ({children, breadcrumbs, headerClasses = 'd-flex justify-content-between align-items-center bg-white py-3', bodyClasses = '', handleCreateClick}: PageProps) => {
    return (
        <Card className="mb-4">
            <Card.Header className={headerClasses} as="h6">
                <Breadcrumbs breadcrumbs={breadcrumbs}/>
                {handleCreateClick && <Row><Col><Button onClick={handleCreateClick}>Create</Button></Col></Row>}
            </Card.Header>
            <Card.Body className={bodyClasses}>
                {children}
            </Card.Body>
        </Card>
    );
};

export default Page;
