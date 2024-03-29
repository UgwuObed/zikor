
## User Management API

   As instructed, this project is a RESTful API for user management, allowing users to register, login, view/update their profiles, and perform CRUD operations on user accounts. It also includes additional security measures such as email domain restrictions, password length requirements, and audit logging to hence security for admin registration. I had the option to seed the admin credentials but for security reasons i opted for creating a dedicated route for admin creation and extra security measures. 


## Getting Started

## 1. Clone the Repository:

   Execute the following command to clone the project repository:

   git clone https://github.com/UgwuObed/Apex---Test--Interview.git


## 2. Navigate and Install Dependencies:

   Change directories into the project folder: 
  
## 3. Next, install all the required dependencies using Composer:

   composer install


## 4. Database Setup:

## Configure Database Connection:

   Make sure these credentials are accurate and match your actual database configuration.

## 5. Run Database Migrations:

   Once the connection is configured, execute the following command to create the necessary tables in your database:

   php artisan migrate

## 6. Starting the Server:

   Bring the API to life by running the development server:

   php artisan serve

   With this command, the API will be accessible on your local machine at http://localhost:8000.

## 7. Run Test

  This project utilizes Pest for its testing framework. To run all the tests and ensure everything configured as expected(The phpunit.xml file is designed to run tests with Pest and incorporates environment setup for testing purposes), use the following command:

  ./vendor/bin/pest

## 8. Explore the API with Postman:

 For a comprehensive overview of the API's functionalities and usage, we recommend utilizing the provided Postman collection: https://documenter.getpostman.com/view/17157575/2sA2rAyhJD. This collection meticulously documents all API endpoints, including request methods, parameters, payloads, and expected responses. By interacting with the collection, you can gain valuable insights into how to effectively interact with the API and leverage its features.

 Thank you very much.
