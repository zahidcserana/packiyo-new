import { Link, useNavigate } from "@remix-run/react";
import React, { useEffect, useRef, useState } from 'react';
import { Nav } from 'react-bootstrap';
import {
    IoBusinessOutline,
    IoCheckboxOutline,
    IoChevronForwardOutline,
    IoCubeOutline, IoDocumentsOutline, IoDownloadOutline, IoExitOutline, IoGitCompareOutline,
    IoNavigateOutline, IoNewspaperOutline,
    IoOpenOutline, IoPeopleOutline,
    IoPersonOutline, IoPrintOutline,
    IoSquareOutline,
    IoToggleOutline,
    IoChevronDownOutline, IoSettingsOutline,
} from 'react-icons/io5';
import { LiaRobotSolid } from "react-icons/lia";
import { useLocation } from "react-router";
import { useRootLoaderData } from "../../../root";

const SideMenu = ({ menuItems }) => {
    const user = useRootLoaderData();

    const {
        is_super_admin: isSuperAdmin,
        is_admin: idAdmin,
        is_3pl: isThreePl,
        is_standalone: isStandalone,
    } = useRootLoaderData();

    const [openSubMenu, setOpenSubMenu] = useState(null);
    const submenuRefs = useRef({});
    const navigate = useNavigate();
    const location = useLocation();
    const toggleSubMenu = (title) => {
        const isCurrentlyOpen = openSubMenu === title;

        if (openSubMenu && submenuRefs.current[openSubMenu]) {
            submenuRefs.current[openSubMenu].style.maxHeight = '0px';
        }

        if (!isCurrentlyOpen) {
            const el = submenuRefs.current[title];
            if (el) {
                el.style.maxHeight = `${el.scrollHeight}px`;
                setOpenSubMenu(title);
            }
        } else {
            setOpenSubMenu(null);
        }
    };

    useEffect(() => {
        Object.keys(submenuRefs.current).forEach((key) => {
            if (submenuRefs.current[key]) {
                submenuRefs.current[key].style.maxHeight = '0px';
                setOpenSubMenu(null);
            }
        });

        menuItems.forEach((item) => {
            if (item.children) {
                const isOpen = item.children.some((child) => location.pathname.startsWith(child.href));
                if (isOpen) {
                    const el = submenuRefs.current[item.title];
                    if (el) {
                        el.style.maxHeight = `${el.scrollHeight}px`;
                        setOpenSubMenu(item.title);
                    }
                }
            }
        });
    }, [location.pathname, navigate, menuItems]);

    const renderMenuItems = (items) => items.map((item, index) => {
        const isActive = item.href ? location.pathname === item.href : false;

        const superAdminOnly = item.superAdminOnly;
        const adminOnly = item.adminOnly;
        const threePlOnly = item.threePlOnly;
        const standaloneOnly = item.standaloneOnly;
        const threePlOrStandalone = item.threePlOrStandalone;

        if (superAdminOnly && !isSuperAdmin) {
            return;
        }

        if (adminOnly && !idAdmin) {
            return;
        }

        if (threePlOnly && !isThreePl) {
            return;
        }

        if (standaloneOnly && !isStandalone) {
            return;
        }

        if (threePlOrStandalone && !isThreePl && !isStandalone) {
            return;
        }

        return (
        <React.Fragment key={item.title + index}>
            {item.children ? (
                <>
                    <Nav.Link onClick={() => toggleSubMenu(item.title)} className={`d-flex justify-content-between align-items-center ${isActive ? 'active' : ''}`}>
                        <div className="d-flex align-items-center">
                            {item.icon} <span className="ms-2">{item.title}</span>
                        </div>
                        {openSubMenu === item.title ? <IoChevronDownOutline /> : <IoChevronForwardOutline />}
                    </Nav.Link>
                    <div
                        ref={(el) => submenuRefs.current[item.title] = el}
                        style={{ overflow: 'hidden', transition: 'max-height 0.3s ease' }}
                        className="submenu"
                    >
                        <Nav className="flex-column ps-4">
                            {renderMenuItems(item.children)}
                        </Nav>
                    </div>
                </>
            ) : (
                <Link target={item.target ? item.target : ''} to={item.href} className={`d-flex align-items-center nav-link ${isActive ? 'active' : ''}`}>
                    {item.icon} <span className="ms-2">{item.title}</span>
                </Link>
            )}
        </React.Fragment>
    )});

    return <Nav defaultActiveKey="/home" className="flex-column side-menu">{renderMenuItems(menuItems)}</Nav>;
};

export default SideMenu;

