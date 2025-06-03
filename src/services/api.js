import axios from 'axios';

// This is a mock API service for the SRC Management System
// In a real application, this would connect to a backend server

// Create an axios instance with default configuration
const api = axios.create({
  baseURL: 'https://api.example.com', // Replace with your actual API base URL
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
});

// Add a request interceptor to add the auth token to requests
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('authToken');
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add a response interceptor to handle errors
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // Handle unauthorized errors (token expired, etc.)
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('user');
      localStorage.removeItem('authToken');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Mock API functions
// In a real application, these would make actual API calls

// Auth APIs
export const authAPI = {
  login: async (email, password) => {
    // In a real app, this would be an actual API call
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        if (email === 'admin@example.com' && password === 'password') {
          const userData = {
            id: 1,
            name: 'Admin User',
            email: 'admin@example.com',
            role: 'admin'
          };
          localStorage.setItem('authToken', 'mock-jwt-token-for-admin');
          resolve({ data: userData });
        } else if (email === 'user@example.com' && password === 'password') {
          const userData = {
            id: 2,
            name: 'Regular User',
            email: 'user@example.com',
            role: 'user'
          };
          localStorage.setItem('authToken', 'mock-jwt-token-for-user');
          resolve({ data: userData });
        } else {
          reject({ message: 'Invalid email or password' });
        }
      }, 800); // Simulate network delay
    });
  },
  
  logout: async () => {
    localStorage.removeItem('authToken');
    return { success: true };
  },
  
  getCurrentUser: async () => {
    const token = localStorage.getItem('authToken');
    if (!token) {
      return null;
    }
    
    // In a real app, this would validate the token with the server
    if (token === 'mock-jwt-token-for-admin') {
      return {
        id: 1,
        name: 'Admin User',
        email: 'admin@example.com',
        role: 'admin'
      };
    } else if (token === 'mock-jwt-token-for-user') {
      return {
        id: 2,
        name: 'Regular User',
        email: 'user@example.com',
        role: 'user'
      };
    }
    
    return null;
  }
};

// Events APIs
export const eventsAPI = {
  getEvents: async () => {
    // Mock data
    return {
      data: [
        { id: 1, name: 'Orientation Week', date: '2023-08-15', location: 'Main Campus', status: 'Upcoming' },
        { id: 2, name: 'Leadership Workshop', date: '2023-08-20', location: 'Conference Hall', status: 'Upcoming' },
        { id: 3, name: 'Cultural Festival', date: '2023-09-05', location: 'Student Center', status: 'Upcoming' },
        { id: 4, name: 'Career Fair', date: '2023-09-15', location: 'Exhibition Hall', status: 'Planning' },
        { id: 5, name: 'Academic Excellence Awards', date: '2023-10-10', location: 'Auditorium', status: 'Planning' },
        { id: 6, name: 'Sports Tournament', date: '2023-07-10', location: 'Sports Complex', status: 'Completed' }
      ]
    };
  },
  
  getEventById: async (id) => {
    // Mock data
    const events = [
      { id: 1, name: 'Orientation Week', date: '2023-08-15', location: 'Main Campus', status: 'Upcoming', description: 'Welcome event for new students', organizer: 'Student Affairs', capacity: 500, registrations: 320 },
      { id: 2, name: 'Leadership Workshop', date: '2023-08-20', location: 'Conference Hall', status: 'Upcoming', description: 'Workshop to develop leadership skills', organizer: 'SRC', capacity: 100, registrations: 75 },
      { id: 3, name: 'Cultural Festival', date: '2023-09-05', location: 'Student Center', status: 'Upcoming', description: 'Celebration of diverse cultures', organizer: 'Cultural Committee', capacity: 1000, registrations: 650 }
    ];
    
    const event = events.find(e => e.id === parseInt(id));
    if (event) {
      return { data: event };
    }
    throw new Error('Event not found');
  },
  
  createEvent: async (eventData) => {
    // In a real app, this would create a new event in the database
    return { data: { id: 7, ...eventData } };
  },
  
  updateEvent: async (id, eventData) => {
    // In a real app, this would update an event in the database
    return { data: { id, ...eventData } };
  },
  
  deleteEvent: async (id) => {
    // In a real app, this would delete an event from the database
    return { data: { success: true } };
  }
};

// News APIs
export const newsAPI = {
  getNews: async () => {
    // Mock data
    return {
      data: [
        { id: 1, title: 'SRC Elections Announced', date: '2023-07-20', author: 'Admin', status: 'Published' },
        { id: 2, title: 'New Campus Facilities Opening', date: '2023-07-15', author: 'Admin', status: 'Published' },
        { id: 3, title: 'Student Achievements 2023', date: '2023-07-10', author: 'Admin', status: 'Published' },
        { id: 4, title: 'Upcoming Cultural Week', date: '2023-07-05', author: 'Admin', status: 'Published' },
        { id: 5, title: 'Academic Calendar Update', date: '2023-08-01', author: 'Admin', status: 'Draft' },
        { id: 6, title: 'Campus Maintenance Notice', date: '2023-08-05', author: 'Admin', status: 'Draft' }
      ]
    };
  }
};

