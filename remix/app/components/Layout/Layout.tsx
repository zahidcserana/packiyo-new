import React, {useEffect, useMemo, useState} from 'react';
import SideMenu, {generateMainMenuItems, generateBottomMenuItems} from "./SideMenu/SideMenu";
import Logo from "./Logo/Logo";
import MainHeader from "./MainHeader/MainHeader";
import {Button, Container} from "react-bootstrap";
import AccountsMenu from "./AccountsMenu/AccountsMenu";
import {useRootLoaderData} from "../../root";
import {useNavigation} from "@remix-run/react";
import {IoCloseOutline} from "react-icons/io5";

const Layout = ({children}) => {
    const user = useRootLoaderData();

    const mainMenuItems = useMemo(() => generateMainMenuItems(user), [user]);
    const bottomMenuItems = useMemo(() => generateBottomMenuItems(user), [user]);
    const [isSideMenuOpen, setIsSideMenuOpen] = useState(false);

    const navigation = useNavigation();

    useEffect(() => {
        setIsSideMenuOpen(false);
    }, [navigation]);

    return (
        <>
            <header className="header">
                {user.id && <MainHeader setIsSideMenuOpen={setIsSideMenuOpen}/>}
            </header>
            <aside className={isSideMenuOpen ? 'sidebar d-flex flex-column opened' : 'sidebar d-flex flex-column'}>
                <Logo/>
                <Button variant="secondary" className="close-sidebar-button d-lg-none" onClick={() => setIsSideMenuOpen(false)}>
                    <IoCloseOutline size={20} />
                </Button>
                {user.id && <AccountsMenu/>}
                <div className="d-flex flex-column h-100 justify-content-between overflow-auto">
                    {user.id && <SideMenu menuItems={mainMenuItems}/>}
                    <SideMenu menuItems={bottomMenuItems}/>
                </div>
            </aside>
            <div className={isSideMenuOpen ? 'side-menu-overlay opened' : 'side-menu-overlay'} onClick={() => setIsSideMenuOpen(false)}></div>
            <main className="main">
                <Container className="mt-4" fluid>
                    {children}
                </Container>
            </main>
        </>
    );
};

export default Layout;