export const generateMainMenuItems = (user) => {
    return [
        {
            title: 'Features',
            target: '_blank',
            href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/features',
            icon: <IoToggleOutline />,
            superAdminOnly: true,
            adminOnly: true,
        },
        {
            title: 'Setup Checklist',
            target: '_blank',
            href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/dashboard',
            icon: <IoCheckboxOutline />,
            adminOnly: true,
        },
        {
            title: 'Account Info',
            href: '/settings/account',
            icon: <IoPersonOutline />,
            adminOnly: true,
        },
        {
            title: 'Additional Settings',
            href: '/settings/additional-settings',
            icon: <IoSettingsOutline />,
            adminOnly: true,
        },
        {
            title: 'Display',
            icon: (<div><IoSquareOutline /></div>),
            children: [
                { title: 'Default Search Results', href: '/settings/display/search-results', icon: '' },
                { title: 'Product Page', href: '/settings/display/product-page', icon: '' },
            ],
            adminOnly: true,
        },
        {
            title: 'Shipping',
            icon: (<div><IoNavigateOutline /></div>),
            children: [
                { title: 'Shipping Carriers', target: '_blank', href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/shipping_carrier', icon: <IoOpenOutline/> },
                { title: 'Shipping Methods', target: '_blank', href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/shipping_method', icon: <IoOpenOutline/> },
                { title: 'EasyPost Credentials', href: '/settings/shipping/easypost-credentials', icon: '' },
                { title: 'WebShipper Credentials', href: '/settings/shipping/webshipper-credentials', icon: '' },
                { title: 'Bulk Shipping', href: '/settings/shipping/bulk-shipping', icon: '' },
                { title: 'Customs', href: '/settings/shipping/customs', icon: '' },
                { title: 'Return Label', href: '/settings/shipping/return-label', icon: '' },
            ],
            adminOnly: true,
        },
        {
            title: 'Labels & Documents',
            icon: (<div><IoDocumentsOutline /></div>),
            children: [
                { title: 'Shipping Labels', href: '/settings/labels-documents/shipping-labels', icon: '' },
                { title: 'Packing Slips', href: '/settings/labels-documents/packing-slips', icon: '' },
                { title: 'Barcodes', href: '/settings/labels-documents/barcodes', icon: '' },
            ],
            adminOnly: true,
        },

        {
            title: 'Team',
            target: '_blank',
            href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/user',
            icon: <IoPeopleOutline />,
            adminOnly: true,
        },
        {
            title: 'Connections',
            href: '/settings/connections',
            icon: <IoGitCompareOutline />,
            superAdminOnly: true,
            adminOnly: true,
        },
        {
            title: 'Packing',
            icon: (<div><IoCubeOutline /></div>),
            children: [
                { title: 'Default Package', href: '/settings/packing/default-package', icon: '' },
                { title: 'Packages', target: '_blank', href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/shipping_box', icon: <IoOpenOutline/> },
            ],
            adminOnly: true,
        },
        {
            title: 'Printing',
            icon: (<div><IoPrintOutline /></div>),
            children: [
                { title: 'Shipping Label Printer', href: '/settings/printing/shipping-label-printer', icon: '' },
                { title: 'Barcode Printer', href: '/settings/printing/barcode-printer', icon: '' },
                { title: 'Packing Slip Printer', href: '/settings/printing/packing-slip-printer', icon: '' },
                { title: 'Printers', target: '_blank', href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/printer', icon: <IoOpenOutline/> },
            ],
            adminOnly: true,
        },
        {
            title: 'Pick & Pack',
            icon: (<div><IoDownloadOutline /></div>),
            children: [
                { title: 'Totes', target: '_blank', href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/tote', icon: <IoOpenOutline/> },
                { title: 'Pick Route Mode', href: '/settings/pick-pack/pick-route-mode', icon: '' },
            ],
            adminOnly: true,
        },
        {
            title: 'Warehouses',
            icon: (<div><IoBusinessOutline /></div>),
            children: [
                { title: 'Warehouses', target: '_blank', href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/warehouses', icon: <IoOpenOutline/> },
                { title: 'Location Types', target: '_blank', href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/location_type', icon: <IoOpenOutline/> },
                { title: 'Locations', target: '_blank', href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/location', icon: <IoOpenOutline/> },
            ],
            adminOnly: true,
        },
        {
            title: 'Client Billing',
            icon: (<div><IoNewspaperOutline /></div>),
            children: [
                { title: 'Rate Card Assignment', href: '/settings/client-billing/rate-card-assignment' },
                { title: 'Billing', target: '_blank', href: user?.backend_domain + '/user_customer/set/' + user?.current_account_id + '?redirect=/billings', icon: <IoOpenOutline/> },
            ],
            adminOnly: true,
        },
        {
            title: 'Automations',
            href: '/automations',
            icon: <LiaRobotSolid />,
            adminOnly: true,
            threePlOrStandalone: true,
        },
    ]
};

export const generateBottomMenuItems = (user) => { return [
    { title: 'Exit Settings', href: user?.backend_domain + '/', icon: (<div><IoExitOutline /></div>) },
]};
