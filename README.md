# Symfony 6.4 LTS with LangChain Integration

This repository showcases a comprehensive integration between Symfony 6.4 LTS and LangChain, demonstrating how to create a modern, secure API architecture with asynchronous processing capabilities.

PS: This README.md is AI Generated - but at a quick glance it's pretty accurate. I shall update/fix at a later stage should the need arise - please contact me should you have found any discrepancy / missing information.

## 📑 Table of Contents

- [Features](#-features)
- [Technology Stack](#️-technology-stack)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Usage](#-usage)
- [Project Structure](#-project-structure)
- [Development](#-development)
- [Configuration](#-configuration)
- [Key Implementation Features](#-key-implementation-features)
  - [RESTful API with JWT & IP Protection](#restful-api-with-jwt--ip-protection)
  - [Google Places API Integration](#google-places-api-integration)
  - [Filesystem Cache](#filesystem-cache)
  - [Entity __toString Implementation](#entity-__tostring-implementation)
  - [Event Listeners](#event-listeners)
  - [LangChain Integration](#langchain-integration)
- [License](#-license)

## 🚀 Features

- **PHP 8.4** with FPM on Alpine Linux
- **Nginx** as web server
- **Percona MySQL 8.0** for database
- **LangChain Agent** integration for AI-powered processing
- **Symfony Messenger** with AWS SQS integration for asynchronous job processing
- **Google Places API** integration for location autocomplete and details
- **RESTful API** with comprehensive documentation via Swagger/OpenAPI
- **JWT Authentication** with IP-based protection for secure API access
- **Event Listeners** for request/response manipulation and logging
- **Entity __toString implementation** for improved debugging and logging
- **Filesystem Cache** for optimized performance
- **Supervisord** for process management
- **Composer** for dependency management
- **Docker aliases** for simplified command execution

## 🛠️ Technology Stack

- **PHP 8.4** on Alpine Linux
- **Symfony 6.4 LTS**
- **Docker & Docker Compose**
- **Nginx** web server
- **Percona MySQL 8.0**
- **LangChain** for AI agent integration
- **Google Places API** for geocoding and location data
- **Symfony Messenger** with Amazon SQS for asynchronous processing
- **JWT Authentication** via LexikJWTAuthenticationBundle
- **IP-based API Protection** for enhanced security
- **Filesystem Cache** for performance optimization
- **OpenAPI/Swagger** via NelmioApiDocBundle

## 📋 Requirements

- Docker and Docker Compose
- Git
- AWS Account (for SQS integration)
- Environment for LangChain Python agent

## 🚀 Installation

### Clone the repository

```bash
git clone https://github.com/tanasecosminromeo/sf6-env.git
cd sf6-env
```

### Configure environment variables

Create a `.env` file with the following content (adjust values as needed):

```
# Network settings
IP_SUBNET=172.16.0

# Database settings
MYSQL_ROOT_PASSWORD=rootpassword
MYSQL_DATABASE=sf6app
MYSQL_USER=sf6app
MYSQL_PASSWORD=sf6password

# AWS SQS settings
AWS_ACCESS_KEY=your_aws_access_key
AWS_SECRET_KEY=your_aws_secret_key
AWS_REGION=your_aws_region
AWS_SQS_QUEUE=your_sqs_queue_name

# JWT settings
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_jwt_passphrase

# Google Places API
GOOGLE_API_KEY=your_google_api_key

# LangChain settings
SCW_SECRET_KEY=scalewaykey
OPENAI_BASE=https://api.scaleway.ai/627cedfa-4b13-4ebc-85ce-2296bd3449e5/v1
```

### Start the containers

```bash
docker compose up -d
```

### Install Symfony dependencies

```bash
docker compose exec app composer install
```

### Generate JWT keys

```bash
docker compose exec app mkdir -p config/jwt
docker compose exec app openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:your_jwt_passphrase
docker compose exec app openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:your_jwt_passphrase
```

### Set up database

```bash
docker compose exec app php bin/console doctrine:database:create
docker compose exec app php bin/console doctrine:migrations:migrate
```

### Load fixtures (optional)

```bash
docker compose exec app php bin/console doctrine:fixtures:load
```

## 🔍 Usage

### Accessing the application

Since the application uses an IP subnet for container networking, you'll need to add an entry to your hosts file:

```
# Add to /etc/hosts (Linux/Mac) or C:\Windows\System32\drivers\etc\hosts (Windows)
172.16.0.101 sf6.local
```

Then access the application at:
```
http://sf6.local
```

### API Authentication

To obtain a JWT token for API authentication:

```bash
curl -X POST -H "Content-Type: application/json" http://sf6.local/api/login_check \
  -d '{"username":"your_username","password":"your_password"}'
```

Example response:
```json
{
  "token": "eyJ0eXAiO..."
}
```

### Making authenticated API requests

Use the JWT token in your requests:

```bash
curl -X GET -H "Authorization: Bearer eyJ0eXAiO..." http://sf6.local/api/protected-endpoint
```

### Symfony Console

Run Symfony console commands:

```bash
docker compose exec app php bin/console [command]
```

### Database access

Connect to the MySQL database:

```bash
docker compose exec db mysql -usf6app -psf6password sf6app
```

### Interacting with LangChain

You can access the LangChain container with:

```bash
docker compose exec langchain bash
```

### Docker Aliases

The project includes a `.bash` file with useful aliases to simplify common Docker commands. Source this file to use these shortcuts:

```bash
source .bash
```

Available aliases:

| Alias | Command | Description |
|-------|---------|-------------|
| `a` | `docker compose exec app $@` | Execute a command in the app container |
| `d` | `docker compose exec db $@` | Execute a command in the database container |
| `p` | `docker compose exec --user app app php bin/console $@` | Run Symfony console commands |
| `c` | `docker compose exec --user app app composer $@` | Run Composer commands |
| `u` | `docker compose exec --user app -e APP_ENV=test app bin/phpunit $@` | Run PHPUnit tests |
| `openapi` | `docker compose exec --user app app php bin/console nelmio:apidoc:dump --format=yaml > public/docs/openapi.yaml` | Generate OpenAPI documentation |
| `l` | `docker compose logs $@` | View container logs |
| `dc` | `docker compose $@` | Run docker compose commands |

Examples:

```bash
# Run a Symfony command
p cache:clear

# Execute composer install
c install

# Run PHPUnit tests
u tests/Controller

# View logs
l -f app
```

## 📝 Project Structure

```
symfony6-environment/
├── compose.yaml              # Docker Compose configuration with IP-based networking
├── config/                   # Symfony configuration
│   ├── packages/             # Package configurations
│   └── services.yaml         # Service definitions
├── docker/                   # Docker configuration
│   ├── db/                   # Database container config
│   ├── langchain/            # LangChain Python agent configuration
│   ├── php/                  # PHP-FPM & Supervisor config
│   └── web/                  # Nginx configuration
├── public/                   # Web server public directory
│   └── index.php             # Application entry point
├── src/                      # Application source code
│   ├── Controller/           # API controllers
│   ├── Entity/               # Doctrine entities with __toString implementations
│   ├── EventListener/        # Event listeners for request/response handling
│   ├── Message/              # Symfony Messenger message classes
│   ├── MessageHandler/       # Message handlers for processing SQS messages
│   ├── Repository/           # Doctrine repositories
│   └── LangChain/            # LangChain integration scripts
├── templates/                # Twig templates
└── .env                      # Environment configuration
```

## 🧑‍💻 Development

### Supervisord Management

The application uses Supervisord to manage PHP-FPM and Messenger workers:

```bash
# View status of all processes
docker compose exec app supervisorctl status

# Restart PHP-FPM
docker compose exec app supervisorctl restart php-fpm

# Restart Messenger workers
docker compose exec app supervisorctl restart messenger-consume:*
```

### Working with Symfony Messenger and AWS SQS

This project demonstrates asynchronous processing using Symfony Messenger with AWS SQS transport. Messages are dispatched from Symfony controllers and processed by dedicated handlers.

Example of dispatching a message:

```php
// In your controller
public function dispatchJob(MessageBusInterface $messageBus)
{
    $job = new ProcessJob('Some data to process');
    $messageBus->dispatch($job);
    
    return new JsonResponse(['status' => 'Job dispatched']);
}
```

The message is sent to SQS and consumed by a worker process.

### Testing

```bash
# Run all tests
docker compose exec app php bin/phpunit

# Run specific test suite
docker compose exec app php bin/phpunit tests/Controller/SomeControllerTest.php
```

## 🔧 Configuration

### Environment Variables

Create a `.env.local` file to override the default environment variables:

```
# Database
DATABASE_URL="mysql://sf6app:sf6password@db:3306/sf6app"

# JWT Authentication
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase
JWT_TOKEN_TTL=3600

# AWS/SQS
AWS_ACCESS_KEY=your_aws_access_key
AWS_SECRET_KEY=your_aws_secret_key
AWS_REGION=your_aws_region
AWS_SQS_QUEUE=your_queue_name

# Google Places API
GOOGLE_API_KEY=your_google_api_key

# LangChain
SCW_SECRET_KEY=scalewaykey
OPENAI_BASE=https://api.scaleway.ai/627cedfa-4b13-4ebc-85ce-2296bd3449e5/v1
```

## 🚀 Key Implementation Features

### RESTful API with JWT & IP Protection

The application showcases how to build secure APIs with multiple layers of protection:

1. **JWT Authentication**: Implements token-based authentication using LexikJWTAuthenticationBundle
2. **IP-Based Protection**: Restricts API access to specific IP addresses or ranges
3. **Rate Limiting**: Prevents abuse through request throttling

Example IP protection configuration:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
            # Custom IP-based voter
            access_control:
                - { path: ^/api, roles: IS_AUTHENTICATED_FULLY, ips: [127.0.0.1, 172.16.0.0/24] }
                - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

### Filesystem Cache

The project implements Symfony's Filesystem Cache for optimized performance in various components:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.filesystem
        system: cache.adapter.filesystem
        directory: '%kernel.cache_dir%/pools'
        default_psr6_provider: 'app.cache.provider'
```

Example usage in a service:

```php
// src/Service/CacheService.php
class CacheService
{
    private $cache;
    
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }
    
    public function getCachedData(string $key, callable $callback, int $ttl = 3600)
    {
        $cacheItem = $this->cache->getItem($key);
        
        if (!$cacheItem->isHit()) {
            $result = $callback();
            $cacheItem->set($result);
            $cacheItem->expiresAfter($ttl);
            $this->cache->save($cacheItem);
            
            return $result;
        }
        
        return $cacheItem->get();
    }
}
```

Implementation in a controller:

```php
// src/Controller/ApiController.php
public function getData(Request $request, CacheService $cacheService)
{
    $data = $cacheService->getCachedData('api_data_' . $request->get('id'), function() {
        // Expensive operation to fetch data
        return $this->repository->findWithComplexJoins();
    }, 1800); // Cache for 30 minutes
    
    return $this->json($data);
}
```

### Google Places API Integration

The project integrates with Google Places API for location search and details retrieval:

#### 1. Place Autocomplete

The application uses the Google Places Autocomplete API to provide real-time location suggestions as users type:

```php
// src/Service/GooglePlacesService.php
public function getPlaceSuggestions(string $query, ?string $countryCode = null): array
{
    $params = [
        'input' => $query,
        'key' => $this->googleApiKey,
        'types' => 'geocode'
    ];
    
    if ($countryCode) {
        $params['components'] = 'country:' . $countryCode;
    }
    
    $response = $this->httpClient->request(
        'GET',
        'https://maps.googleapis.com/maps/api/place/autocomplete/json',
        ['query' => $params]
    );
    
    return $this->cache->getCachedData(
        'place_autocomplete_' . md5($query . $countryCode),
        function() use ($response) {
            return $response->toArray();
        },
        86400 // Cache for 24 hours
    );
}
```

#### 2. Place Details

After a user selects a location from autocomplete suggestions, the application fetches detailed information:

```php
// src/Service/GooglePlacesService.php
public function getPlaceDetails(string $placeId): array
{
    $params = [
        'place_id' => $placeId,
        'key' => $this->googleApiKey,
        'fields' => 'geometry,formatted_address,name,place_id'
    ];
    
    $response = $this->httpClient->request(
        'GET',
        'https://maps.googleapis.com/maps/api/place/details/json',
        ['query' => $params]
    );
    
    return $this->cache->getCachedData(
        'place_details_' . $placeId,
        function() use ($response) {
            $data = $response->toArray();
            if (isset($data['result'])) {
                // Extract location coordinates
                $location = $data['result']['geometry']['location'] ?? null;
                $data['result']['coordinates'] = $location ? [
                    'lat' => $location['lat'],
                    'lng' => $location['lng']
                ] : null;
            }
            return $data;
        },
        604800 // Cache for 1 week
    );
}
```

#### 3. Controller Implementation

```php
// src/Controller/LocationController.php
/**
 * @Route("/api/locations/autocomplete", methods={"GET"})
 */
public function autocomplete(Request $request, GooglePlacesService $placesService): JsonResponse
{
    $query = $request->query->get('query');
    $countryCode = $request->query->get('country');
    
    $suggestions = $placesService->getPlaceSuggestions($query, $countryCode);
    
    return $this->json($suggestions);
}

/**
 * @Route("/api/locations/{placeId}", methods={"GET"})
 */
public function getLocationDetails(string $placeId, GooglePlacesService $placesService): JsonResponse
{
    $details = $placesService->getPlaceDetails($placeId);
    
    return $this->json($details);
}
```

This integration enables the application to provide a seamless location search experience with accurate geographic data.

### Entity __toString Implementation

The project demonstrates effective use of the `__toString()` method in entities for improved debugging, logging, and display in admin interfaces:

```php
// src/Entity/Job.php
class Job
{
    // ...
    
    public function __toString(): string
    {
        return sprintf(
            'Job #%d [%s] - Status: %s, Created: %s',
            $this->id,
            $this->title,
            $this->status,
            $this->createdAt->format('Y-m-d H:i:s')
        );
    }
}
```

### Event Listeners

The application uses Symfony event listeners for various cross-cutting concerns:

1. **Request/Response Logging**: Tracks all API requests and responses
2. **Exception Handling**: Provides consistent error responses
3. **Authentication Events**: Tracks login attempts and failures
4. **Entity Lifecycle Events**: Automatically manages timestamps and auditing

Example event listener:

```php
// src/EventListener/ApiRequestListener.php
class ApiRequestListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->headers->has('X-API-Key')) {
            throw new AccessDeniedHttpException('Missing API key');
        }
        
        // Validate API key...
    }
}
```

### LangChain Integration

The project shows how to integrate a Python-based LangChain agent with Symfony:

1. **Symfony Controller**: Receives requests and dispatches messages to SQS
2. **Message Handler**: Processes SQS messages and communicates with LangChain
3. **LangChain Agent**: Python container that processes natural language inputs using Scaleway AI models
4. **Response Handling**: Webhook pattern for asynchronous results
5. **Caching**: Filesystem cache to store processed results

#### LangChain Configuration

The LangChain integration uses Scaleway AI for language model access:

```python
# src/LangChain/agent.py
import os
from langchain.llms import OpenAI
from langchain.agents import Tool, AgentExecutor, create_react_agent
from langchain.chains import LLMChain
from langchain.prompts import PromptTemplate

# Configure LangChain with Scaleway AI
os.environ["OPENAI_API_KEY"] = os.getenv("SCW_SECRET_KEY")
os.environ["OPENAI_API_BASE"] = os.getenv("OPENAI_BASE")

# Initialize the language model
llm = OpenAI(temperature=0)

# Define tools and agent
tools = [
    Tool(
        name="LocationSearch",
        func=search_locations,
        description="Search for locations by name or description"
    )
]

prompt = PromptTemplate.from_template("""
You are a location expert. Use the tools available to answer questions about geographic locations.

Question: {question}
""")

agent = create_react_agent(llm, tools, prompt)
agent_executor = AgentExecutor.from_agent_and_tools(agent=agent, tools=tools, verbose=True)
```

#### Integration Flow:

1. User submits query via API
2. Symfony dispatches message to SQS
3. Message handler sends data to LangChain container
4. LangChain processes with Scaleway AI and returns result
5. Results are stored in database and available via API

Example code for caching LangChain results:

```php
// src/Service/LangChainService.php
class LangChainService
{
    private $cache;
    private $langchainClient;
    
    public function __construct(CacheItemPoolInterface $cache, LangChainClientInterface $langchainClient)
    {
        $this->cache = $cache;
        $this->langchainClient = $langchainClient;
    }
    
    public function processQuery(string $query): array
    {
        $cacheKey = 'langchain_query_' . md5($query);
        $cacheItem = $this->cache->getItem($cacheKey);
        
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
        
        $result = $this->langchainClient->processQuery($query);
        
        $cacheItem->set($result);
        $cacheItem->expiresAfter(3600); // Cache for 1 hour
        $this->cache->save($cacheItem);
        
        return $result;
    }
}
```

## 📄 License

This project is licensed under the MIT License.
