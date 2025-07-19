import {Link} from "@remix-run/react";
import {useRootLoaderData} from "../../../root";

const Logo = () => {
    const user = useRootLoaderData();

    return <Link to="/" className="logo">
        <img style={{width: '150px', height: '80px', objectFit: 'contain'}} src={user.logo_src} alt="Logo"/>
    </Link>
}

export default Logo;
