import React from 'react';
import { Card, Button, Table, Form, InputGroup } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { FaCalendarAlt, FaSearch, FaPlus } from 'react-icons/fa';

const Events = () => {
  // Mock events data
  const events = [
    { id: 1, name: 'Orientation Week', date: '2023-08-15', location: 'Main Campus', status: 'Upcoming' },
    { id: 2, name: 'Leadership Workshop', date: '2023-08-20', location: 'Conference Hall', status: 'Upcoming' },
    { id: 3, name: 'Cultural Festival', date: '2023-09-05', location: 'Student Center', status: 'Upcoming' },
    { id: 4, name: 'Career Fair', date: '2023-09-15', location: 'Exhibition Hall', status: 'Planning' },
    { id: 5, name: 'Academic Excellence Awards', date: '2023-10-10', location: 'Auditorium', status: 'Planning' },
    { id: 6, name: 'Sports Tournament', date: '2023-07-10', location: 'Sports Complex', status: 'Completed' }
  ];

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>
          <FaCalendarAlt className="me-2" /> Event Management
        </h2>
        <Button as={Link} to="/dashboard/events/new" variant="primary">
          <FaPlus className="me-2" /> Create Event
        </Button>
      </div>

      <Card className="mb-4 border-0 shadow-sm">
        <Card.Body>
          <div className="d-flex justify-content-between flex-wrap">
            <div className="mb-3 mb-md-0" style={{ maxWidth: '300px' }}>
              <InputGroup>
                <Form.Control
                  placeholder="Search events..."
                  aria-label="Search events"
                />
                <Button variant="outline-secondary">
                  <FaSearch />
                </Button>
              </InputGroup>
            </div>

            <div className="d-flex gap-2">
              <Form.Select style={{ width: 'auto' }}>
                <option value="">All Statuses</option>
                <option value="upcoming">Upcoming</option>
                <option value="planning">Planning</option>
                <option value="completed">Completed</option>
              </Form.Select>

              <Form.Select style={{ width: 'auto' }}>
                <option value="">Sort By</option>
                <option value="date-asc">Date (Ascending)</option>
                <option value="date-desc">Date (Descending)</option>
                <option value="name-asc">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
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
                <th>Event Name</th>
                <th>Date</th>
                <th>Location</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {events.map(event => (
                <tr key={event.id}>
                  <td>{event.name}</td>
                  <td>{event.date}</td>
                  <td>{event.location}</td>
                  <td>
                    <span className={`badge bg-${
                      event.status === 'Upcoming' ? 'success' : 
                      event.status === 'Planning' ? 'warning' : 'secondary'
                    }`}>
                      {event.status}
                    </span>
                  </td>
                  <td>
                    <div className="d-flex gap-2">
                      <Button as={Link} to={`/dashboard/events/${event.id}`} variant="outline-primary" size="sm">
                        View
                      </Button>
                      <Button as={Link} to={`/dashboard/events/${event.id}/edit`} variant="outline-secondary" size="sm">
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

export default Events; 