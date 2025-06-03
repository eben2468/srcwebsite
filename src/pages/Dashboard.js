import React from 'react';
import { Routes, Route } from 'react-router-dom';
import { Container, Row, Col } from 'react-bootstrap';
import Sidebar from '../components/dashboard/Sidebar';
import DashboardHome from '../components/dashboard/DashboardHome';
import Events from '../components/dashboard/Events';
import EventDetail from '../components/dashboard/EventDetail';
import News from '../components/dashboard/News';
import Documents from '../components/dashboard/Documents';
import Elections from '../components/dashboard/Elections';
import Users from '../components/dashboard/Users';
import Budget from '../components/dashboard/Budget';
import Settings from '../components/dashboard/Settings';
import NotFound from './NotFound';
import useAuth from '../hooks/useAuth';

const Dashboard = () => {
  const { currentUser } = useAuth();
  const isAdmin = currentUser?.role === 'admin';

  return (
    <div className="dashboard-container">
      <Sidebar />
      <div className="dashboard-content">
        <Routes>
          <Route index element={<DashboardHome />} />
          <Route path="events" element={<Events />} />
          <Route path="events/:id" element={<EventDetail />} />
          <Route path="news" element={<News />} />
          <Route path="documents" element={<Documents />} />
          <Route path="elections" element={<Elections />} />
          
          {/* Admin only routes */}
          {isAdmin && (
            <>
              <Route path="users" element={<Users />} />
              <Route path="budget" element={<Budget />} />
              <Route path="settings" element={<Settings />} />
            </>
          )}
          
          <Route path="*" element={<NotFound />} />
        </Routes>
      </div>
    </div>
  );
};

export default Dashboard; 