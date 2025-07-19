import { Button, Col, Row } from "react-bootstrap";
import React from "react";
import { Automation } from "~/types/automations";
import { ClientAction } from "../../Table/Column/NameColumn";
import ToggleSwitch from "../../../../components/ToggleSwitch";
import EditPencil from "../../../../components/Icon/EditPencil";
import { useNavigate } from "@remix-run/react";
import Tooltip from "../../../../components/Tooltip/Tooltip";
import PositionChanger from "./PositionChanger";

interface SummaryProps {
    showClientsSidebar: any;
    automation: Automation;
}

const Summary = ({ showClientsSidebar, automation }: SummaryProps) => {
    const navigate = useNavigate();

    const Events = (automation: Automation) => {
        return <p className="font-16 mb-0">{automation?.events}</p>;
    };

    const Conditions = (automation: Automation) => {
        const conditions = automation?.conditions ?? [];

        return (
            <>
                {conditions.map((trigger, index) => {
                    return (
                        <React.Fragment key={index}>
                            <p className="font-16 mb-0">
                                {trigger?.attributes?.description}
                            </p>
                            {index < conditions.length - 1 && (
                                <p className="font-16 mb-0">and</p>
                            )}
                        </React.Fragment>
                    );
                })}
            </>
        );
    };

    const Actions = (automation: Automation) => {
        const actions = automation?.actions ?? [];

        return (
            <>
                {actions.map((action, index) => {
                    return (
                        <React.Fragment key={index}>
                            <p className="font-16 mb-0">
                                {action?.attributes?.description}
                            </p>
                            {index < actions.length - 1 && (
                                <p className="font-16 mb-0">and</p>
                            )}
                        </React.Fragment>
                    );
                })}
            </>
        );
    };

    const navigateToAutomation = () => {
        navigate(`/automations/edit/${automation.id}?showClients=true`);
    };

    return (
        <div>
            <Row>
                {automation?.createdBy && (
                    <p className="font-13 mb-2">
                        Created by {automation?.createdBy}
                    </p>
                )}
                {automation?.lastEditted && (
                    <p className="font-13">
                        Last edited {automation?.lastEditted} by{" "}
                        {automation?.lastEdittedBy}
                    </p>
                )}
            </Row>
            <Row className="mt-4 mb-3">
                <Col className="font-18 fw-semibold d-flex align-items-center">
                    Position
                    <Tooltip
                        tooltipText="You can change the automation order. If an order meets the conditions of two or more automations, the last one will override the previous ones."
                        placement="top"
                    />
                </Col>
                <Col className="font-18 fw-semibold d-flex align-items-center">
                    Last triggered
                </Col>
                <Col className="font-18 fw-semibold d-flex align-items-center">
                    Clients
                    <Tooltip
                        tooltipText="Clients or brands for which the automation is being applied."
                        placement="top"
                    />
                </Col>
            </Row>
            <Row className="align-items-center  mb-4">
                <Col className="font-13">
                    <PositionChanger automation={automation} />
                </Col>
                <Col className="font-13">
                    Last triggered{automation?.lastEdittedBy}
                </Col>
                <Col className="font-13">
                    {automation?.clientsList.length > 0 && (
                        <ClientAction
                            onClientClick={showClientsSidebar}
                            clientList={automation?.clientsList}
                        />
                    )}
                </Col>
            </Row>
            <Row>
                <Col className="font-20 d-flex align-items-center fw-semibold mb-3">
                    Summary
                </Col>
                <Col className="mb-3 d-flex justify-content-end gap-1">
                    <Button
                        variant="transparent"
                        size="sm"
                        onClick={navigateToAutomation}
                        className="align-items-center"
                    >
                        Edit <EditPencil />
                    </Button>
                    <ToggleSwitch
                        optionLabels={["Active", "Inactive"]}
                        id={automation?.id}
                        checked={automation?.isEnabled}
                        onChange={() => null}
                    />
                </Col>
            </Row>
            <Row>
                <Col sm={12}>
                    <div
                        style={{
                            padding: "16px",
                            background: "#eee",
                            borderRadius: "8px",
                        }}
                    >
                        <h6 className="font-18 fw-semibold">When (Event)</h6>
                        {Events(automation)}
                    </div>
                </Col>
                <Col sm={12} className="d-flex justify-content-center">
                    <div className="vertical-separator"></div>
                </Col>
                <Col sm={12}>
                    <div
                        style={{
                            padding: "16px",
                            background: "#eee",
                            borderRadius: "8px",
                        }}
                    >
                        <h6 className="font-18 fw-semibold">If (Condition)</h6>
                        {Conditions(automation)}
                    </div>
                </Col>
                <Col sm={12} className="d-flex justify-content-center">
                    <div className="vertical-separator"></div>
                </Col>
                <Col sm={12}>
                    <div
                        style={{
                            padding: "16px",
                            background: "#eee",
                            borderRadius: "8px",
                        }}
                    >
                        <h6 className="font-18 fw-semibold">Then (Action)</h6>
                        {Actions(automation)}
                    </div>
                </Col>
            </Row>
        </div>
    );
};

export default Summary;
