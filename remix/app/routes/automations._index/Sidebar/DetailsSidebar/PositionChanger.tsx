import { Button, Col, Row } from "react-bootstrap";
import React from "react";
import { Automation } from "~/types/automations";
import { useFetcher, useSubmit } from "@remix-run/react";
import ArrowDown from "../../../../components/Icon/ArrowDown";
import ArrowUp from "../../../../components/Icon/ArrowUp";

interface PositionChangerProps {
    automation: Automation;
}

const PositionChanger = ({ automation }: PositionChangerProps) => {
    const fetcher = useFetcher();

    const increasePosition = (automation: Automation) => {
        let position = Number(automation.position) + 1;
        const formData = new FormData();
        formData.set("action", "update-automation-position");
        formData.set("id", automation.id);
        formData.set("position", String(position));
        fetcher.submit(formData, { method: "post", action: "/automations" });
    };

    const decreasePosition = (automation: Automation) => {
        if (automation.position === 1) {
            return;
        }

        let position = Number(automation.position) - 1;
        const formData = new FormData();
        formData.set("action", "update-automation-position");
        formData.set("id", automation.id);
        formData.set("position", String(position));
        fetcher.submit(formData, { method: "post", action: "/automations" });
    };

    return (
        <Row style={{ maxWidth: "calc(1.5rem + 166px)" }}>
            <Col className="pe-0">
                <Button
                    disabled={fetcher.state !== "idle"}
                    className="btn-purple-darker-purple w-100 p-[1px] flex justify-center items-center"
                    style={{
                        display: "flex",
                        padding: "1px",
                        borderRadius: "5px 0 0 5px",
                    }}
                    onClick={() => increasePosition(automation)}
                >
                    <ArrowDown />
                </Button>
            </Col>
            <Col
                className="d-flex justify-content-center align-items-center font-13"
                style={{ background: "#eee" }}
            >
                {automation?.order}
            </Col>
            <Col className="ps-0">
                <Button
                    disabled={fetcher.state !== "idle"}
                    className="btn-purple-darker-purple w-100 justify-center items-center"
                    style={{
                        display: "flex",
                        padding: "1px",
                        borderRadius: "0 5px 5px 0",
                    }}
                    onClick={() => decreasePosition(automation)}
                >
                    <ArrowUp />
                </Button>
            </Col>
        </Row>
    );
};

export default PositionChanger;
