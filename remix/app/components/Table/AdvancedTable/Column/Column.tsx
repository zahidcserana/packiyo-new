import { CSSProperties } from "react";

interface ColumnProps {
    label: string;
    center?: boolean;
    addStyle?: CSSProperties;
}

const Column = ({ label, center, addStyle = {} }: ColumnProps) => {
    let inlineStyle: CSSProperties = {
        display: "block",
        width: "auto",
        textAlign: "left",
    };

    if (center) {
        inlineStyle = { ...inlineStyle, width: "100%", textAlign: "center" };
    }
    inlineStyle = { ...inlineStyle, ...addStyle };

    return <span style={inlineStyle}>{label}</span>;
};

export default Column;
