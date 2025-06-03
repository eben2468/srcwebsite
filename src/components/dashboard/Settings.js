import React, { useState } from 'react';
import { Card, Button, Form, Row, Col, Tab, Nav, Alert } from 'react-bootstrap';
import { FaCog, FaUniversity, FaEnvelope, FaUsers, FaLock, FaDatabase, FaCloudUploadAlt } from 'react-icons/fa';

const Settings = () => {
  const [showAlert, setShowAlert] = useState(false);
  const [alertVariant, setAlertVariant] = useState('success');
  const [alertMessage, setAlertMessage] = useState('');

  const handleSave = (section) => {
    setAlertVariant('success');
    setAlertMessage(`${section} settings have been saved successfully.`);
    setShowAlert(true);
    
    // Hide the alert after 3 seconds
    setTimeout(() => {
      setShowAlert(false);
    }, 3000);
  };

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>
          <FaCog className="me-2" /> System Settings
        </h2>
      </div>

      {showAlert && (
        <Alert 
          variant={alertVariant} 
          onClose={() => setShowAlert(false)} 
          dismissible
          className="mb-4"
        >
          {alertMessage}
        </Alert>
      )}

      <Card className="border-0 shadow-sm mb-4">
        <Card.Body>
          <Tab.Container id="settings-tabs" defaultActiveKey="general">
            <Row>
              <Col md={3}>
                <Nav variant="pills" className="flex-column">
                  <Nav.Item>
                    <Nav.Link eventKey="general">
                      <FaUniversity className="me-2" /> General
                    </Nav.Link>
                  </Nav.Item>
                  <Nav.Item>
                    <Nav.Link eventKey="email">
                      <FaEnvelope className="me-2" /> Email
                    </Nav.Link>
                  </Nav.Item>
                  <Nav.Item>
                    <Nav.Link eventKey="users">
                      <FaUsers className="me-2" /> Users & Permissions
                    </Nav.Link>
                  </Nav.Item>
                  <Nav.Item>
                    <Nav.Link eventKey="security">
                      <FaLock className="me-2" /> Security
                    </Nav.Link>
                  </Nav.Item>
                  <Nav.Item>
                    <Nav.Link eventKey="backup">
                      <FaDatabase className="me-2" /> Backup & Restore
                    </Nav.Link>
                  </Nav.Item>
                </Nav>
              </Col>
              <Col md={9}>
                <Tab.Content>
                  {/* General Settings */}
                  <Tab.Pane eventKey="general">
                    <h4 className="mb-4">General Settings</h4>
                    <Form>
                      <Form.Group className="mb-3">
                        <Form.Label>Organization Name</Form.Label>
                        <Form.Control type="text" defaultValue="Students' Representative Council" />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Label>Website Title</Form.Label>
                        <Form.Control type="text" defaultValue="SRC Management System" />
                      </Form.Group>
                      <Row className="mb-3">
                        <Col md={6}>
                          <Form.Group>
                            <Form.Label>Academic Year Start</Form.Label>
                            <Form.Control type="date" defaultValue="2023-08-01" />
                          </Form.Group>
                        </Col>
                        <Col md={6}>
                          <Form.Group>
                            <Form.Label>Academic Year End</Form.Label>
                            <Form.Control type="date" defaultValue="2024-05-31" />
                          </Form.Group>
                        </Col>
                      </Row>
                      <Form.Group className="mb-3">
                        <Form.Label>Contact Email</Form.Label>
                        <Form.Control type="email" defaultValue="info@src.edu" />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Label>Contact Phone</Form.Label>
                        <Form.Control type="text" defaultValue="+123-456-7890" />
                      </Form.Group>
                      <Form.Group className="mb-4">
                        <Form.Label>System Logo</Form.Label>
                        <div className="d-flex align-items-center">
                          <img 
                            src="/logo192.png" 
                            alt="Current Logo" 
                            height="50" 
                            className="me-3"
                          />
                          <Button variant="outline-primary">
                            <FaCloudUploadAlt className="me-2" /> Change Logo
                          </Button>
                        </div>
                      </Form.Group>
                      <div className="d-flex justify-content-end">
                        <Button variant="primary" onClick={() => handleSave('General')}>
                          Save Changes
                        </Button>
                      </div>
                    </Form>
                  </Tab.Pane>

                  {/* Email Settings */}
                  <Tab.Pane eventKey="email">
                    <h4 className="mb-4">Email Settings</h4>
                    <Form>
                      <Form.Group className="mb-3">
                        <Form.Label>SMTP Server</Form.Label>
                        <Form.Control type="text" defaultValue="smtp.example.com" />
                      </Form.Group>
                      <Row className="mb-3">
                        <Col md={6}>
                          <Form.Group>
                            <Form.Label>SMTP Port</Form.Label>
                            <Form.Control type="number" defaultValue="587" />
                          </Form.Group>
                        </Col>
                        <Col md={6}>
                          <Form.Group>
                            <Form.Label>Encryption</Form.Label>
                            <Form.Select defaultValue="tls">
                              <option value="none">None</option>
                              <option value="ssl">SSL</option>
                              <option value="tls">TLS</option>
                            </Form.Select>
                          </Form.Group>
                        </Col>
                      </Row>
                      <Form.Group className="mb-3">
                        <Form.Label>Username</Form.Label>
                        <Form.Control type="text" defaultValue="notifications@src.edu" />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Label>Password</Form.Label>
                        <Form.Control type="password" defaultValue="********" />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Label>From Name</Form.Label>
                        <Form.Control type="text" defaultValue="SRC Management System" />
                      </Form.Group>
                      <Form.Group className="mb-4">
                        <Form.Label>From Email</Form.Label>
                        <Form.Control type="email" defaultValue="notifications@src.edu" />
                      </Form.Group>
                      <div className="d-flex justify-content-between">
                        <Button variant="outline-primary">
                          Test Connection
                        </Button>
                        <Button variant="primary" onClick={() => handleSave('Email')}>
                          Save Changes
                        </Button>
                      </div>
                    </Form>
                  </Tab.Pane>

                  {/* Users & Permissions Settings */}
                  <Tab.Pane eventKey="users">
                    <h4 className="mb-4">Users & Permissions Settings</h4>
                    <Form>
                      <Form.Group className="mb-3">
                        <Form.Label>Default User Role</Form.Label>
                        <Form.Select defaultValue="user">
                          <option value="admin">Admin</option>
                          <option value="moderator">Moderator</option>
                          <option value="user">User</option>
                        </Form.Select>
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Check 
                          type="switch"
                          id="allow-registration"
                          label="Allow Public Registration"
                          defaultChecked={false}
                        />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Check 
                          type="switch"
                          id="email-verification"
                          label="Require Email Verification"
                          defaultChecked={true}
                        />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Check 
                          type="switch"
                          id="admin-approval"
                          label="Require Admin Approval for New Accounts"
                          defaultChecked={true}
                        />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Label>Session Timeout (minutes)</Form.Label>
                        <Form.Control type="number" defaultValue="30" />
                      </Form.Group>
                      <div className="d-flex justify-content-end">
                        <Button variant="primary" onClick={() => handleSave('Users & Permissions')}>
                          Save Changes
                        </Button>
                      </div>
                    </Form>
                  </Tab.Pane>

                  {/* Security Settings */}
                  <Tab.Pane eventKey="security">
                    <h4 className="mb-4">Security Settings</h4>
                    <Form>
                      <Form.Group className="mb-3">
                        <Form.Label>Password Policy</Form.Label>
                        <div className="ms-3 mb-3">
                          <Form.Check 
                            type="checkbox"
                            id="pwd-uppercase"
                            label="Require uppercase letters"
                            defaultChecked={true}
                          />
                          <Form.Check 
                            type="checkbox"
                            id="pwd-lowercase"
                            label="Require lowercase letters"
                            defaultChecked={true}
                          />
                          <Form.Check 
                            type="checkbox"
                            id="pwd-numbers"
                            label="Require numbers"
                            defaultChecked={true}
                          />
                          <Form.Check 
                            type="checkbox"
                            id="pwd-special"
                            label="Require special characters"
                            defaultChecked={true}
                          />
                        </div>
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Label>Minimum Password Length</Form.Label>
                        <Form.Control type="number" defaultValue="8" />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Label>Password Expiry (days)</Form.Label>
                        <Form.Control type="number" defaultValue="90" />
                        <Form.Text className="text-muted">
                          Set to 0 to disable password expiry.
                        </Form.Text>
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Check 
                          type="switch"
                          id="two-factor"
                          label="Enable Two-Factor Authentication"
                          defaultChecked={false}
                        />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Check 
                          type="switch"
                          id="login-attempts"
                          label="Limit Login Attempts"
                          defaultChecked={true}
                        />
                      </Form.Group>
                      <Form.Group className="mb-3">
                        <Form.Label>Max Login Attempts</Form.Label>
                        <Form.Control type="number" defaultValue="5" />
                      </Form.Group>
                      <div className="d-flex justify-content-end">
                        <Button variant="primary" onClick={() => handleSave('Security')}>
                          Save Changes
                        </Button>
                      </div>
                    </Form>
                  </Tab.Pane>

                  {/* Backup & Restore Settings */}
                  <Tab.Pane eventKey="backup">
                    <h4 className="mb-4">Backup & Restore Settings</h4>
                    <Card className="bg-light mb-4">
                      <Card.Body>
                        <h5>Create Backup</h5>
                        <p>
                          Create a backup of the entire system including database and files.
                        </p>
                        <Button variant="primary">
                          Create Backup Now
                        </Button>
                      </Card.Body>
                    </Card>

                    <Card className="bg-light mb-4">
                      <Card.Body>
                        <h5>Scheduled Backups</h5>
                        <Form className="mb-3">
                          <Form.Group className="mb-3">
                            <Form.Check 
                              type="switch"
                              id="auto-backup"
                              label="Enable Automatic Backups"
                              defaultChecked={true}
                            />
                          </Form.Group>
                          <Form.Group className="mb-3">
                            <Form.Label>Backup Frequency</Form.Label>
                            <Form.Select defaultValue="daily">
                              <option value="hourly">Hourly</option>
                              <option value="daily">Daily</option>
                              <option value="weekly">Weekly</option>
                              <option value="monthly">Monthly</option>
                            </Form.Select>
                          </Form.Group>
                          <Form.Group className="mb-3">
                            <Form.Label>Retention Period (days)</Form.Label>
                            <Form.Control type="number" defaultValue="30" />
                          </Form.Group>
                          <Button variant="primary" onClick={() => handleSave('Backup')}>
                            Save Backup Settings
                          </Button>
                        </Form>
                      </Card.Body>
                    </Card>

                    <Card className="bg-light">
                      <Card.Body>
                        <h5>Restore from Backup</h5>
                        <p>
                          Select a backup file to restore the system. This will overwrite current data.
                        </p>
                        <div className="mb-3">
                          <Form.Group>
                            <Form.Label>Select Backup File</Form.Label>
                            <Form.Control type="file" />
                          </Form.Group>
                        </div>
                        <Button variant="danger">
                          Restore System
                        </Button>
                      </Card.Body>
                    </Card>
                  </Tab.Pane>
                </Tab.Content>
              </Col>
            </Row>
          </Tab.Container>
        </Card.Body>
      </Card>
    </div>
  );
};

export default Settings; 