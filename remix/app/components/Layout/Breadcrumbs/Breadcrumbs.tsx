import { Link } from "@remix-run/react";
import { IoChevronForwardOutline } from "react-icons/io5";

interface BreadcrumbsProps {
    breadcrumbs: any;
    large?: boolean;
}

const Breadcrumbs = ({ breadcrumbs, large }: BreadcrumbsProps) => {
    let inlineStyle: any = {
        textDecoration: "none",
    };

    let inlineStyleLink: any = {
        textDecoration: "none",
    };

    if (large) {
        inlineStyle = {
            textDecoration: "none",
            fontSize: "28px",
            lineHeight: "32px",
            fontWeight: "600",
        };

        inlineStyleLink = {
            textDecoration: "none",
            fontSize: "28px",
            lineHeight: "32px",
            fontWeight: "300",
        };
    }

    return (
        <div className="flex breadcrumbs align-items-center flex-row">
            {breadcrumbs.map(
                (breadcrumb: { label: string; to: string }, index: number) => (
                    <div className="flex items-center" key={index}>
                        {index < breadcrumbs.length - 1 ? (
                            <Link style={inlineStyleLink} to={breadcrumb.to}>
                                {breadcrumb.label}
                            </Link>
                        ) : (
                            <span style={inlineStyle}>{breadcrumb.label}</span>
                        )}
                        {index < breadcrumbs.length - 1 && (
                            <span className="mx-1" style={inlineStyle}>
                                <IoChevronForwardOutline
                                    size={large ? 24 : 16}
                                />
                            </span>
                        )}
                    </div>
                )
            )}
        </div>
    );
};

export default Breadcrumbs;
