import React from 'react';

interface ArrowBackProps extends React.HTMLAttributes<HTMLSpanElement> {
    className?: string;
}

export default function ArrowUp({className, ...rest}: ArrowBackProps) {
    return (
        <span className={className} {...rest}>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none">
              <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M11.468 7.46888C11.5377 7.39903 11.6204 7.34362 11.7115 7.30581C11.8027 7.268 11.9003 7.24854 11.999 7.24854C12.0976 7.24854 12.1953 7.268 12.2864 7.30581C12.3776 7.34362 12.4603 7.39903 12.53 7.46888L21.53 16.4689C21.6708 16.6097 21.7499 16.8007 21.7499 16.9999C21.7499 17.199 21.6708 17.39 21.53 17.5309C21.3892 17.6717 21.1982 17.7508 20.999 17.7508C20.7998 17.7508 20.6088 17.6717 20.468 17.5309L11.999 9.06038L3.52999 17.5309C3.38916 17.6717 3.19816 17.7508 2.99899 17.7508C2.79983 17.7508 2.60882 17.6717 2.46799 17.5309C2.32716 17.39 2.24805 17.199 2.24805 16.9999C2.24805 16.8007 2.32716 16.6097 2.46799 16.4689L11.468 7.46888Z"
                    fill="black"/>
            </svg>
        </span>
    );
}
