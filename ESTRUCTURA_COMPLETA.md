# Estructura Completa del Proyecto Oreka

## 📁 Directorio Raíz
```
oreka/
├── 📄 .env
├── 📄 .gitignore
├── 📄 .htaccess
├── 📄 DUDAS.md
├── 📄 README.md
├── 📄 README 1.md
├── 📄 structure.txt
├── 📄 TAREAS.md
└── 📁 [CARPETAS PRINCIPALES]
```

## 📁 bbdd/ - Base de Datos
```
bbdd/
├── 📄 limpiar de datos base de datos.sql
├── 📄 oreka.sql
└── 📄 rellenar de datos la base de datos.sql
```

## 📁 logs/ - Registros del Sistema
```
logs/
└── 📄 .gitkeep
```

## 📁 private/ - Código Backend (PHP)

### 📁 private/app/ - Núcleo de la Aplicación
```
private/app/
├── 📄 Auth.php           # Sistema de autenticación
├── 📄 Controller.php     # Controlador base
├── 📄 DB.php            # Conexión y gestión de base de datos
├── 📄 functions.php     # Funciones auxiliares
└── 📄 Router.php        # Sistema de rutas
```

### 📁 private/config/ - Configuración
```
private/config/
├── 📄 config.php        # Configuración general
└── 📄 db_connect.php    # Configuración de base de datos
```

### 📁 private/controllers/ - Controladores Principales
```
private/controllers/
├── 📄 DashboardController.php
└── 📄 HomeController.php
```

### 📁 private/includes/ - Elementos Reutilizables
```
private/includes/
├── 📄 close.php         # Cierre de sesión/conexiones
├── 📄 footer.php        # Pie de página
├── 📄 header.php        # Cabecera HTML
├── 📄 menu.php          # Menú de navegación
└── 📄 topbar.php        # Barra superior
```

### 📁 private/lang/ - Internacionalización
```
private/lang/
├── 📄 es.php            # Idioma español
└── 📄 eu.php            # Idioma euskera
```

### 📁 private/models/ - Modelos de Datos
```
private/models/
├── 📄 Category.php      # Modelo de categorías
└── 📄 User.php          # Modelo de usuarios
```

### 📁 private/modules/ - Módulos de la Aplicación

#### 📁 Módulo Banner
```
private/modules/banner/
├── 📁 controllers/
│   └── 📄 BannerController.php
├── 📁 models/
│   └── 📄 Banner.php
└── 📁 views/
    ├── 📄 form.php
    └── 📄 index.php
```

#### 📁 Módulo Categories
```
private/modules/categories/
├── 📁 controllers/
│   └── 📄 CategoryController.php
├── 📁 models/
│   └── 📄 Category.php
└── 📁 views/
    ├── 📄 form.php
    └── 📄 index.php
```

#### 📁 Módulo Community
```
private/modules/community/
├── 📁 controllers/
│   └── 📄 CommunityController.php
├── 📁 models/
│   └── 📄 Community.php
└── 📁 views/
    └── 📄 community.php
```

#### 📁 Módulo Forum
```
private/modules/forum/
├── 📁 controllers/
│   └── 📄 ForumController.php
├── 📁 models/
│   └── 📄 Forum.php
└── 📁 views/
    └── 📄 forum.php
```

#### 📁 Módulo Intra (Administración)
```
private/modules/intra/
├── 📁 controllers/
│   ├── 📄 AdminController.php
│   ├── 📄 PanelController.php
│   └── 📄 UserController.php
├── 📁 models/
│   └── 📄 User.php
└── 📁 views/
    ├── 📁 admin/
    │   ├── 📄 admin.php
    │   ├── 📄 community.php
    │   ├── 📄 forum.php
    │   ├── 📄 index.php
    │   ├── 📄 learn.php
    │   ├── 📄 store.php
    │   └── 📄 users.php
    └── 📁 users/
        ├── 📄 form.php
        ├── 📄 index.php
        └── 📄 profile.php
```

#### 📁 Módulo Learn
```
private/modules/learn/
├── 📁 controllers/
│   └── 📄 LearnController.php
├── 📁 models/
│   └── 📄 Learn.php
└── 📁 views/
    └── 📄 learn.php
```

