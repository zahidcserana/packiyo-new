import React from 'react';

interface CloseProps extends React.HTMLAttributes<HTMLSpanElement> {
    className?: string;
}

export default function Close({className, ...rest}: CloseProps) {
    return (
        <span className={className} {...rest}>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"  {...rest}>
                <g id="x">
                    <path id="Vector 47" d="M4 20L20 4M4 4L20 20" stroke="black" strokeWidth="2" strokeLinecap="round"/>
                </g>
            </svg>
        </span>
    );
}
