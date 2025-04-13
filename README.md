# Gaming Zone Project

## Overview
The Gaming Zone project is a web application designed to manage games, users, and scores. It utilizes a MySQL database for data storage and PHP for server-side scripting.

## Project Structure
```
gaming-zone
├── backend
│   ├── dbconfig
│   │   ├── dbconn.php        # Database connection management
│   │   └── config.php        # Database configuration settings
│   ├── setup
│   │   ├── create_tables.php  # Script to create database tables
│   │   ├── seed_data.php      # Script to populate database with initial data
│   │   └── drop_tables.php    # Script to drop database tables
│   └── sql
│       ├── tables
│       │   ├── users.sql      # SQL for creating the 'users' table
│       │   ├── games.sql      # SQL for creating the 'games' table
│       │   └── scores.sql     # SQL for creating the 'scores' table
│       └── seeds
│           ├── users_seed.sql  # SQL for seeding the 'users' table
│           └── games_seed.sql  # SQL for seeding the 'games' table
├── index.php                   # Entry point for the application
└── README.md                   # Project documentation
```

## Setup Instructions
1. **Database Configuration**: Update the `backend/dbconfig/config.php` file with your database credentials.
2. **Create Tables**: Run the `backend/setup/create_tables.php` script to create the necessary tables in your database.
3. **Seed Data**: Execute the `backend/setup/seed_data.php` script to populate the tables with initial data.
4. **Drop Tables**: If needed, use the `backend/setup/drop_tables.php` script to remove the tables from the database.

## Usage
- Access the application through `index.php`.
- Follow the instructions in the setup section to configure and initialize the database.

## Contributing
Feel free to contribute to the project by submitting issues or pull requests.