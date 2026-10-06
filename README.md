<div align="center">

# 💳 TrueBalance

### Personal Finance Tracking & Real-Time Analytics Platform

[![Symfony](https://img.shields.io/badge/Symfony_8.1-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP_8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Vue.js](https://img.shields.io/badge/Vue_3-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-3178C6?style=for-the-badge&logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS_v4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL_16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Podman](https://img.shields.io/badge/Podman_Compose-892CA0?style=for-the-badge&logo=podman&logoColor=white)](https://podman.io/)
[![JWT](https://img.shields.io/badge/JWT_Auth-000000?style=for-the-badge&logo=jsonwebtokens&logoColor=white)](https://jwt.io/)

</div>

---

## 📋 Descripción General

**TrueBalance** es una aplicación web *Full-Stack* de gestión de finanzas personales diseñada bajo una arquitectura desacoplada y orientada a servicios contenedorizados. Permite a los usuarios registrar ingresos y gastos, clasificar movimientos mediante categorías personalizadas con código de color y visualizar métricas clave (KPIs) como balance neto, tasa de ahorro porcentual y distribución de gastos por periodo.

El backend implementa **Symfony 8.1** sobre **PHP 8.4** exponiendo una API RESTful asegurada mediante tokens **JWT RS256** (`lexik/jwt-authentication-bundle`), delegando los cálculos de agregación financiera directamente al motor de **PostgreSQL** mediante consultas optimizadas en Doctrine QueryBuilder. El frontend está construido con **Vue 3 (Composition API + TypeScript)**, gestión de estado reactivo con **Pinia**, visualización de datos con **Chart.js** e interfaz en modo oscuro con **Tailwind CSS v4**.

---

## ✨ Características Principales

* 🔐 **Autenticación Stateless con JWT:** Registro e inicio de sesión seguros mediante llaves criptográficas asimétricas, guardias de navegación en Vue Router e interceptores de Axios.
* 🌱 **Aprovisionamiento Automático (*Category Seeding*):** Al registrarse un nuevo usuario, el backend genera automáticamente un conjunto base de categorías de ingresos y gastos listas para operar.
* 📊 **Motor de Agregación SQL para Analítica:** Cálculo en tiempo real de ingresos totales, egresos, balance neto, tasa de ahorro (`Savings Rate`) y desglose agrupado por categoría directamente en PostgreSQL.
* 📅 **Filtrado Dinámico por Periodos:** Navegación interactiva por mes/año o consulta de historial completo con actualización reactiva de tarjetas KPI, tabla de movimientos y gráfico *Doughnut*.
* 🏷️ **Gestión de Categorías Personalizadas:** Creación de categorías de ingreso o gasto con selector de paleta de colores y vista previa en vivo.
* 🐳 **Entorno 100% Contenedorizado:** Orquestación multicontenedor lista para levantar con **Podman Compose** o **Docker Compose** (Nginx + PHP-FPM, Node/Vite, PostgreSQL 16 y pgAdmin 4).

---

## 🏗️ Arquitectura y Stack Tecnológico

| Capa | Tecnología | Propósito |
| :--- | :--- | :--- |
| **Backend API** | PHP 8.4 + Symfony 8.1 | API RESTful, lógica de negocio, validación e inyección de dependencias |
| **ORM & Datos** | Doctrine ORM + PostgreSQL 16 | Persistencia relacional, migraciones y consultas de agregación DQL |
| **Seguridad** | LexikJWTAuthenticationBundle + NelmioCors | Autenticación basada en tokens Bearer y políticas CORS |
| **Frontend SPA** | Vue 3 + Vite + TypeScript | Interfaz reactiva con Composition API (`<script setup>`) |
| **Estado & Red** | Pinia + Vue Router + Axios | Gestión de estado global, rutas protegidas e interceptores HTTP |
| **Estilos & UI** | Tailwind CSS v4 + Chart.js + Lucide Icons | Diseño *Dark UI* responsivo, visualización gráfica e iconografía |
| **Infraestructura** | Podman / Docker + Nginx Alpine | Orquestación de contenedores y servidor proxy inverso para PHP-FPM |

---

## 📂 Estructura del Proyecto

```text
TrueBalance/
├── backend-symfony/               # API RESTful en Symfony 8.1 (PHP 8.4)
│   ├── config/                    # Configuración de paquetes, rutas, CORS y llaves JWT
│   ├── migrations/                # Migraciones de esquema de PostgreSQL (Doctrine)
│   ├── src/
│   │   ├── Controller/Api/        # Controladores REST (Auth, Category, Transaction)
│   │   ├── Entity/                # Entidades relacionales (User, Category, Transaction)
│   │   └── Repository/            # Consultas DQL y agregaciones financieras
│   └── Dockerfile                 # Imagen PHP 8.4-FPM con extensiones PostgreSQL e Intl
├── frontend-vue/                  # Single Page Application en Vue 3 + TypeScript
│   ├── public/                    # Activos estáticos e identidad visual (favicon.svg)
│   ├── src/
│   │   ├── api/                   # Cliente Axios e interceptores de autorización JWT
│   │   ├── router/                # Definición de rutas y Navigation Guards
│   │   ├── stores/                # Stores reactivos de Pinia (auth.ts, finance.ts)
│   │   ├── types/                 # Contratos e interfaces TypeScript
│   │   └── views/                 # Vistas principales (LoginView, DashboardView)
│   └── Dockerfile                 # Imagen Node 22 Alpine con servidor Vite HMR
├── nginx/
│   └── default.conf               # Configuración de servidor web Nginx hacia PHP-FPM
└── compose.yml                    # Orquestación de servicios (DB, Backend, Nginx, Frontend, pgAdmin)
```

---

## 🚀 Instalación y Puesta en Marcha

### Prerrequisitos

* **Podman** (con `podman compose`) o **Docker Desktop**.
* **Git**.

### 1. Clonar el repositorio

```bash
git clone [https://github.com/tu-usuario/TrueBalance.git](https://github.com/tu-usuario/TrueBalance.git)
cd TrueBalance
```

### 2. Levantar los contenedores

```bash
podman compose up -d --build
```

### 3. Ejecutar migraciones de base de datos y generar llaves JWT

```bash
# Ejecutar migraciones en PostgreSQL
podman exec -it truebalance_backend php bin/console doctrine:migrations:migrate --no-interaction

# Generar par de llaves criptográficas para JWT (RS256)
podman exec -it truebalance_backend php bin/console lexik:jwt:generate-keypair --skip-if-exists
```

### 4. Acceso a los servicios locales

| Servicio | URL Local | Descripción |
| :--- | :--- | :--- |
| **Frontend (Vue 3)** | `http://localhost:5173` | Interfaz principal de usuario (Dashboard & Auth) |
| **Backend API (Nginx)** | `http://localhost:8000/api` | Endpoints RESTful de Symfony |
| **pgAdmin 4** | `http://localhost:5050` | Cliente web de administración PostgreSQL |
| **PostgreSQL 16** | `localhost:5432` | Base de datos relacional (`truebalance_db`) |

---

## 🔌 Referencia de Endpoints (API REST)

Todos los endpoints bajo `/api` (excepto `/api/register` y `/api/login_check`) requieren el encabezado `Authorization: Bearer <JWT_TOKEN>`.

| Método | Endpoint | Descripción | Autenticación |
| :---: | :--- | :--- | :---: |
| `POST` | `/api/register` | Registra un usuario y genera sus 6 categorías iniciales | Pública |
| `POST` | `/api/login_check` | Autentica credenciales y retorna el token JWT | Pública |
| `GET` | `/api/me` | Retorna el perfil del usuario autenticado | Bearer JWT |
| `GET` | `/api/categories` | Lista todas las categorías del usuario activo | Bearer JWT |
| `POST` | `/api/categories` | Crea una nueva categoría personalizada (`INCOME` o `EXPENSE`) | Bearer JWT |
| `DELETE` | `/api/categories/{id}` | Elimina una categoría (si no tiene transacciones asociadas) | Bearer JWT |
| `GET` | `/api/transactions` | Lista transacciones con filtros opcionales (`startDate`, `endDate`, `type`, `categoryId`) | Bearer JWT |
| `POST` | `/api/transactions` | Registra un nuevo ingreso o gasto | Bearer JWT |
| `DELETE` | `/api/transactions/{id}` | Elimina una transacción del historial del usuario | Bearer JWT |
| `GET` | `/api/transactions/summary` | Calcula KPIs globales y desglose agrupado por categoría en el rango de fechas | Bearer JWT |
