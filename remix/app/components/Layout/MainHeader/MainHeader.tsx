import React, {useState} from "react";
import {Col, Container, Row, Dropdown, Image, Button} from "react-bootstrap";
import {
    IoLogOutOutline, IoMenuOutline
} from 'react-icons/io5';

import thumbnail from "./../../../images/thumbnail.png";
import {useRootLoaderData} from "../../../root";
import Logo from "../Logo/Logo";

const MainHeader = ({setIsSideMenuOpen}) => {
    const user = useRootLoaderData();

    const ProfileToggle = React.forwardRef(({ children, onClick }, ref) => (
        <div className="d-flex flex-row" style={{cursor: 'pointer'}} ref={ref} onClick={(e) => {
            e.preventDefault();
            onClick(e);
        }}>
            <div className="d-none d-lg-flex flex-column me-2">
                <strong>{user?.name}</strong>
                <span>{user?.email}</span>
            </div>
            <Image style={{width: '50px', height: '50px', objectFit: 'cover'}} className="profile-image" onError={(e) => {
                e.target.onerror = null;
                e.target.src = thumbnail;
            }} src={user?.picture ?? thumbnail} roundedCircle />
        </div>
    ));

    return (
        <Container fluid>
            <Row>
                <Col xs={4} className="d-flex align-items-center">
                    <Button variant={"secondary"} className="d-lg-none align-items-center" onClick={() => setIsSideMenuOpen(true)}>
                        <IoMenuOutline size={20} />
                    </Button>
                </Col>
                <Col xs={4} className="d-flex align-items-center justify-content-center">
                    <div className="d-lg-none">
                        <Logo/>
                    </div>
                </Col>
                <Col xs={4} className="d-flex align-items-center justify-content-end">
                    <Dropdown align="end">
                        <Dropdown.Toggle as={ProfileToggle} />
                        <Dropdown.Menu>
                            <Dropdown.Header>Welcome!</Dropdown.Header>
                            <Button href={user?.backend_domain + '/'} variant="" type="submit" className="d-flex align-items-center dropdown-item me-2">
                                <IoLogOutOutline style={{fontSize: '20px'}} className="me-2"/>Exit Settings
                            </Button>
                        </Dropdown.Menu>
                    </Dropdown>
                </Col>
            </Row>
        </Container>
    )
}

export default MainHeader;
