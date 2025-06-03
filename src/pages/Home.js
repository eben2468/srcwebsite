import React from 'react';
import { Row, Col, Card, Button, Container, Carousel } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { FaCalendarAlt, FaNewspaper, FaUsers, FaFileAlt } from 'react-icons/fa';

const Home = () => {
  return (
    <div>
      {/* Hero Section */}
      <div className="bg-primary text-white py-5 mb-5">
        <Container>
          <Row className="align-items-center">
            <Col lg={6} className="mb-4 mb-lg-0">
              <h1 className="display-4 fw-bold mb-3">Students' Representative Council</h1>
              <p className="lead mb-4">
                Empowering students through effective representation, transparent governance,
                and meaningful engagement with the university community.
              </p>
              <div className="d-flex gap-3">
                <Button as={Link} to="/login" variant="light" size="lg">
                  Login
                </Button>
                <Button as={Link} to="/contact" variant="outline-light" size="lg">
                  Contact Us
                </Button>
              </div>
            </Col>
            <Col lg={6}>
              <img 
                src="https://via.placeholder.com/600x400?text=SRC+Management+System" 
                alt="SRC Management" 
                className="img-fluid rounded shadow" 
              />
            </Col>
          </Row>
        </Container>
      </div>

      {/* Features Section */}
      <Container className="mb-5">
        <h2 className="text-center mb-4">What We Offer</h2>
        <Row>
          <Col md={3} className="mb-4">
            <Card className="h-100 text-center">
              <Card.Body>
                <div className="mb-3 text-primary fs-1">
                  <FaCalendarAlt />
                </div>
                <Card.Title>Event Management</Card.Title>
                <Card.Text>
                  Plan, organize, and promote campus events and activities for the student body.
                </Card.Text>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3} className="mb-4">
            <Card className="h-100 text-center">
              <Card.Body>
                <div className="mb-3 text-primary fs-1">
                  <FaNewspaper />
                </div>
                <Card.Title>News & Announcements</Card.Title>
                <Card.Text>
                  Stay updated with the latest campus news, announcements, and SRC initiatives.
                </Card.Text>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3} className="mb-4">
            <Card className="h-100 text-center">
              <Card.Body>
                <div className="mb-3 text-primary fs-1">
                  <FaUsers />
                </div>
                <Card.Title>Student Representation</Card.Title>
                <Card.Text>
                  Advocate for student interests and concerns to university administration.
                </Card.Text>
              </Card.Body>
            </Card>
          </Col>
          <Col md={3} className="mb-4">
            <Card className="h-100 text-center">
              <Card.Body>
                <div className="mb-3 text-primary fs-1">
                  <FaFileAlt />
                </div>
                <Card.Title>Document Repository</Card.Title>
                <Card.Text>
                  Access important documents, policies, and resources for students.
                </Card.Text>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      </Container>

      {/* Recent Events Section */}
      <Container className="mb-5">
        <h2 className="text-center mb-4">Upcoming Events</h2>
        <Carousel className="mb-4">
          <Carousel.Item>
            <img
              className="d-block w-100"
              src="https://via.placeholder.com/1200x400?text=Orientation+Week"
              alt="Orientation Week"
            />
            <Carousel.Caption>
              <h3>Orientation Week</h3>
              <p>Welcome new students and help them adjust to campus life</p>
            </Carousel.Caption>
          </Carousel.Item>
          <Carousel.Item>
            <img
              className="d-block w-100"
              src="https://via.placeholder.com/1200x400?text=Leadership+Workshop"
              alt="Leadership Workshop"
            />
            <Carousel.Caption>
              <h3>Leadership Workshop</h3>
              <p>Develop essential leadership skills for student representatives</p>
            </Carousel.Caption>
          </Carousel.Item>
          <Carousel.Item>
            <img
              className="d-block w-100"
              src="https://via.placeholder.com/1200x400?text=Cultural+Festival"
              alt="Cultural Festival"
            />
            <Carousel.Caption>
              <h3>Cultural Festival</h3>
              <p>Celebrate diversity and cultural exchange on campus</p>
            </Carousel.Caption>
          </Carousel.Item>
        </Carousel>
        <div className="text-center">
          <Button as={Link} to="/events" variant="outline-primary">View All Events</Button>
        </div>
      </Container>

      {/* Call to Action */}
      <div className="bg-light py-5">
        <Container className="text-center">
          <h2 className="mb-3">Get Involved with SRC</h2>
          <p className="lead mb-4">
            Join us in creating a vibrant campus community. There are many ways to contribute!
          </p>
          <Button as={Link} to="/contact" variant="primary" size="lg">
            Contact Us
          </Button>
        </Container>
      </div>
    </div>
  );
};

export default Home; 