// Documents APIs
export const documentsAPI = {
  getDocuments: async () => {
    // Mock data
    return {
      data: [
        { id: 1, name: 'SRC Constitution', category: 'Governance', uploadDate: '2023-06-15', size: '1.2 MB' },
        { id: 2, name: 'Budget Proposal 2023', category: 'Finance', uploadDate: '2023-07-10', size: '3.5 MB' },
        { id: 3, name: 'Event Planning Guidelines', category: 'Events', uploadDate: '2023-06-30', size: '890 KB' },
        { id: 4, name: 'Election Procedures', category: 'Elections', uploadDate: '2023-07-15', size: '1.5 MB' },
        { id: 5, name: 'Annual Report 2022', category: 'Reports', uploadDate: '2023-02-10', size: '5.2 MB' },
        { id: 6, name: 'Student Handbook', category: 'General', uploadDate: '2023-05-20', size: '2.7 MB' }
      ]
    };
  }
};

// Elections APIs
export const electionsAPI = {
  getElections: async () => {
    // Mock data
    return {
      data: [
        { 
          id: 1, 
          title: 'SRC Presidential Election', 
          startDate: '2023-09-10', 
          endDate: '2023-09-15', 
          status: 'Upcoming',
          candidates: 4,
          voterTurnout: 0
        },
        { 
          id: 2, 
          title: 'Faculty Representatives', 
          startDate: '2023-09-12', 
          endDate: '2023-09-17', 
          status: 'Upcoming',
          candidates: 12,
          voterTurnout: 0
        },
        { 
          id: 3, 
          title: 'Student Senate Election', 
          startDate: '2023-08-01', 
          endDate: '2023-08-05', 
          status: 'Active',
          candidates: 8,
          voterTurnout: 45
        },
        { 
          id: 4, 
          title: 'Sports Committee Election', 
          startDate: '2023-07-05', 
          endDate: '2023-07-10', 
          status: 'Completed',
          candidates: 6,
          voterTurnout: 72
        },
        { 
          id: 5, 
          title: 'Cultural Committee Election', 
          startDate: '2023-06-10', 
          endDate: '2023-06-15', 
          status: 'Completed',
          candidates: 5,
          voterTurnout: 65
        }
      ]
    };
  },
  
  getActiveElection: async () => {
    // Mock data
    return {
      data: {
        id: 3,
        title: 'Student Senate Election',
        startDate: '2023-08-01',
        endDate: '2023-08-05',
        description: 'Election for student senate representatives from all departments',
        totalVoters: 1500,
        votesSubmitted: 675,
        positions: [
          { title: 'President', candidates: 3, votesCounted: 675 },
          { title: 'Vice President', candidates: 2, votesCounted: 670 },
          { title: 'Secretary', candidates: 3, votesCounted: 668 }
        ]
      }
    };
  }
};

// Users APIs (Admin only)
export const usersAPI = {
  getUsers: async () => {
    // Mock data
    return {
      data: [
        { id: 1, name: 'Admin User', email: 'admin@example.com', role: 'admin', status: 'Active', lastLogin: '2023-07-25' },
        { id: 2, name: 'John Doe', email: 'john@example.com', role: 'user', status: 'Active', lastLogin: '2023-07-24' },
        { id: 3, name: 'Jane Smith', email: 'jane@example.com', role: 'user', status: 'Active', lastLogin: '2023-07-20' },
        { id: 4, name: 'Robert Johnson', email: 'robert@example.com', role: 'user', status: 'Inactive', lastLogin: '2023-06-15' },
        { id: 5, name: 'Emily Brown', email: 'emily@example.com', role: 'moderator', status: 'Active', lastLogin: '2023-07-22' },
        { id: 6, name: 'Michael Wilson', email: 'michael@example.com', role: 'user', status: 'Active', lastLogin: '2023-07-23' }
      ]
    };
  }
};

// Budget APIs (Admin only)
export const budgetAPI = {
  getBudgetOverview: async () => {
    // Mock data
    return {
      data: {
        totalBudget: 120000,
        allocated: 87500,
        spent: 62300,
        remaining: 57700,
        fiscalYear: '2023/2024'
      }
    };
  },
  
  getBudgetCategories: async () => {
    // Mock data
    return {
      data: [
        { id: 1, name: 'Events & Programs', allocated: 35000, spent: 22500, remaining: 12500 },
        { id: 2, name: 'Administrative', allocated: 20000, spent: 15800, remaining: 4200 },
        { id: 3, name: 'Welfare & Support', allocated: 18000, spent: 12000, remaining: 6000 },
        { id: 4, name: 'Marketing & Communications', allocated: 15000, spent: 9000, remaining: 6000 },
        { id: 5, name: 'Training & Development', allocated: 12000, spent: 3000, remaining: 9000 },
        { id: 6, name: 'Contingency', allocated: 10000, spent: 0, remaining: 10000 },
        { id: 7, name: 'Capital Expenses', allocated: 10000, spent: 0, remaining: 10000 }
      ]
    };
  },
  
  getTransactions: async () => {
    // Mock data
    return {
      data: [
        { id: 1, date: '2023-07-20', description: 'Cultural Festival Supplies', category: 'Events & Programs', amount: 3500, type: 'Expense' },
        { id: 2, date: '2023-07-15', description: 'Office Supplies', category: 'Administrative', amount: 1200, type: 'Expense' },
        { id: 3, date: '2023-07-10', description: 'Leadership Workshop', category: 'Training & Development', amount: 3000, type: 'Expense' },
        { id: 4, date: '2023-07-05', description: 'Student Support Fund', category: 'Welfare & Support', amount: 5000, type: 'Expense' },
        { id: 5, date: '2023-07-01', description: 'University Funding Allocation', category: 'Income', amount: 30000, type: 'Income' }
      ]
    };
  }
};

export default api; 