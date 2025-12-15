# Hyperf API Docs

[![Latest Stable Version](https://img.shields.io/packagist/v/tangwei/apidocs)](https://packagist.org/packages/tangwei/apidocs)
[![Total Downloads](https://img.shields.io/packagist/dt/tangwei/apidocs)](https://packagist.org/packages/tangwei/apidocs)
[![License](https://img.shields.io/packagist/l/tangwei/apidocs)](https://github.com/tw2066/api-docs)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue)](https://www.php.net)

English | [中文](./README.md)

Automatic Swagger/OpenAPI documentation generator for the [Hyperf](https://github.com/hyperf/hyperf) framework, supporting Swoole/Swow engines, providing an elegant and powerful API documentation solution.

## ✨ Features

- 🚀 **Auto Generation** - Automatically generate OpenAPI 3.0 documentation based on PHP 8 Attributes
- 🎯 **Type Safety** - Support DTO mode with automatic parameter mapping to PHP classes
- 📝 **Multiple UIs** - Support Swagger UI, Knife4j, Redoc, RapiDoc, Scalar, and more
- ✅ **Data Validation** - Integrate Hyperf validator with rich validation annotations
- 🔒 **Security** - Support API Token and multiple security schemes
- 🔄 **Type Support** - Support arrays, recursion, nesting, enums, and other complex types
- 🎨 **Flexible Config** - Customizable global response format, route prefix, etc.
- 📦 **Out of Box** - Zero configuration ready to use with deep customization support

## 📋 Requirements

- PHP >= 8.1
- Hyperf >= 3.0
- Swoole >= 5.0 or Swow

## 💡 Important Notes

- Union types are not supported for parameter mapping to PHP classes
- Controller methods should return specific types (including simple types) for better documentation generation
- Use `#[ApiResponse]` annotation when return types cannot fully express the response structure

## 📦 Installation

```bash
composer require tangwei/apidocs
```

By default, Swagger UI is used. You can optionally install Knife4j UI (recommended):

```bash
composer require tangwei/knife4j-ui
```

## 🚀 Quick Start

### 1. Publish Configuration

```bash
php bin/hyperf.php vendor:publish tangwei/apidocs
```

Configuration file will be published to `config/autoload/api_docs.php`

<details>
  <summary>Complete Configuration Reference (Click to expand)</summary>
  <p>

> Full configuration example: config/autoload/api_docs.php

```php
<?php
use Hyperf\ApiDocs\DTO\GlobalResponse;
use function Hyperf\Support\env;

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Swagger Service
    |--------------------------------------------------------------------------
    |
    | Set to false to disable swagger service
    |
    */
    'enable' => env('APP_ENV') !== 'prod',

    /*
    |--------------------------------------------------------------------------
    | Swagger File Format
    |--------------------------------------------------------------------------
    |
    | Supports json and yaml
    |
    */
    'format' => 'json',

    /*
    |--------------------------------------------------------------------------
    | Swagger File Output Path
    |--------------------------------------------------------------------------
    */
    'output_dir' => BASE_PATH . '/runtime/container',

    /*
    |--------------------------------------------------------------------------
    | Proxy Class Path
    |--------------------------------------------------------------------------
    */
    'proxy_dir' => BASE_PATH . '/runtime/container/proxy',

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    */
    'prefix_url' => env('API_DOCS_PREFIX_URL', '/swagger'),

    /*
    |--------------------------------------------------------------------------
    | Swagger Resources CDN Path
    |--------------------------------------------------------------------------
    */
    'prefix_swagger_resources' => 'https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.27.1',

    /*
    |--------------------------------------------------------------------------
    | Global Response Class
    |--------------------------------------------------------------------------
    |
    | Global response format like: [code=>200, data=>null]
    | Use with ApiVariable annotation, see GlobalResponse class example
    | Response format can be unified using AOP
    |
    */
    // 'global_return_responses_class' => GlobalResponse::class,

    /*
    |--------------------------------------------------------------------------
    | Replace Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Use ApiModelProperty annotation values for validation error messages
    |
    */
    'validation_custom_attributes' => true,

    /*
    |--------------------------------------------------------------------------
    | DTO Default Value Level
    |--------------------------------------------------------------------------
    |
    | 0: Default (no default values)
    | 1: Simple types get default values, complex types with ? get null
    |    - Simple type defaults: int:0 float:0 string:'' bool:false array:[] mixed:null
    | 2: (Use with caution) Includes level 1 and complex types (except union) get null
    |
    */
    'dto_default_value_level' => 0,

    /*
    |--------------------------------------------------------------------------
    | Global Responses
    |--------------------------------------------------------------------------
    */
    'responses' => [
        ['response' => 401, 'description' => 'Unauthorized'],
        ['response' => 500, 'description' => 'System error'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Swagger Basic Configuration
    |--------------------------------------------------------------------------
    |
    | This maps to OpenAPI object
    |
    */
    'swagger' => [
        'info' => [
            'title' => 'API Documentation',
            'version' => '1.0.0',
            'description' => 'API Documentation',
        ],
        'servers' => [
            [
                'url' => 'http://127.0.0.1:9501',
                'description' => 'API Server',
            ],
        ],
        'components' => [
            'securitySchemes' => [
                [
                    'securityScheme' => 'Authorization',
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'Authorization',
                ],
            ],
        ],
        'security' => [
            ['Authorization' => []],
        ],
        'externalDocs' => [
            'description' => 'GitHub',
            'url' => 'https://github.com/tw2066/api-docs',
        ],
    ],
];
```
  </p>
</details>

### 2. Basic Configuration

```php
<?php
// config/autoload/api_docs.php
return [
    // Enable documentation service (recommended to disable in production)
    'enable' => env('APP_ENV') !== 'prod',
    
    // Documentation access path
    'prefix_url' => env('API_DOCS_PREFIX_URL', '/swagger'),
    
    // Basic information
    'swagger' => [
        'info' => [
            'title' => 'API Documentation',
            'version' => '1.0.0',
            'description' => 'Project API Documentation',
        ],
        'servers' => [
            [
                'url' => 'http://127.0.0.1:9501',
                'description' => 'API Server',
            ],
        ],
    ],
];
```

### 3. Start Server

```bash
php bin/hyperf.php start
```

After successful startup, visit `http://your-host:9501/swagger` to view the API documentation.

```
[INFO] Swagger docs url at http://0.0.0.0:9501/swagger
[INFO] Worker#0 started.
[INFO] HTTP Server listening at 0.0.0.0:9501
```

## 📖 Usage Guide

### Basic Example

#### 1. Define DTO Class

```php
<?php

namespace App\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Integer;
use Hyperf\DTO\Annotation\Validation\Between;

class UserRequest
{
    #[ApiModelProperty('Username')]
    #[Required]
    public string $username;

    #[ApiModelProperty('Age')]
    #[Required]
    #[Integer]
    #[Between(1, 120)]
    public int $age;

    #[ApiModelProperty('Email')]
    public ?string $email = null;
}
```

#### 2. Write Controller

```php
<?php

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\ApiDocs\Annotation\Api;
use Hyperf\ApiDocs\Annotation\ApiOperation;
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\Valid;
use App\Request\UserRequest;

#[Controller(prefix: '/user')]
#[Api(tags: 'User Management', position: 1)]
class UserController
{
    #[GetMapping(path: 'list')]
    #[ApiOperation(summary: 'Get user list')]
    public function list(#[RequestQuery] #[Valid] UserRequest $request): array
    {
        return [
            ['id' => 1, 'username' => 'admin'],
            ['id' => 2, 'username' => 'user'],
        ];
    }

    #[PostMapping(path: 'create')]
    #[ApiOperation(summary: 'Create user')]
    public function create(#[RequestBody] #[Valid] UserRequest $request): array
    {
        return [
            'id' => 1,
            'username' => $request->username,
            'age' => $request->age,
        ];
    }
}
```

## 🎨 Annotation Reference

### Controller Annotations

#### `#[Api]` - Controller Tag

```php
#[Api(
    tags: 'User Management',   // Tag name (supports array)
    description: 'User operations',  // Description
    position: 1,               // Sort position
    hidden: false             // Whether to hide
)]
```

#### `#[ApiOperation]` - API Operation

```php
#[ApiOperation(
    summary: 'Create user',    // Summary
    description: 'Detailed description',  // Detailed description
    deprecated: false,         // Whether deprecated
    security: true,           // Whether authentication required
    hidden: false            // Whether to hide
)]
```

#### `#[ApiResponse]` - Response Definition

```php
// Simple type response
#[ApiResponse(PhpType::INT, 200, 'Success')]

// Object response
#[ApiResponse(UserResponse::class, 200, 'User information')]

// Array response
#[ApiResponse([UserResponse::class], 200, 'User list')]

// Paginated response
#[ApiResponse(new Page([UserResponse::class]), 200, 'Paginated data')]
```

### Parameter Annotations

#### `#[RequestBody]` - Body Parameters

Get JSON body parameters from POST/PUT/PATCH requests:

```php
public function create(#[RequestBody] #[Valid] UserRequest $request)
{
    // $request automatically populated with body data
}
```

#### `#[RequestQuery]` - Query Parameters

Get URL query parameters (GET parameters):

```php
public function list(#[RequestQuery] #[Valid] QueryRequest $request)
{
    // $request automatically populated with query parameters
}
```

#### `#[RequestFormData]` - Form Parameters

Get form data (multipart/form-data):

```php
#[ApiFormData(name: 'photo', format: 'binary')]
public function upload(#[RequestFormData] UploadRequest $formData)
{
    $file = $this->request->file('photo');
    // Handle file upload
}
```

#### `#[RequestHeader]` - Header Parameters

Get request header information:

```php
public function auth(#[RequestHeader] #[Valid] AuthHeader $header)
{
    // $header automatically populated with header data
}
```

**Generic Type Support Example:**

PHP doesn't natively support generics, but you can achieve similar functionality using `#[ApiVariable]`:

```php
use Hyperf\ApiDocs\Annotation\ApiVariable;

class Page
{
    public int $total;

    #[ApiVariable]
    public array $content;

    public function __construct(array $content, int $total = 0)
    {
        $this->content = $content;
        $this->total = $total;
    }
}
```

Controller usage:

```php
#[ApiOperation('Paginated query')]
#[GetMapping(path: 'page')]
#[ApiResponse(new Page([UserResponse::class]))]
public function page(#[RequestQuery] PageQuery $query): Page
{
    // Return paginated data
}
```

### Property Annotations

#### `#[ApiModelProperty]` - Property Description

```php
#[ApiModelProperty(
    value: 'Username',      // Property description
    example: 'admin',       // Example value
    required: true,         // Whether required
    hidden: false          // Whether to hide
)]
public string $username;
```

#### `#[ApiHeader]` - Header Definition

```php
// Global header (class level)
#[ApiHeader('X-Request-Id')]

// Method level header
#[ApiHeader(
    name: 'Authorization',
    required: true,
    type: 'string',
    description: 'Bearer token'
)]
```

#### `#[ApiSecurity]` - Security Authentication

Priority: Method > Class > Global

```php
// Use default authentication
#[ApiSecurity('Authorization')]

// Method level override
#[ApiOperation(summary: 'Login', security: false)]  // No authentication required
```

> ⚠️ **Note**: A method cannot inject both `RequestBody` and `RequestFormData` simultaneously

## ✅ Data Validation

### Built-in Validation Annotations

The component provides rich validation annotations:

```php
use Hyperf\DTO\Annotation\Validation\*;

class UserRequest
{
    #[Required]                        // Required
    #[Max(50)]                         // Max length
    public string $username;

    #[Required]
    #[Integer]                         // Integer
    #[Between(1, 120)]                 // Range
    public int $age;

    #[Email]                           // Email format
    public ?string $email;

    #[Url]                             // URL format
    public ?string $website;

    #[Regex('/^1[3-9]\d{9}$/')]       // Regex validation
    public ?string $mobile;

    #[In(['male', 'female'])]          // Enum values
    public ?string $gender;

    #[Date]                            // Date format
    public ?string $birthday;
}
```

> 💡 **Tip**: Simply add the `#[Valid]` annotation to controller method parameters to enable validation

```php
public function create(#[RequestBody] #[Valid] UserRequest $request)
{
    // Validation is automatically executed
}
```

### Custom Validation

#### Using Validation Annotation

```php
// Support Laravel-style validation rules
#[Validation('required|string|min:3|max:50')]
public string $username;

// Array element validation
#[Validation('integer', customKey: 'ids.*')]
public array $ids;
```

#### Custom Validation Annotation

```php
<?php

namespace App\Validation;

use Attribute;
use Hyperf\DTO\Annotation\Validation\BaseValidation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Mobile extends BaseValidation
{
    protected $rule = 'regex:/^1[3-9]\d{9}$/';
    
    public function __construct(string $messages = 'Invalid mobile format')
    {
        parent::__construct($messages);
    }
}
```

Using custom validation:

```php
use App\Validation\Mobile;

class RegisterRequest
{
    #[Required]
    #[Mobile]
    public string $phone;
}
```

## 🔧 Advanced Features

### Array Type Support

#### Method 1: Using PHPDoc

```php
/**
 * @var Address[]
 */
#[ApiModelProperty('Address list')]
public array $addresses;

/**
 * @var int[]
 */
#[ApiModelProperty('ID list')]
public array $ids;
```

#### Method 2: Using ArrayType Annotation

```php
use Hyperf\DTO\Annotation\ArrayType;

#[ApiModelProperty('Address list')]
#[ArrayType(Address::class)]
public array $addresses;

#[ApiModelProperty('Tag list')]
#[ArrayType('string')]
public array $tags;
```

### Nested Objects

```php
class UserRequest
{
    public string $name;
    
    // Nested object
    #[ApiModelProperty('Address info')]
    public Address $address;
    
    /**
     * @var Address[]
     */
    #[ApiModelProperty('Multiple addresses')]
    public array $addresses;
}

class Address
{
    public string $province;
    public string $city;
    public string $street;
}
```

### Enum Support

```php
use Hyperf\DTO\Type\PhpType;

enum StatusEnum: int
{
    case PENDING = 0;
    case ACTIVE = 1;
    case INACTIVE = 2;
}

class OrderRequest
{
    #[ApiModelProperty('Order status')]
    public StatusEnum $status;
}
```

### Global Response Format

Configure global response wrapper class:

```php
// config/autoload/api_docs.php
return [
    'global_return_responses_class' => \App\DTO\GlobalResponse::class,
];
```

Define global response class:

```php
<?php

namespace App\DTO;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\ApiVariable;

class GlobalResponse
{
    #[ApiModelProperty('Status code')]
    public int $code = 0;

    #[ApiModelProperty('Message')]
    public string $message = 'success';

    #[ApiVariable]
    #[ApiModelProperty('Response data')]
    public mixed $data = null;
}
```

### File Upload

```php
#[PostMapping(path: 'upload')]
#[ApiOperation(summary: 'File upload')]
#[ApiFormData(name: 'file', format: 'binary', required: true)]
#[ApiFormData(name: 'description', type: 'string')]
public function upload(#[RequestFormData] UploadRequest $request)
{
    $file = $this->request->file('file');
    // Handle file upload
    return ['url' => '/uploads/file.jpg'];
}
```

## 🔧 Advanced Features

### Array Type Support

#### Method 1: Using PHPDoc

```php
/**
 * @var Address[]
 */
#[ApiModelProperty('Address list')]
public array $addresses;

/**
 * @var int[]
 */
#[ApiModelProperty('ID list')]
public array $ids;
```

#### Method 2: Using ArrayType Annotation

```php
use Hyperf\DTO\Annotation\ArrayType;

#[ApiModelProperty('Address list')]
#[ArrayType(Address::class)]
public array $addresses;

#[ApiModelProperty('Tag list')]
#[ArrayType('string')]
public array $tags;
```

### Nested Objects

```php
class UserRequest
{
    public string $name;
    
    // Nested object
    #[ApiModelProperty('Address info')]
    public Address $address;
    
    /**
     * @var Address[]
     */
    #[ApiModelProperty('Multiple addresses')]
    public array $addresses;
}

class Address
{
    public string $province;
    public string $city;
    public string $street;
}
```

### Enum Support

```php
use Hyperf\DTO\Type\PhpType;

enum StatusEnum: int
{
    case PENDING = 0;
    case ACTIVE = 1;
    case INACTIVE = 2;
}

class OrderRequest
{
    #[ApiModelProperty('Order status')]
    public StatusEnum $status;
}
```

### Global Response Format

Configure global response wrapper class:

```php
// config/autoload/api_docs.php
return [
    'global_return_responses_class' => \App\DTO\GlobalResponse::class,
];
```

Define global response class:

```php
<?php

namespace App\DTO;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\ApiVariable;

class GlobalResponse
{
    #[ApiModelProperty('Status code')]
    public int $code = 0;

    #[ApiModelProperty('Message')]
    public string $message = 'success';

    #[ApiVariable]
    #[ApiModelProperty('Response data')]
    public mixed $data = null;
}
```

### File Upload

```php
#[PostMapping(path: 'upload')]
#[ApiOperation(summary: 'File upload')]
#[ApiFormData(name: 'file', format: 'binary', required: true)]
#[ApiFormData(name: 'description', type: 'string')]
public function upload(#[RequestFormData] UploadRequest $request)
{
    $file = $this->request->file('file');
    // Handle file upload
    return ['url' => '/uploads/file.jpg'];
}
```

## 🎭 Multiple UI Interfaces

Access different UI interfaces:

- **Swagger UI**: `http://your-host:9501/swagger`
- **Knife4j**: `http://your-host:9501/swagger/knife4j`
- **Redoc**: `http://your-host:9501/swagger/redoc`
- **RapiDoc**: `http://your-host:9501/swagger/rapidoc`
- **Scalar**: `http://your-host:9501/swagger/scalar`

## ⚙️ Configuration Reference

### DTO Data Mapping

> api-docs depends on the DTO component. For more details, see [DTO Documentation](https://github.com/hyperf/dto)

#### `#[Dto]` Annotation

Mark as DTO class:

```php
use Hyperf\DTO\Annotation\Dto;

#[Dto]
class DemoQuery
{
}
```

- Can set return format `#[Dto(Convert::SNAKE)]` to batch convert keys to snake_case
- `Dto` annotation doesn't generate documentation, use `JSONField` annotation to generate docs

#### `#[JSONField]` Annotation

Used to set property aliases:

```php
use Hyperf\DTO\Annotation\Dto;
use Hyperf\DTO\Annotation\JSONField;

#[Dto]
class DemoQuery
{
    #[ApiModelProperty('This is an alias')]
    #[JSONField('alias_name')]
    #[Required]
    public string $name;
}
```

- Setting `JSONField` generates proxy class with `alias_name` property
- Both request and response use `alias_name` as the field name

### RPC Support

[Return PHP Object](https://hyperf.wiki/3.1/#/en/json-rpc?id=returning-php-objects)

Configure in aspects.php:

```php
return [
    \Hyperf\DTO\Aspect\ObjectNormalizerAspect::class
]
```

After importing `symfony/serializer (^5.0)` and `symfony/property-access (^5.0)`, configure mapping in dependencies.php:

```php
use Hyperf\Serializer\SerializerFactory;
use Hyperf\Serializer\Serializer;

return [
    Hyperf\Contract\NormalizerInterface::class => new SerializerFactory(Serializer::class),
];
```

## 💡 Best Practices

### 1. DTO Class Design

- Use meaningful class names like `CreateUserRequest`, `UserResponse`
- Add `ApiModelProperty` annotation for each property
- Separate Request and Response definitions
- Use validation annotations appropriately

### 2. Controller Design

- Use `Api` annotation to group controllers
- Add `ApiOperation` description for each method
- Return specific types instead of `array` when possible
- Use `ApiResponse` to define response formats properly

### 3. Security

- Disable documentation service in production
- Use `ApiSecurity` to control API authentication
- Use `hidden: true` to hide sensitive endpoints

### 4. Performance Optimization

- Use documentation in development, disable in production
- Use caching appropriately
- Avoid deeply nested structures

## 📚 FAQ

### Q: Documentation not generated?

A: Check the following:
1. Is `enable` set to `true` in config file
2. Is `#[Api]` annotation added to controller
3. Is route annotation added to method (e.g., `#[GetMapping]`)
4. Check logs for errors

### Q: How to define array types?

A: Use PHPDoc comments or `ArrayType` annotation:

```php
/**
 * @var User[]
 */
public array $users;

// Or
#[ArrayType(User::class)]
public array $users;
```

### Q: How to hide certain endpoints?

A: Use `hidden` parameter:

```php
#[Api(hidden: true)]  // Hide entire controller

#[ApiOperation(summary: 'Test', hidden: true)]  // Hide single endpoint
```

### Q: How to customize response format?

A: Use `ApiResponse` annotation or configure global response class:

```php
#[ApiResponse(UserResponse::class, 200, 'Success')]
public function getUser(): UserResponse
{
    return new UserResponse();
}
```

### Q: What validation rules are supported?

A: All Hyperf Validation rules are supported. See [Hyperf Validation Documentation](https://hyperf.wiki/3.1/#/en/validation).

### Q: Does `AutoController` annotation work?

A: Yes, but it only collects `POST` methods. It's recommended to use standard route annotations for better documentation generation.

## 📖 Example Project

> For complete examples, see the [example directory](https://github.com/tw2066/api-docs/tree/master/example)

## 🔗 Related Links

- [Hyperf Official Documentation](https://hyperf.wiki)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Swagger UI](https://swagger.io/tools/swagger-ui/)
- [Knife4j](https://doc.xiaominfo.com/)
- [Example Project](https://github.com/tw2066/api-docs/tree/master/example)

## 📝 Changelog

See [CHANGELOG](CHANGELOG.md) for detailed version updates.

## 🤝 Contributing

Issues and Pull Requests are welcome!

1. Fork this repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📜 License

[MIT License](LICENSE)

## ❤️ Acknowledgments

- [Hyperf](https://github.com/hyperf/hyperf) - Excellent coroutine PHP framework
- [Swagger PHP](https://github.com/zircote/swagger-php) - PHP Swagger generator
- [Knife4j](https://gitee.com/xiaoym/knife4j) - Excellent API documentation tool

---

If this project helps you, please give it a ⭐ Star!
