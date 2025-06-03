import React from 'react';
import { Row, Col, Card, Button, Table } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { 
  FaCalendarAlt, 
  FaUsers, 
  FaNewspaper, 
  FaVoteYea, 
  FaChartLine, 
  FaBell 
} from 'react-icons/fa';
import useAuth from '../../hooks/useAuth';

const DashboardHome = () => {
  const { currentUser } = useAuth();
  const isAdmin = currentUser?.role === 'admin';

  // Mock data for the dashboard
  const stats = [
    { title: 'Upcoming Events', count: 12, icon: FaCalendarAlt, color: 'primary' },
    { title: 'News Items', count: 8, icon: FaNewspaper, color: 'info' },
    { title: 'Active Elections', count: 1, icon: FaVoteYea, color: 'success' },
    { title: isAdmin ? 'Total Users' : 'Registered Students', count: isAdmin ? 250 : 1200, icon: FaUsers, color: 'warning' }
  ];

  // Mock recent events data
  const recentEvents = [
    { id: 1, name: 'Orientation Week', date: '2023-08-15', status: 'Upcoming' },
    { id: 2, name: 'Leadership Workshop', date: '2023-08-20', status: 'Upcoming' },
    { id: 3, name: 'Cultural Festival', date: '2023-09-05', status: 'Upcoming' },
    { id: 4, name: 'Career Fair', date: '2023-09-15', status: 'Planning' }
  ];

  // Mock notifications
  const notifications = [
    { id: 1, message: 'New document uploaded: Budget Proposal 2023', time: '2 hours ago' },
    { id: 2, message: 'Election voting starts in 3 days', time: '5 hours ago' },
    { id: 3, message: 'New event created: Academic Excellence Awards', time: '1 day ago' },
    { id: 4, message: 'System maintenance scheduled for this weekend', time: '2 days ago' }
  ];

  return (
    <div>
      <h2 className="mb-4">Dashboard Overview</h2>
      
      {/* Stats Cards */}
      <Row className="mb-4">
        {stats.map((stat, index) => (
          <Col md={3} sm={6} className="mb-3" key={index}>
            <Card className="h-100 border-0 shadow-sm">
              <Card.Body className="d-flex flex-column align-items-center">
                <div className={`text-${stat.color} mb-3`} style={{ fontSize: '2.5rem' }}>
                  <stat.icon />
                </div>
                <h3 className="h2 mb-2">{stat.count}</h3>
                <p className="text-muted mb-0">{stat.title}</p>
              </Card.Body>
            </Card>
          </Col>
        ))}
      </Row>
      
      <Row>
        {/* Recent Events */}
        <Col lg={7} className="mb-4">
          <Card className="border-0 shadow-sm h-100">
            <Card.Header className="bg-white">
              <div className="d-flex justify-content-between align-items-center">
                <h5 className="mb-0">Recent Events</h5>
                <Button as={Link} to="/dashboard/events" variant="outline-primary" size="sm">View All</Button>
              </div>
            </Card.Header>
            <Card.Body>
              <Table responsive borderless hover>
                <thead className="table-light">
                  <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {recentEvents.map(event => (
                    <tr key={event.id}>
                      <td>{event.name}</td>
                      <td>{event.date}</td>
                      <td>
                        <span className={`badge bg-${event.status === 'Upcoming' ? 'success' : 'warning'}`}>
                          {event.status}
                        </span>
                      </td>
                      <td>
                        <Button as={Link} to={`/dashboard/events/${event.id}`} variant="link" size="sm">Details</Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            </Card.Body>
          </Card>
        </Col>
        
        {/* Notifications */}
        <Col lg={5} className="mb-4">
          <Card className="border-0 shadow-sm h-100">
            <Card.Header className="bg-white">
              <div className="d-flex justify-content-between align-items-center">
                <h5 className="mb-0">
                  <FaBell className="me-2" /> Notifications
                </h5>
                <Button variant="outline-primary" size="sm">Mark All Read</Button>
              </div>
            </Card.Header>
            <Card.Body className="p-0">
              <div className="list-group list-group-flush">
                {notifications.map(notification => (
                  <div key={notification.id} className="list-group-item list-group-item-action">
                    <div className="d-flex w-100 justify-content-between">
                      <p className="mb-1">{notification.message}</p>
                    </div>
                    <small className="text-muted">{notification.time}</small>
                  </div>
                ))}
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>
      
      {/* Quick Actions */}
      {isAdmin && (
        <Row className="mb-4">
          <Col>
            <Card className="border-0 shadow-sm">
              <Card.Header className="bg-white">
                <h5 className="mb-0">Quick Actions</h5>
              </Card.Header>
              <Card.Body>
                <div className="d-flex gap-2 flex-wrap">
                  <Button as={Link} to="/dashboard/events/new" variant="outline-primary">
                    <FaCalendarAlt className="me-2" /> Create Event
                  </Button>
                  <Button as={Link} to="/dashboard/news/new" variant="outline-info">
                    <FaNewspaper className="me-2" /> Post Announcement
                  </Button>
                  <Button as={Link} to="/dashboard/elections/new" variant="outline-success">
                    <FaVoteYea className="me-2" /> Setup Election
                  </Button>
                  <Button as={Link} to="/dashboard/users/new" variant="outline-warning">
                    <FaUsers className="me-2" /> Add User
                  </Button>
                </div>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      )}
    </div>
  );
};

export default DashboardHome; 