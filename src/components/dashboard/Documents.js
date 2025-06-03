import React from 'react';
import { Card, Button, Table, Form, InputGroup, Row, Col } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { FaFileAlt, FaSearch, FaPlus, FaDownload, FaTrash, FaEdit } from 'react-icons/fa';

const Documents = () => {
  // Mock documents data
  const documents = [
    { id: 1, name: 'SRC Constitution', category: 'Governance', uploadDate: '2023-06-15', size: '1.2 MB' },
    { id: 2, name: 'Budget Proposal 2023', category: 'Finance', uploadDate: '2023-07-10', size: '3.5 MB' },
    { id: 3, name: 'Event Planning Guidelines', category: 'Events', uploadDate: '2023-06-30', size: '890 KB' },
    { id: 4, name: 'Election Procedures', category: 'Elections', uploadDate: '2023-07-15', size: '1.5 MB' },
    { id: 5, name: 'Annual Report 2022', category: 'Reports', uploadDate: '2023-02-10', size: '5.2 MB' },
    { id: 6, name: 'Student Handbook', category: 'General', uploadDate: '2023-05-20', size: '2.7 MB' }
  ];

  const categories = [
    'All Categories', 'Governance', 'Finance', 'Events', 'Elections', 'Reports', 'General'
  ];

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>
          <FaFileAlt className="me-2" /> Document Repository
        </h2>
        <Button variant="primary">
          <FaPlus className="me-2" /> Upload Document
        </Button>
      </div>

      <Row className="mb-4">
        <Col lg={3} md={4}>
          <Card className="border-0 shadow-sm mb-4">
            <Card.Header className="bg-white">
              <h5 className="mb-0">Categories</h5>
            </Card.Header>
            <Card.Body className="p-0">
              <div className="list-group list-group-flush">
                {categories.map((category, index) => (
                  <a 
                    key={index} 
                    href="#" 
                    className="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                    onClick={(e) => e.preventDefault()}
                  >
                    {category}
                    {category !== 'All Categories' && (
                      <span className="badge bg-primary rounded-pill">
                        {documents.filter(doc => doc.category === category).length}
                      </span>
                    )}
                  </a>
                ))}
              </div>
            </Card.Body>
          </Card>
        </Col>

        <Col lg={9} md={8}>
          <Card className="border-0 shadow-sm mb-4">
            <Card.Body>
              <div className="d-flex justify-content-between flex-wrap">
                <div className="mb-3 mb-md-0" style={{ maxWidth: '300px' }}>
                  <InputGroup>
                    <Form.Control
                      placeholder="Search documents..."
                      aria-label="Search documents"
                    />
                    <Button variant="outline-secondary">
                      <FaSearch />
                    </Button>
                  </InputGroup>
                </div>

                <Form.Select style={{ width: 'auto' }}>
                  <option value="">Sort By</option>
                  <option value="date-desc">Date (Newest)</option>
                  <option value="date-asc">Date (Oldest)</option>
                  <option value="name-asc">Name (A-Z)</option>
                  <option value="name-desc">Name (Z-A)</option>
                  <option value="size-desc">Size (Largest)</option>
                  <option value="size-asc">Size (Smallest)</option>
                </Form.Select>
              </div>
            </Card.Body>
          </Card>

          <Card className="border-0 shadow-sm">
            <Card.Body className="p-0">
              <Table responsive hover className="mb-0">
                <thead className="bg-light">
                  <tr>
                    <th>Document Name</th>
                    <th>Category</th>
                    <th>Upload Date</th>
                    <th>Size</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {documents.map(doc => (
                    <tr key={doc.id}>
                      <td>{doc.name}</td>
                      <td>{doc.category}</td>
                      <td>{doc.uploadDate}</td>
                      <td>{doc.size}</td>
                      <td>
                        <div className="d-flex gap-2">
                          <Button variant="outline-primary" size="sm">
                            <FaDownload /> Download
                          </Button>
                          <Button variant="outline-secondary" size="sm">
                            <FaEdit />
                          </Button>
                          <Button variant="outline-danger" size="sm">
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
        </Col>
      </Row>
    </div>
  );
};

export default Documents; 