import React from 'react';
import { Nav } from 'react-bootstrap';
import { NavLink } from 'react-router-dom';
import { 
  FaHome, 
  FaCalendarAlt, 
  FaUsers, 
  FaFileAlt, 
  FaVoteYea, 
  FaMoneyBillWave, 
  FaCog, 
  FaNewspaper 
} from 'react-icons/fa';
import useAuth from '../../hooks/useAuth';

const Sidebar = () => {
  const { currentUser } = useAuth();
  const isAdmin = currentUser?.role === 'admin';

  return (
    <div className="dashboard-sidebar">
      <div className="px-3 py-4">
        <h5 className="text-white">Dashboard</h5>
        <p className="text-light small mb-0">Welcome, {currentUser?.name}</p>
      </div>
      <Nav className="flex-column">
        <Nav.Link 
          as={NavLink} 
          to="/dashboard" 
          end
          className={({ isActive }) => 
            `d-flex align-items-center px-3 py-2 ${isActive ? 'bg-primary' : ''}`
          }
        >
          <FaHome className="me-2" /> Overview
        </Nav.Link>
        
        <Nav.Link 
          as={NavLink} 
          to="/dashboard/events" 
          className={({ isActive }) => 
            `d-flex align-items-center px-3 py-2 ${isActive ? 'bg-primary' : ''}`
          }
        >
          <FaCalendarAlt className="me-2" /> Events
        </Nav.Link>
        
        <Nav.Link 
          as={NavLink} 
          to="/dashboard/news" 
          className={({ isActive }) => 
            `d-flex align-items-center px-3 py-2 ${isActive ? 'bg-primary' : ''}`
          }
        >
          <FaNewspaper className="me-2" /> News & Announcements
        </Nav.Link>
        
        <Nav.Link 
          as={NavLink} 
          to="/dashboard/documents" 
          className={({ isActive }) => 
            `d-flex align-items-center px-3 py-2 ${isActive ? 'bg-primary' : ''}`
          }
        >
          <FaFileAlt className="me-2" /> Documents
        </Nav.Link>
        
        <Nav.Link 
          as={NavLink} 
          to="/dashboard/elections" 
          className={({ isActive }) => 
            `d-flex align-items-center px-3 py-2 ${isActive ? 'bg-primary' : ''}`
          }
        >
          <FaVoteYea className="me-2" /> Elections
        </Nav.Link>
        
        {isAdmin && (
          <>
            <div className="sidebar-heading px-3 py-2 mt-2 text-muted small text-uppercase">
              Administration
            </div>
            
            <Nav.Link 
              as={NavLink} 
              to="/dashboard/users" 
              className={({ isActive }) => 
                `d-flex align-items-center px-3 py-2 ${isActive ? 'bg-primary' : ''}`
              }
            >
              <FaUsers className="me-2" /> User Management
            </Nav.Link>
            
            <Nav.Link 
              as={NavLink} 
              to="/dashboard/budget" 
              className={({ isActive }) => 
                `d-flex align-items-center px-3 py-2 ${isActive ? 'bg-primary' : ''}`
              }
            >
              <FaMoneyBillWave className="me-2" /> Budget
            </Nav.Link>
            
            <Nav.Link 
              as={NavLink} 
              to="/dashboard/settings" 
              className={({ isActive }) => 
                `d-flex align-items-center px-3 py-2 ${isActive ? 'bg-primary' : ''}`
              }
            >
              <FaCog className="me-2" /> Settings
            </Nav.Link>
          </>
        )}
      </Nav>
    </div>
  );
};

export default Sidebar; 