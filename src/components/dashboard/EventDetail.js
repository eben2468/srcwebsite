import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Card, Button, Row, Col, Badge, Table, ProgressBar, Spinner, Alert } from 'react-bootstrap';
import { FaCalendarAlt, FaMapMarkerAlt, FaUsers, FaArrowLeft, FaEdit, FaTrash } from 'react-icons/fa';
import { eventsAPI } from '../../services/api';

const EventDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [event, setEvent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchEvent = async () => {
      try {
        const response = await eventsAPI.getEventById(id);
        setEvent(response.data);
      } catch (err) {
        setError('Failed to load event details. The event may not exist.');
        console.error('Error fetching event:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchEvent();
  }, [id]);

  const handleDelete = async () => {
    if (window.confirm('Are you sure you want to delete this event?')) {
      try {
        await eventsAPI.deleteEvent(id);
        navigate('/dashboard/events');
      } catch (err) {
        setError('Failed to delete event');
        console.error('Error deleting event:', err);
      }
    }
  };

  if (loading) {
    return (
      <div className="text-center p-5">
        <Spinner animation="border" variant="primary" />
        <p className="mt-3">Loading event details...</p>
      </div>
    );
  }

  if (error) {
    return (
      <Alert variant="danger">
        <Alert.Heading>Error</Alert.Heading>
        <p>{error}</p>
        <Button variant="outline-danger" onClick={() => navigate('/dashboard/events')}>
          <FaArrowLeft className="me-2" /> Back to Events
        </Button>
      </Alert>
    );
  }

  if (!event) {
    return (
      <Alert variant="warning">
        <Alert.Heading>Event Not Found</Alert.Heading>
        <p>The event you are looking for does not exist or has been removed.</p>
        <Button variant="outline-primary" onClick={() => navigate('/dashboard/events')}>
          <FaArrowLeft className="me-2" /> Back to Events
        </Button>
      </Alert>
    );
  }

  // Calculate registration percentage
  const registrationPercentage = Math.round((event.registrations / event.capacity) * 100);
  let variant = 'success';
  if (registrationPercentage > 90) variant = 'danger';
  else if (registrationPercentage > 75) variant = 'warning';

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <Button variant="outline-primary" onClick={() => navigate('/dashboard/events')}>
          <FaArrowLeft className="me-2" /> Back to Events
        </Button>
        <div>
          <Button variant="outline-secondary" className="me-2" onClick={() => navigate(`/dashboard/events/${id}/edit`)}>
            <FaEdit className="me-2" /> Edit Event
          </Button>
          <Button variant="outline-danger" onClick={handleDelete}>
            <FaTrash className="me-2" /> Delete Event
          </Button>
        </div>
      </div>

      <Card className="border-0 shadow-sm mb-4">
        <Card.Body>
          <Row>
            <Col md={8}>
              <h2>{event.name}</h2>
              <div className="d-flex flex-wrap gap-3 mb-3">
                <div className="d-flex align-items-center text-muted">
                  <FaCalendarAlt className="me-2" /> {event.date}
                </div>
                <div className="d-flex align-items-center text-muted">
                  <FaMapMarkerAlt className="me-2" /> {event.location}
                </div>
                <Badge bg={
                  event.status === 'Upcoming' ? 'success' : 
                  event.status === 'Planning' ? 'warning' : 'secondary'
                }>
                  {event.status}
                </Badge>
              </div>
              <p>{event.description}</p>
            </Col>
            <Col md={4}>
              <Card className="bg-light h-100">
                <Card.Body>
                  <h5 className="mb-3">Event Details</h5>
                  <Table size="sm" borderless>
                    <tbody>
                      <tr>
                        <td className="text-muted">Organizer:</td>
                        <td>{event.organizer}</td>
                      </tr>
                      <tr>
                        <td className="text-muted">Capacity:</td>
                        <td>{event.capacity} attendees</td>
                      </tr>
                      <tr>
                        <td className="text-muted">Registrations:</td>
                        <td>{event.registrations} attendees</td>
                      </tr>
                    </tbody>
                  </Table>
                </Card.Body>
              </Card>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      <Card className="border-0 shadow-sm mb-4">
        <Card.Header className="bg-white">
          <h5 className="mb-0">Registration Status</h5>
        </Card.Header>
        <Card.Body>
          <div className="mb-3">
            <div className="d-flex justify-content-between mb-2">
              <span>Registration Progress</span>
              <span>{registrationPercentage}%</span>
            </div>
            <ProgressBar 
              now={registrationPercentage} 
              variant={variant} 
            />
            <div className="text-muted small mt-1">
              {event.registrations} out of {event.capacity} spots filled
            </div>
          </div>

          <div className="d-flex gap-2">
            <Button variant="primary">
              <FaUsers className="me-2" /> Manage Attendees
            </Button>
            <Button variant="outline-secondary">
              Export Attendee List
            </Button>
          </div>
        </Card.Body>
      </Card>

      {/* Additional content could be added here, such as:
      - Schedule details
      - Event tasks/checklist
      - Budget information
      - Team members/organizers
      */}
    </div>
  );
};

export default EventDetail; 