# SRC Management System - Setup Instructions

This document provides detailed instructions on how to set up and run the SRC Management System.

## Prerequisites

Before you can run the application, you need to install the following software:

1. **Node.js and npm**: The application is built using React, which requires Node.js to run.
   - Download and install Node.js from [nodejs.org](https://nodejs.org/)
   - This will also install npm (Node Package Manager)
   - Verify installation by running `node -v` and `npm -v` in your terminal

2. **Git** (optional): If you want to clone the repository.
   - Download and install Git from [git-scm.com](https://git-scm.com/)

## Installation Steps

### 1. Clone or Download the Repository

If you have Git installed:
```bash
git clone <repository-url>
cd srcwebsite
```

Alternatively, you can download the ZIP file of the repository and extract it.

### 2. Install Dependencies

Navigate to the project directory in your terminal and run:
```bash
npm install
```

This will install all the required dependencies defined in `package.json`.

### 3. Start the Development Server

To start the application in development mode, run:
```bash
npm start
```

This will start the development server and automatically open the application in your default web browser. If it doesn't open automatically, you can access it at [http://localhost:3000](http://localhost:3000).

## Project Structure

The project follows a standard React application structure:

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

## Login Credentials

For testing purposes, you can use the following credentials:

- **Admin User**:
  - Email: `admin@example.com`
  - Password: `password`

- **Regular User**:
  - Email: `user@example.com`
  - Password: `password`

## Building for Production

When you're ready to deploy the application, you can create a production build by running:
```bash
npm run build
```

This will create a `build` directory with optimized files ready for deployment.

## Troubleshooting

1. **Node.js or npm not recognized**:
   - Ensure Node.js is properly installed
   - Check if the installation path is added to your system's PATH variable
   - Restart your terminal or computer after installation

2. **Dependencies installation issues**:
   - Try deleting the `node_modules` folder and `package-lock.json` file, then run `npm install` again
   - Check for any error messages and search for solutions online

3. **Port conflicts**:
   - If port 3000 is already in use, the development server will prompt you to use a different port

## Support

If you encounter any issues or have questions about the SRC Management System, please contact the development team at support@srcmanagementsystem.com. 