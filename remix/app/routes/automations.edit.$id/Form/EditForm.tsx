import React, { useEffect, useState } from "react";
import { Button, Card, Col, Form, Row } from "react-bootstrap";
import EditPencil from "../../../components/Icon/EditPencil";
import { Automation } from "~/types/automations";
import { ClientAction } from "../../automations._index/Table/Column/NameColumn";
import { useActionData, useNavigate, useSubmit } from "@remix-run/react";
import { useToast } from "~/hooks/use-toast";

interface EditFormProps {
    automation: Automation;
    selectedClients: any;
    sidebarAction: () => void;
    clients?: any;
}

const EditForm = ({
    automation,
    selectedClients,
    sidebarAction,
    clients,
}: EditFormProps) => {
    const [appliesTo, setAppliesTo] = useState<string | undefined>(
        automation.appliesTo
    );
    const navigate = useNavigate();
    const submit = useSubmit();
    const action = useActionData<any>();
    const { toast } = useToast();

    const handleFormSubmit = async (event: any) => {
        try {
            const selectedClientsForm =
                appliesTo === "all" ? [] : selectedClients;
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append("id", automation.id);
            formData.append("action", "update-automation-clients");
            formData.append("applies_to", String(automation.appliesTo));
            formData.append("customers", JSON.stringify(automation.clients));
            formData.append(
                "selected_customers",
                JSON.stringify(selectedClientsForm)
            );
            submit(formData, { method: "post" });
        } catch (e) {
            console.error(e);
        }
    };

    useEffect(() => {
        if (action) {
            if (action?.success) {
                navigate("/automations");
                toast({
                    description: "Updated successfully",
                });
            } else {
                toast({
                    variant: "destructive",
                    description: action?.message || "Unable to save",
                });
            }
        }
    }, [action]);

    const ClientsButton = () => {
        if (!selectedClients?.length || appliesTo === "all") {
            return;
        }
        const selectedClientsNames = selectedClients?.map((c: string) => {
            return (
                (automation?.clients || [])?.find(
                    (automationClient) => automationClient.id === c
                )?.name ||
                (clients || [])?.find((client: any) => client.id === c)?.name ||
                c
            );
        });

        return (
            <Col xs="auto">
                <ClientAction
                    onClientClick={() => null}
                    clientList={selectedClientsNames}
                />
            </Col>
        );
    };

    const EditListButton = () => {
        if (appliesTo === "all") {
            return;
        }

        return (
            <Col xs="auto">
                <Button variant="transparent" size="sm" onClick={sidebarAction}>
                    Edit list <EditPencil />
                </Button>
            </Col>
        );
    };

    return (
        <Form
            onSubmit={(event) => {
                handleFormSubmit(event);
            }}
        >
            <Card className="mt-4 mb-4" style={{ maxWidth: "704px" }}>
                <Card.Body className="p-4">
                    <Card.Title className="fw-semibold mb-4 text-5xl">
                        Automation #{automation.id}
                    </Card.Title>
                    <Row className="align-items-center">
                        <Col xs="12">
                            <Form.Group>
                                <Form.Label>Name</Form.Label>
                                <Form.Control
                                    type="text"
                                    name="name"
                                    placeholder="Automation Name"
                                    defaultValue={automation.name}
                                />
                            </Form.Group>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>

            <Card className="mt-4 mb-4" style={{ maxWidth: "704px" }}>
                <Card.Body className="p-4">
                    <Card.Title className="fw-semibold mb-4">
                        Select Clients
                    </Card.Title>
                    <Card.Text className="mb-4">
                        For which clients would you like this automation to be
                        applied?
                    </Card.Text>
                    <Row className="align-items-center">
                        <Col xs="auto">
                            <Form.Check
                                name="applies_all"
                                checked={appliesTo === "all"}
                                type="radio"
                                label="All"
                                onChange={() => {
                                    setAppliesTo("all");
                                }}
                            />
                        </Col>
                        <Col xs="auto">
                            <Form.Check
                                name="applies_some"
                                checked={appliesTo === "some"}
                                type="radio"
                                label="From a list"
                                onChange={() => {
                                    setAppliesTo("some");
                                }}
                            />
                        </Col>
                        <ClientsButton />
                        <EditListButton />
                    </Row>
                </Card.Body>
            </Card>

            <Button type="submit">Save changes</Button>
        </Form>
    );
};

export default EditForm;
