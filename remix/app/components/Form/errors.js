import {Alert} from "react-bootstrap";

export default function Label({ errors = [], ...props }) {
    return (
        <>
            {errors.length > 0 && (
                <div {...props}>
                    {errors.map(error => (
                        <Alert key={error} variant="danger">
                            {error}
                        </Alert>
                    ))}
                </div>
            )}
        </>
    )
}
