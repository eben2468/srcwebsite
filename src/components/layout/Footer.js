import React from 'react';
import { Container, Row, Col } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { FaFacebook, FaTwitter, FaInstagram, FaEnvelope } from 'react-icons/fa';

const Footer = () => {
  const year = new Date().getFullYear();
  
  return (
    <footer className="bg-dark text-light py-4 mt-5">
      <Container>
        <Row>
          <Col md={4} className="mb-4 mb-md-0">
            <h5>SRC Management System</h5>
            <p className="text-muted">
              A comprehensive platform for Students' Representative Council 
              to manage activities and communicate with the student body.
            </p>
          </Col>
          <Col md={4} className="mb-4 mb-md-0">
            <h5>Quick Links</h5>
            <ul className="list-unstyled">
              <li><Link to="/" className="text-decoration-none text-muted">Home</Link></li>
              <li><Link to="/about" className="text-decoration-none text-muted">About</Link></li>
              <li><Link to="/contact" className="text-decoration-none text-muted">Contact</Link></li>
              <li><Link to="/login" className="text-decoration-none text-muted">Login</Link></li>
            </ul>
          </Col>
          <Col md={4}>
            <h5>Connect With Us</h5>
            <div className="d-flex gap-3 fs-4 mb-3">
              <a href="https://facebook.com" className="text-light" target="_blank" rel="noopener noreferrer">
                <FaFacebook />
              </a>
              <a href="https://twitter.com" className="text-light" target="_blank" rel="noopener noreferrer">
                <FaTwitter />
              </a>
              <a href="https://instagram.com" className="text-light" target="_blank" rel="noopener noreferrer">
                <FaInstagram />
              </a>
              <a href="mailto:info@src.edu" className="text-light">
                <FaEnvelope />
              </a>
            </div>
            <p className="text-muted mb-0">Email: info@src.edu</p>
            <p className="text-muted mb-0">Phone: +123-456-7890</p>
          </Col>
        </Row>
        <hr className="my-4" />
        <Row>
          <Col className="text-center text-muted">
            <p className="mb-0">&copy; {year} SRC Management System. All rights reserved.</p>
          </Col>
        </Row>
      </Container>
    </footer>
  );
};

export default Footer; 