import React, {useState} from "react";
import {Button, Col, Row} from "react-bootstrap";

const Tabs = ({ tabs }) => {
    const [activeTabIndex, setActiveTabIndex] = useState(0)

    const handleClick = (index) => {
        setActiveTabIndex(index);
    };

    return (
        <div className="tabs">
            <Row>
                <Col xl={2} lg={12}>
                    <Row className="mb-3 mt-1 d-flex justify-content-center">
                        {tabs.map((tab, index) => (
                            <Col key={index} xl={12} lg={"auto"} className="mb-2">
                                <Button style={{width: '100%'}} variant={activeTabIndex === index ? 'primary' : 'secondary'} onClick={() => handleClick(index)}>
                                    {tab.title}
                                </Button>
                            </Col>
                        ))}
                    </Row>
                </Col>
                <Col xl={10} lg={12}>
                    <Row>
                        <div className="col">
                            {tabs[activeTabIndex]?.content}
                        </div>
                    </Row>
                </Col>
            </Row>
        </div>
    );
};

export default Tabs;
