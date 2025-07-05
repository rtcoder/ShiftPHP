# ShiftPHP Framework - Refaktoryzacja

## 🎯 **Przegląd zmian**

Ten dokument opisuje kompleksową refaktoryzację frameworka ShiftPHP, która wprowadza nowoczesne wzorce projektowe, poprawia architekturę i zwiększa możliwości testowania.

## 📋 **Lista zmian**

### 1. **Refaktoryzacja klasy Request**
- **Przed**: Statyczne metody i właściwości
- **Po**: Instancje klas z dependency injection
- **Korzyści**: 
  - Łatwiejsze testowanie
  - Lepsze zarządzanie stanem
  - Dodatkowe metody (isPost, isGet, getHeader, etc.)

### 2. **Refaktoryzacja klasy App**
- **Przed**: Statyczne metody, globalny stan
- **Po**: Instancje z dependency injection
- **Korzyści**:
  - Kontrola cyklu życia aplikacji
  - Możliwość testowania
  - Lepsze zarządzanie błędami

### 3. **Ulepszenie systemu widoków**
- **Przed**: Monolityczna klasa View
- **Po**: Podzielona na mniejsze, wyspecjalizowane metody
- **Korzyści**:
  - Lepsze separation of concerns
  - Łatwiejsze utrzymanie
  - Czytelniejszy kod

### 4. **Poprawa klasy Storage**
- **Przed**: Podstawowa walidacja, publiczne właściwości
- **Po**: Zaawansowana walidacja, enkapsulacja
- **Korzyści**:
  - Bezpieczniejsze operacje na plikach
  - Lepsze zarządzanie błędami
  - Dodatkowe funkcje (clearViews)

### 5. **Nowy system obsługi błędów**
- **Nowe**: Klasa ErrorHandler z centralnym zarządzaniem
- **Korzyści**:
  - Spójna obsługa błędów
  - Wsparcie dla CLI i web
  - Możliwość customizacji

### 6. **Service Container**
- **Nowe**: System dependency injection
- **Korzyści**:
  - Loose coupling
  - Łatwiejsze testowanie
  - Zarządzanie zależnościami

## 🔧 **Szczegóły techniczne**

### Dependency Injection

```php
// Przed
$app = new App();
App::start();

// Po
$request = new Request();
$app = new App($request);
$app->start();
```

### Service Container

```php
// Rejestracja serwisów
$app->getContainer()->singleton('database', function() {
    return new Database();
});

// Resolwowanie serwisów
$database = $app->resolve('database');
```

### Obsługa błędów

```php
// Automatyczna rejestracja w bootstrap.php
\Engine\Error\ErrorHandler::register();

// Custom handler
\Engine\Error\ErrorHandler::setCustomHandler(function($exception) {
    // Custom error handling
});
```

## 📁 **Struktura plików**

```
Engine/
├── App.php                    # Główna klasa aplikacji (refaktoryzowana)
├── Request.php                # Obsługa żądań (refaktoryzowana)
├── Controller.php             # Bazowa klasa kontrolera (ulepszona)
├── View.php                   # System widoków (refaktoryzowany)
├── ServiceContainer.php       # NOWY: Container dla DI
├── ServiceInterface.php       # NOWY: Interfejs dla serwisów
├── Error/
│   ├── ErrorHandler.php       # NOWY: Centralny system błędów
│   └── ShiftError.php         # Ulepszona obsługa błędów
└── Utils/
    └── Storage.php            # Ulepszona klasa Storage
```

## 🚀 **Korzyści z refaktoryzacji**

### 1. **Testowalność**
- Wszystkie klasy można teraz łatwo testować
- Dependency injection umożliwia mockowanie
- Brak globalnego stanu

### 2. **Maintainability**
- Czytelniejszy kod
- Lepsze separation of concerns
- Mniejsze klasy z jedną odpowiedzialnością

### 3. **Extensibility**
- Service container umożliwia łatwe dodawanie nowych serwisów
- Modularna architektura
- Interfejsy dla rozszerzeń

### 4. **Error Handling**
- Centralny system obsługi błędów
- Lepsze logowanie
- Wsparcie dla różnych środowisk

## 🔄 **Migracja**

### Dla istniejących kontrolerów:

```php
// Przed
class MyController extends \Engine\Controller
{
    public function index()
    {
        $this->render('view', ['data' => 'value']);
    }
}

// Po (bez zmian w API!)
class MyController extends \Engine\Controller
{
    public function index()
    {
        $this->render('view', ['data' => 'value']);
        // Dodatkowo dostępne:
        $this->json(['status' => 'success']);
        $this->redirect('/home');
    }
}
```

### Dla punktu wejścia:

```php
// Przed
require_once 'bootstrap.php';
Engine\App::start();

// Po
require_once 'bootstrap.php';
$request = new Engine\Request();
$app = new Engine\App($request);
$app->start();
```

## 🧪 **Testowanie**

Framework jest teraz w pełni testowalny:

```php
// Przykład testu
$request = $this->createMock(Engine\Request::class);
$request->method('getController')->willReturn('test');
$request->method('getAction')->willReturn('index');

$app = new Engine\App($request);
// Test aplikacji...
```

## 📈 **Wydajność**

- Brak statycznych właściwości = lepsze zarządzanie pamięcią
- Service container z singletonami = optymalizacja zasobów
- Lepsze zarządzanie błędami = szybsze debugowanie

## 🔮 **Przyszłe rozszerzenia**

Refaktoryzacja przygotowuje framework na:

1. **Middleware system**
2. **Event system**
3. **Database abstraction layer**
4. **Caching system**
5. **CLI commands**
6. **API routing**

## 📝 **Podsumowanie**

Refaktoryzacja ShiftPHP wprowadza:

- ✅ **Dependency Injection**
- ✅ **Service Container**
- ✅ **Lepsze zarządzanie błędami**
- ✅ **Testowalność**
- ✅ **Maintainability**
- ✅ **Extensibility**

Framework jest teraz gotowy na przyszłe rozszerzenia i łatwiejszy w utrzymaniu. 