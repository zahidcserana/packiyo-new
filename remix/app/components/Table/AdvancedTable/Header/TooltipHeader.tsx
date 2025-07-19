import React, { CSSProperties } from "react";
import Tooltip from "../../../Tooltip/Tooltip";

interface TooltipHeaderProps {
    label: string;
    tooltipText: string;
    tooltipPlacement: "top" | "right" | "bottom" | "left";
    center?: boolean;
}

const TooltipHeader = ({
    label,
    tooltipText,
    tooltipPlacement,
    center,
}: TooltipHeaderProps) => {
    let inlineStyle: CSSProperties = {
        display: "flex",
        width: "auto",
        textAlign: "left",
        alignItems: "center",
    };

    if (center) {
        inlineStyle = {
            ...inlineStyle,
            width: "100%",
            justifyContent: "center",
            textAlign: "center",
        };
    }

    return (
        <span style={inlineStyle}>
            {label}
            <Tooltip
                tooltipText={tooltipText}
                placement={tooltipPlacement}
                light={true}
            />
        </span>
    );
};

export default TooltipHeader;
