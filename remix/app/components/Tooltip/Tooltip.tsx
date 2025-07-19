import React, { useState, useEffect } from "react";
import { Tooltip as BSTooltip, OverlayTrigger } from "react-bootstrap";
import { BsQuestionCircle } from "react-icons/bs";
import { Button } from "react-bootstrap";

interface TooltipProps {
    tooltipText: string;
    placement?: "top" | "right" | "bottom" | "left";
    delay?: number;
    light?: boolean;
    onlyWrapper?: boolean;
    bgDark?: boolean;
    hideArrow?: boolean;
    children: React.ReactNode;
}

const Tooltip: React.FC<TooltipProps> = ({
    tooltipText,
    placement = "top",
    delay = 0,
    light = false,
    onlyWrapper,
    children,
    bgDark,
    hideArrow,
}) => {
    const [showTooltip, setShowTooltip] = useState(false);

    const handleOpenTooltip = () => {
        setShowTooltip(true);
    };
    const handleCloseTooltip = () => {
        setShowTooltip(false);
    };

    useEffect(() => {
        let timer: number | null = null;

        if (showTooltip) {
            timer = window.setTimeout(() => {
                setShowTooltip(false);
            }, 10000);
        }

        return () => {
            if (timer) {
                clearTimeout(timer);
            }
        };
    }, [showTooltip]);

    const renderTooltip = (props: any) => {
        const newProps = hideArrow
            ? { arrowProps: { style: { display: "none" } } }
            : {};
        return (
            <BSTooltip
                id="tooltip"
                {...props}
                {...newProps}
                className={`custom-tooltip ${bgDark && "tooltipBlack"}`}
            >
                <div className="d-flex justify-content-between align-items-center">
                    <span>{tooltipText}</span>
                </div>
            </BSTooltip>
        );
    };

    return (
        <div
            onMouseLeave={handleCloseTooltip}
            style={onlyWrapper ? {} : { lineHeight: 0 }}
        >
            <OverlayTrigger
                placement={placement}
                show={showTooltip}
                delay={{ show: delay, hide: 100 }}
                overlay={renderTooltip}
            >
                {onlyWrapper ? (
                    <span onMouseEnter={handleOpenTooltip}>{children}</span>
                ) : (
                    <Button
                        variant="link"
                        className="p-0 ms-2"
                        onMouseEnter={handleOpenTooltip}
                        style={{ lineHeight: 0 }}
                    >
                        <BsQuestionCircle
                            size={16}
                            style={{ color: light ? "white" : "#222" }}
                        />
                    </Button>
                )}
            </OverlayTrigger>
        </div>
    );
};

export default Tooltip;
