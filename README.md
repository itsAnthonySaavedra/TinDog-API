# TinDog API
The robust Laravel backend powering the TinDog application. This API handles user authentication, data management, and business logic for the TinDog platform.

## 🚀 Getting Started

### Prerequisites
*   **PHP 8.2+**
*   **Composer**
*   **PostgreSQL** (or Supabase)

### Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/itsAnthonySaavedra/TinDog-API.git
    cd TinDog-API
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Environment Setup**
    *   Copy the example environment file:
        ```bash
        cp .env.example .env
        ```
    *   Update the `.env` file with your database credentials (Supabase recommended).
    *   **Important:** If using Supabase Transaction Pooler (IPv4), ensure you use port `6543` and the correct pooler host.

4.  **Generate App Key**
    ```bash
    php artisan key:generate
    ```

5.  **Run Migrations**
    ```bash
    php artisan migrate
    ```

6.  **Serve the Application**
    ```bash
    php artisan serve
    ```
    The API will be available at `http://127.0.0.1:8000`.

## 🔑 API Endpoints

*   **Auth:** `/api/user-login`, `/api/admin-login`, `/api/register`
*   **Users:** `/api/users`, `/api/users/{id}`
*   **Admin:** `/api/admin/profile` (via user endpoint)

## 🤝 Contributing
Please refer to the [TinDog-PHP Repository](https://github.com/itsAnthonySaavedra/TinDog-PHP) for the frontend application and contribution guidelines.
