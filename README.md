# SRC Management System

A comprehensive Students' Representative Council management system to streamline administrative processes and enhance communication between the SRC and the student body.

## Features

- User authentication for administrators and public users
- Dashboard for SRC administrators with various management modules
- Public portal for students to view announcements, events, and resources
- Event management system
- Document repository
- Communication tools
- Online voting and election management

## Technology Stack

- Frontend: React.js with Bootstrap for responsive design
- API Communication: Axios for HTTP requests
- Routing: React Router for client-side navigation
- State Management: Context API for application state
- UI Components: React Bootstrap and custom components

## Project Structure

```
src/
  ├── assets/        # Static assets like images, fonts, etc.
  ├── components/    # Reusable UI components
  ├── context/       # React Context for state management
  ├── hooks/         # Custom React hooks
  ├── pages/         # Page components for each route
  ├── services/      # API services and data fetching logic
  └── utils/         # Utility functions and helpers
```

## Getting Started

1. Clone the repository
2. Install dependencies: `npm install`
3. Start the development server: `npm start`
4. Open [http://localhost:3000](http://localhost:3000) in your browser

## Available Scripts

- `npm start`: Run the app in development mode
- `npm test`: Launch the test runner
- `npm run build`: Build the app for production
- `npm run eject`: Eject from Create React App configuration

## License

This project is licensed under the MIT License. 