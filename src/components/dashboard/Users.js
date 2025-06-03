import React, { useState } from 'react';
import { Card, Button, Table, Form, InputGroup, Badge, Modal } from 'react-bootstrap';
import { FaUsers, FaSearch, FaPlus, FaUserEdit, FaTrash, FaKey } from 'react-icons/fa';

const Users = () => {
  const [showModal, setShowModal] = useState(false);
  const [modalType, setModalType] = useState(''); // 'add', 'edit', 'delete', 'resetPassword'
  const [selectedUser, setSelectedUser] = useState(null);

  // Mock users data
  const users = [
    { id: 1, name: 'Admin User', email: 'admin@example.com', role: 'admin', status: 'Active', lastLogin: '2023-07-25' },
    { id: 2, name: 'John Doe', email: 'john@example.com', role: 'user', status: 'Active', lastLogin: '2023-07-24' },
    { id: 3, name: 'Jane Smith', email: 'jane@example.com', role: 'user', status: 'Active', lastLogin: '2023-07-20' },
    { id: 4, name: 'Robert Johnson', email: 'robert@example.com', role: 'user', status: 'Inactive', lastLogin: '2023-06-15' },
    { id: 5, name: 'Emily Brown', email: 'emily@example.com', role: 'moderator', status: 'Active', lastLogin: '2023-07-22' },
    { id: 6, name: 'Michael Wilson', email: 'michael@example.com', role: 'user', status: 'Active', lastLogin: '2023-07-23' }
  ];

  const handleCloseModal = () => {
    setShowModal(false);
    setSelectedUser(null);
  };

  const handleShowModal = (type, user = null) => {
    setModalType(type);
    setSelectedUser(user);
    setShowModal(true);
  };

  const getRoleBadgeColor = (role) => {
    switch (role) {
      case 'admin':
        return 'danger';
      case 'moderator':
        return 'warning';
      default:
        return 'info';
    }
  };

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>
          <FaUsers className="me-2" /> User Management
        </h2>
        <Button variant="primary" onClick={() => handleShowModal('add')}>
          <FaPlus className="me-2" /> Add User
        </Button>
      </div>

      <Card className="mb-4 border-0 shadow-sm">
        <Card.Body>
          <div className="d-flex justify-content-between flex-wrap">
            <div className="mb-3 mb-md-0" style={{ maxWidth: '300px' }}>
              <InputGroup>
                <Form.Control
                  placeholder="Search users..."
                  aria-label="Search users"
                />
                <Button variant="outline-secondary">
                  <FaSearch />
                </Button>
              </InputGroup>
            </div>

            <div className="d-flex gap-2">
              <Form.Select style={{ width: 'auto' }}>
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="moderator">Moderator</option>
                <option value="user">User</option>
              </Form.Select>

              <Form.Select style={{ width: 'auto' }}>
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </Form.Select>
            </div>
          </div>
        </Card.Body>
      </Card>

      <Card className="border-0 shadow-sm">
        <Card.Body className="p-0">
          <Table responsive hover className="mb-0">
            <thead className="bg-light">
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.map(user => (
                <tr key={user.id}>
                  <td>{user.name}</td>
                  <td>{user.email}</td>
                  <td>
                    <Badge bg={getRoleBadgeColor(user.role)}>
                      {user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                    </Badge>
                  </td>
                  <td>
                    <Badge bg={user.status === 'Active' ? 'success' : 'secondary'}>
                      {user.status}
                    </Badge>
                  </td>
                  <td>{user.lastLogin}</td>
                  <td>
                    <div className="d-flex gap-2">
                      <Button variant="outline-primary" size="sm" onClick={() => handleShowModal('edit', user)}>
                        <FaUserEdit />
                      </Button>
                      <Button variant="outline-warning" size="sm" onClick={() => handleShowModal('resetPassword', user)}>
                        <FaKey />
                      </Button>
                      <Button variant="outline-danger" size="sm" onClick={() => handleShowModal('delete', user)}>
                        <FaTrash />
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>
        </Card.Body>
      </Card>

      {/* Modals for user management actions */}
      <Modal show={showModal} onHide={handleCloseModal}>
        {modalType === 'add' && (
          <>
            <Modal.Header closeButton>
              <Modal.Title>Add New User</Modal.Title>
            </Modal.Header>
            <Modal.Body>
              <Form>
                <Form.Group className="mb-3">
                  <Form.Label>Full Name</Form.Label>
                  <Form.Control type="text" placeholder="Enter full name" />
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Label>Email Address</Form.Label>
                  <Form.Control type="email" placeholder="Enter email" />
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Label>Role</Form.Label>
                  <Form.Select>
                    <option value="user">User</option>
                    <option value="moderator">Moderator</option>
                    <option value="admin">Admin</option>
                  </Form.Select>
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Label>Initial Password</Form.Label>
                  <Form.Control type="password" placeholder="Enter password" />
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Check 
                    type="checkbox" 
                    label="Send welcome email with login details" 
                    defaultChecked 
                  />
                </Form.Group>
              </Form>
            </Modal.Body>
            <Modal.Footer>
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button variant="primary">
                Add User
              </Button>
            </Modal.Footer>
          </>
        )}

        {modalType === 'edit' && selectedUser && (
          <>
            <Modal.Header closeButton>
              <Modal.Title>Edit User</Modal.Title>
            </Modal.Header>
            <Modal.Body>
              <Form>
                <Form.Group className="mb-3">
                  <Form.Label>Full Name</Form.Label>
                  <Form.Control type="text" defaultValue={selectedUser.name} />
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Label>Email Address</Form.Label>
                  <Form.Control type="email" defaultValue={selectedUser.email} />
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Label>Role</Form.Label>
                  <Form.Select defaultValue={selectedUser.role}>
                    <option value="user">User</option>
                    <option value="moderator">Moderator</option>
                    <option value="admin">Admin</option>
                  </Form.Select>
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Label>Status</Form.Label>
                  <Form.Select defaultValue={selectedUser.status}>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </Form.Select>
                </Form.Group>
              </Form>
            </Modal.Body>
            <Modal.Footer>
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button variant="primary">
                Save Changes
              </Button>
            </Modal.Footer>
          </>
        )}

        {modalType === 'resetPassword' && selectedUser && (
          <>
            <Modal.Header closeButton>
              <Modal.Title>Reset Password</Modal.Title>
            </Modal.Header>
            <Modal.Body>
              <p>Are you sure you want to reset the password for {selectedUser.name}?</p>
              <Form>
                <Form.Group className="mb-3">
                  <Form.Label>New Password</Form.Label>
                  <Form.Control type="password" placeholder="Enter new password" />
                </Form.Group>
                <Form.Group className="mb-3">
                  <Form.Check 
                    type="checkbox" 
                    label="Send email notification with new password" 
                    defaultChecked 
                  />
                </Form.Group>
              </Form>
            </Modal.Body>
            <Modal.Footer>
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button variant="warning">
                Reset Password
              </Button>
            </Modal.Footer>
          </>
        )}

        {modalType === 'delete' && selectedUser && (
          <>
            <Modal.Header closeButton>
              <Modal.Title>Delete User</Modal.Title>
            </Modal.Header>
            <Modal.Body>
              <p>Are you sure you want to delete the user {selectedUser.name}?</p>
              <p className="text-danger">This action cannot be undone.</p>
            </Modal.Body>
            <Modal.Footer>
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button variant="danger">
                Delete User
              </Button>
            </Modal.Footer>
          </>
        )}
      </Modal>
    </div>
  );
};

export default Users; 