#### 📁 Módulo Store (Tienda)
```
private/modules/store/
├── 📄 slug-resolver.php
├── 📁 controllers/
│   ├── 📄 AdminProductController.php
│   ├── 📄 OrderController.php
│   └── 📄 ProductController.php
├── 📁 models/
│   ├── 📄 Order.php
│   └── 📄 Product.php
└── 📁 views/
    ├── 📄 cart.php
    ├── 📄 detail.php
    ├── 📄 index.php
    └── 📁 admin/
        ├── 📄 form.php
        ├── 📄 index.php
        └── 📄 orders.php
```

### 📁 private/views/ - Vistas Principales
```
private/views/
├── 📄 dashboard.php     # Panel de control
└── 📄 home.php          # Página de inicio
```

## 📁 public/ - Contenido Público (Frontend)

### 📁 Archivos Principales
```
public/
├── 📄 .htaccess         # Configuración del servidor
├── 📄 index.php         # Punto de entrada de la aplicación
└── 📄 web.config        # Configuración IIS
```

### 📁 public/assets/ - Recursos Estáticos

#### 📁 CSS
```
public/assets/css/
├── 📄 main.css          # CSS compilado principal
└── 📄 main.css.map      # Mapa de fuentes CSS
```

#### 📁 Imágenes
```
public/assets/images/
├── 📄 bienestar.png
├── 📄 logo_kutxa.jpg
├── 📄 logo_oreka.png
├── 📄 nutricion.png
├── 📄 placeholder.txt
└── 📄 productividad.png
```

#### 📁 JavaScript
```
public/assets/js/
├── 📄 learn-slider.js
├── 📄 main.js           # JavaScript principal
└── 📄 recommendation-likes.js
```

#### 📁 SCSS (Sass)
```
public/assets/scss/
├── 📄 _index.scss       # Índice de imports
├── 📄 main.scss         # Archivo principal SCSS
├── 📁 abstracts/        # Variables, funciones, mixins
│   ├── 📄 _functions.scss
│   ├── 📄 _index.scss
│   ├── 📄 _media-queries.scss
│   ├── 📄 _mixins.scss
│   ├── 📄 _typography.scss
│   └── 📄 _variables.scss
├── 📁 components/       # Componentes reutilizables
│   ├── 📄 _animations.scss
│   ├── 📄 _buttons.scss
│   └── 📄 _index.scss
├── 📁 includes/         # Estilos de elementos incluidos
│   ├── 📄 _footer.scss
│   ├── 📄 _index.scss
│   └── 📄 _menu.scss
├── 📁 layout/           # Estructura de layout
│   ├── 📄 _base.scss
│   ├── 📄 _general.scss
│   ├── 📄 _grid.scss
│   └── 📄 _index.scss
├── 📁 pages/            # Estilos específicos de páginas
│   ├── 📄 _admin.scss
│   ├── 📄 _index.scss
│   ├── 📄 _store.scss
│   └── 📄 _user.scss
└── 📁 sections/         # Estilos de secciones
    ├── 📄 _community.scss
    ├── 📄 _forum.scss
    ├── 📄 _hero.scss
    ├── 📄 _index.scss
    └── 📄 _learn.scss
```

### 📁 public/mirar/ - Archivos de Desarrollo/Prueba
```
public/mirar/
├── 📄 _admin.scss       # Estilos admin (prueba)
├── 📄 _welcome.scss     # Estilos welcome (prueba)
├── 📄 admin_nav.php     # Navegación admin (prueba)
├── 📄 dashboard.php     # Dashboard (prueba)
└── 📄 welcome.php       # Welcome (prueba)
```

## 📁 sessions/ - Sesiones de Usuario
```
sessions/
└── 📄 .gitkeep
```

## 📁 uploads/ - Archivos Subidos
```
uploads/
├── 📄 .gitkeep
└── 📄 image.png         # Imagen de ejemplo
```

---



### **Características del Proyecto:**
- ✅ Arquitectura MVC (Modelo-Vista-Controlador)
- ✅ Sistema modular
- ✅ Multiidioma (Español/Euskera)
- ✅ Panel de administración
- ✅ Sistema de autenticación
- ✅ Base de datos MySQL
- ✅ Frontend responsive con SCSS
- ✅ Sistema de rutas
- ✅ Gestión de uploads
- ✅ Sistema de sesiones
