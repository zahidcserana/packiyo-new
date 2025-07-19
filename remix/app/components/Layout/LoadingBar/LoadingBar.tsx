import { useNavigation } from "@remix-run/react";
import TopLoadingBar from "react-top-loading-bar";
import React, {useEffect, useMemo, useRef, useState} from "react";

const LoadingBar = () => {
    const ref = useRef(null);
    const navigation = useNavigation();
    const [isRunning, setIsRunning] = useState(false);

    const shouldShowLoadingBar = useMemo(() => {
        return navigation.state === "loading" || navigation.state === "submitting";
    }, [navigation]);

    useEffect(() => {
        setIsRunning(true);

        if (shouldShowLoadingBar && !isRunning) {
            ref.current.continuousStart();
        }

        if (navigation.state === "idle") {
            ref.current.complete();
            setIsRunning(false);
        }
    }, [shouldShowLoadingBar, navigation]);

    return (
        <TopLoadingBar color="#03ce6f" shadow={true} ref={ref} waitingTime={200} />
    );
};

export default LoadingBar;
