# FoodFlow

## Overview
The **FoodFlow** is a web-based application designed to connect food donors with individuals or organizations in need. Its primary goal is to reduce food waste and combat hunger by facilitating a seamless donation process. The platform enables donors to list surplus food, receivers to claim available donations, and food banks to coordinate resource distribution efficiently.

---

## Features
### 1. User Authentication & Management
- Secure user sign-up, login, and logout.
- Role-based access for **donors** and **receivers**.
- User profile management for personalized experiences.

### 2. Donation Management
- Create, update, and manage food donation entries.
- Validate donation details before submission.
- Track donation history.

### 3. Receiver Dashboard
- View real-time available donations.
- Claim donations based on specific needs.
- Provide additional information for better matching.

### 4. Search Functionality
- Locate nearby food banks or donation centers.
- Filter donations based on location and availability.

### 5. Password Management
- Securely create and reset passwords.

### 6. User Roles & Additional Information
- Collect extra details about donors and receivers.
- Improve the matching process for efficient resource allocation.

### 7. User-Friendly Interface
- Clean, responsive design for seamless navigation.
- Custom styling for an intuitive and visually appealing experience.

---

## Installation
### Prerequisites
- [XAMPP](https://www.apachefriends.org/index.html) or any local server environment.
- PHP 7.4 or higher.
- MySQL database.

### Setup Steps
1. Clone the repository to your local machine:
   ```bash
   git clone https://github.com/your-repo/food-donation-platform.git
   ```
2. Move the project folder to your XAMPP `htdocs` directory:
   ```bash
   mv food-donation-platform c:/xampp/htdocs/
   ```
3. Start Apache and MySQL using the XAMPP Control Panel.
4. Import the database:
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Create a new database (e.g., `food_donation`).
   - Import the SQL file from the `database` folder.
5. Launch the application in your browser:
   ```
   http://localhost/food-donation-platform
   ```

---

## Usage Guide
### 1. Sign Up
- Register as a **donor** or **receiver**.

### 2. Log In
- Access your personalized dashboard.

### 3. Donor Actions
- List available food items for donation.
- Monitor donation history.

### 4. Receiver Actions
- Browse and claim available food donations.
- Filter results based on preferences.

### 5. Search Feature
- Locate nearby food banks or donation centers.
- Use filters for better search results.

---

## File Structure
```
c:/xampp/htdocs/food-donation-platform/
├── css/
│   ├── style.css
│   ├── signup.css
├── images/
├── uploads/
├── login.php
├── signup.php
├── donations.php
├── donationform.php
├── receiver_dashboard.php
├── search-foodBank.php
├── create-password.php
├── test.php
└── database/
    └── food_donation.sql
```

---

## Contributing
We welcome contributions! To contribute:
1. **Fork** the repository.
2. Create a **new branch** for your feature or fix.
3. Submit a **pull request** with a detailed description of your changes.

---

## License
This project is licensed under the **MIT License**. See the `LICENSE` file for more details.

---

## Contact
For any questions or support, please reach out:
- **Email:** pendempavansundar8955@gmail.com

