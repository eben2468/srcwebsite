# School Departments System

This system provides a dedicated section on the SRC website for managing and displaying information about the various schools and faculties. Each department has a profile page containing detailed information, and the system allows administrators to manage department data.

## Department Structure

The system includes the following departments:

- School of Nursing and Midwifery (NURSA)
- School of Theology and Mission (THEMSA)
- School of Education (EDSA)
- Faculty of Science (COSSA)
- Development Studies (DESSA)
- School of Business (SOBSA)

## Key Features

### Main Departments Page (`departments.php`)
- Overview of all departments with basic information
- Attractive cards layout showing department image, code, name, and brief description
- Links to detailed department pages
- Admin functionality to add new departments

### Department Detail Page (`department-detail.php`)
Each department detail page includes:
- Banner image with department title and description
- Tabbed interface for organizing information:
  - **Overview**: General information about the department and contact details
  - **Programs**: List of academic programs offered
  - **Events**: Calendar of upcoming events
  - **Contacts**: Key personnel and their contact information
  - **Documents**: Downloadable resources (handbooks, syllabi, etc.)
  - **Gallery**: Media images related to the department

### Administrative Features
- Create new departments
- Edit existing department information
- Add/manage department events
- Upload department documents
- Add gallery images
- Delete departments, events, documents, staff, and gallery images

### Admin Controls

The system provides a comprehensive set of admin controls to manage all aspects of departments:

1. **From Departments Listing Page:**
   - Add new department
   - Edit existing department details
   - Delete department
   - Quick access to department management

2. **From Department Detail Page:**
   - Edit department information
   - Add/delete events
   - Add/delete documents
   - Add/delete staff members
   - Upload/delete gallery images

All admin actions are accessible through an intuitive UI with confirmation dialogues to prevent accidental deletions.

## File Structure

```
srcwebsite/
├── pages_php/
│   ├── departments.php              # Main departments listing page
│   ├── department-detail.php        # Individual department detail page
│   └── department_handler.php       # Backend script for department operations
├── images/
│   └── departments/                 # Department images
│       └── gallery/                 # Gallery images for each department
├── documents/
│   └── departments/                 # Department documents (PDF files)
├── create_placeholder_files.php     # Utility to create placeholder files
├── department_system.php            # Dashboard interface for department system
└── DEPARTMENT_README.md             # This documentation file
```

## Implementation Details

### Data Structure
Each department contains the following information:
- Basic info: ID, code, name, head, email, phone, description
- Detailed info: Overview text, list of programs
- Events: Title, date, description
- Staff contacts: Name, position, email, phone
- Documents: Title, file path, size
- Gallery: Collection of image paths

### Technologies Used
- PHP for backend logic
- MySQL (simulated in this version) for data storage
- Bootstrap 5 for responsive UI components
- Font Awesome for icons
- CSS3 for custom styling

## Usage

1. Access the main departments page through the sidebar navigation
2. Browse through the list of departments
3. Click on a department card to view detailed information
4. Use the tabs to navigate different sections of department information

## Setup Instructions

1. Create the required directories:
   ```
   mkdir -p images/departments/gallery
   mkdir -p documents/departments
   ```

2. Run the placeholder file creation script:
   ```
   php create_placeholder_files.php
   ```

3. Access the department system dashboard:
   ```
   http://localhost/srcwebsite/department_system.php
   ```

## Future Improvements

1. Implement actual database storage (currently using simulated data)
2. Add image upload functionality in the admin interface
3. Implement search and filtering for departments
4. Add student enrollment statistics
5. Integrate with events system for department-specific events 