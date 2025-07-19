import React, {
    forwardRef,
    useImperativeHandle,
    useState,
    useRef,
} from "react";
import { Offcanvas } from "react-bootstrap";
import ArrowBack from "../Icon/ArrowBack";
import Close from "../Icon/Close";
import Tooltip from "../Tooltip/Tooltip";

interface SidebarProps {
    id?: string;
    title?: string;
    children: any;
    close?: boolean;
    placement?: "start" | "end" | "top" | "bottom"; // Adjust to match OffcanvasPlacement types
    idTooltip?: string;
}

export interface SidebarHandle {
    toggleSidebar: (value: boolean) => void;
    getBody: any; // Expose the body element directly
}

const Sidebar = forwardRef<SidebarHandle, SidebarProps>(
    ({ id, title, children, close = true, placement, idTooltip = "" }, ref) => {
        const [showSidebar, setShowSidebar] = useState<boolean>(false);
        const bodyRef = useRef<any>();

        useImperativeHandle(ref, () => ({
            toggleSidebar(value: any) {
                setShowSidebar(value);
            },
            getBody() {
                return bodyRef.current;
            },
        }));

        return (
            <Offcanvas
                show={showSidebar}
                onHide={() => setShowSidebar(false)}
                placement={placement}
            >
                <Offcanvas.Header
                    style={{
                        padding: "24px",
                        display: "flex",
                        justifyContent: "space-between",
                    }}
                >
                    {close ? (
                        <Close
                            onClick={() => setShowSidebar(false)}
                            style={{ cursor: "pointer" }}
                        />
                    ) : (
                        <ArrowBack
                            onClick={() => setShowSidebar(false)}
                            style={{ cursor: "pointer" }}
                        />
                    )}
                    {id && (
                        <Tooltip
                            onlyWrapper
                            bgDark
                            hideArrow
                            placement="left"
                            tooltipText={idTooltip}
                        >
                            <span
                                className="font-18 fw-semibold"
                                style={{
                                    padding: "12px 8px",
                                    borderRadius: "4px",
                                    background: "#eee",
                                    color: "#5e39cc",
                                }}
                            >
                                #{id}
                            </span>
                        </Tooltip>
                    )}
                </Offcanvas.Header>
                <Offcanvas.Body
                    ref={bodyRef} // Attach the ref to Offcanvas.Body
                    style={{
                        padding: "0 24px 24px",
                        display: "flex",
                        gap: "24px",
                        flexFlow: "column",
                        overflowY: "auto",
                    }}
                >
                    <h5 className="font-24 fw-semibold m-0">{title}</h5>
                    {children}
                </Offcanvas.Body>
            </Offcanvas>
        );
    }
);

export default Sidebar;
