import React from 'react';
import { Card, Button, Table, Form, InputGroup } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { FaNewspaper, FaSearch, FaPlus } from 'react-icons/fa';

const News = () => {
  // Mock news data
  const newsItems = [
    { id: 1, title: 'SRC Elections Announced', date: '2023-07-20', author: 'Admin', status: 'Published' },
    { id: 2, title: 'New Campus Facilities Opening', date: '2023-07-15', author: 'Admin', status: 'Published' },
    { id: 3, title: 'Student Achievements 2023', date: '2023-07-10', author: 'Admin', status: 'Published' },
    { id: 4, title: 'Upcoming Cultural Week', date: '2023-07-05', author: 'Admin', status: 'Published' },
    { id: 5, title: 'Academic Calendar Update', date: '2023-08-01', author: 'Admin', status: 'Draft' },
    { id: 6, title: 'Campus Maintenance Notice', date: '2023-08-05', author: 'Admin', status: 'Draft' }
  ];

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>
          <FaNewspaper className="me-2" /> News & Announcements
        </h2>
        <Button as={Link} to="/dashboard/news/new" variant="primary">
          <FaPlus className="me-2" /> Create Announcement
        </Button>
      </div>

      <Card className="mb-4 border-0 shadow-sm">
        <Card.Body>
          <div className="d-flex justify-content-between flex-wrap">
            <div className="mb-3 mb-md-0" style={{ maxWidth: '300px' }}>
              <InputGroup>
                <Form.Control
                  placeholder="Search announcements..."
                  aria-label="Search announcements"
                />
                <Button variant="outline-secondary">
                  <FaSearch />
                </Button>
              </InputGroup>
            </div>

            <div className="d-flex gap-2">
              <Form.Select style={{ width: 'auto' }}>
                <option value="">All Statuses</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
              </Form.Select>

              <Form.Select style={{ width: 'auto' }}>
                <option value="">Sort By</option>
                <option value="date-desc">Date (Newest)</option>
                <option value="date-asc">Date (Oldest)</option>
                <option value="title-asc">Title (A-Z)</option>
                <option value="title-desc">Title (Z-A)</option>
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
                <th>Title</th>
                <th>Date</th>
                <th>Author</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {newsItems.map(item => (
                <tr key={item.id}>
                  <td>{item.title}</td>
                  <td>{item.date}</td>
                  <td>{item.author}</td>
                  <td>
                    <span className={`badge bg-${item.status === 'Published' ? 'success' : 'warning'}`}>
                      {item.status}
                    </span>
                  </td>
                  <td>
                    <div className="d-flex gap-2">
                      <Button as={Link} to={`/dashboard/news/${item.id}`} variant="outline-primary" size="sm">
                        View
                      </Button>
                      <Button as={Link} to={`/dashboard/news/${item.id}/edit`} variant="outline-secondary" size="sm">
                        Edit
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>
        </Card.Body>
      </Card>
    </div>
  );
};

export default News; 