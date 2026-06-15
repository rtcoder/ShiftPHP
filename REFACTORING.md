# ShiftPHP - API-only refactoring

Ten dokument opisuje kierunek refaktoryzacji ShiftPHP po decyzji, że framework ma obslugiwac tylko API. Warstwa widokow, template engine i storage dla skompilowanych widokow nie sa juz czescia docelowego runtime.

## Cel

ShiftPHP powinien byc malym rdzeniem HTTP/API:

- przyjmuje request,
- mapuje request na endpoint,
- uruchamia kontroler lub handler,
- zwraca odpowiedz JSON,
- obsluguje bledy w spojnym formacie JSON,
- pozwala podpinac zaleznosci przez container.

## Zmiany wykonane w pierwszym kroku

### Controller

- Bazowy kontroler dostaje `Request` przez konstruktor.
- Bazowy kontroler nie tworzy juz wlasnego `Request`.
- Bazowy kontroler nie tworzy juz `View`.
- Publiczny kierunek API to `json()`, `error()` i `noContent()`.

```php
class HelloController extends \Engine\Controller
{
    public function index(): void
    {
        $this->json([
            'message' => 'Hello from ShiftPHP!',
        ]);
    }
}
```

### App

- `App` przekazuje ten sam `Request` do kontrolera.
- Domyslne serwisy nie rejestruja juz `view`.
- `App` nadal odpowiada za znalezienie kontrolera i wywolanie akcji, ale to powinno zostac wydzielone do routera.

### Error handling

- `ShiftError` nie renderuje HTML w konstruktorze.
- `ErrorHandler` zwraca JSON dla requestow webowych.
- HTML highlighter moze zostac usuniety albo przeniesiony do osobnego narzedzia developerskiego, ale nie powinien byc czescia API runtime.

### Composer/autoload

- Namespace `View\\` zostal usuniety z PSR-4 autoload.

## Do usuniecia z runtime

Te elementy sa zwiazane z dawnym MVC i nie powinny zostac w docelowym API-only rdzeniu:

- `Engine/View.php`
- `Engine/View/`
- `application/view/`
- `public/css/hello/`
- `public/js/hello/`
- `Engine/Utils/Storage.php`, jezeli sluzy tylko do widokow
- helpery zwiazane z template engine, np. `__()` i `is_url()`, jezeli nie sa uzywane poza widokami

Usuwanie najlepiej zrobic osobnym commitem po upewnieniu sie, ze nie ma juz zaleznosci w kontrolerach i dokumentacji.

## Nastepne kroki

### 1. Response object

Aktualnie kontroler robi `echo` i ustawia naglowki bezposrednio. Lepszy docelowy model:

```php
return JsonResponse::ok(['status' => 'success']);
return JsonResponse::created($resource);
return JsonResponse::error('Not found', 404);
```

Korzyści:

- latwiejsze testy,
- brak efektow ubocznych w kontrolerze,
- jedno miejsce dla status code, headers i serializacji JSON.

### 2. Router

`Request` nie powinien znac pojec `controller` i `action`. Docelowo:

- `Request` opisuje HTTP: method, path, headers, query, body,
- `Router` mapuje method + path na handler,
- `App` tylko spina request, router, container i response emitter.

Minimalny etap posredni:

```php
$router->get('/hello', [HelloController::class, 'index']);
$router->post('/users', [UserController::class, 'create']);
```

### 3. Request body

Dla API potrzebne sa metody:

- `getJson(): array`
- `input(string $key, mixed $default = null): mixed`
- `query(string $key, mixed $default = null): mixed`
- `header(string $name, ?string $default = null): ?string`
- `method(): string`
- `path(): string`

Wazne: JSON body powinien byc walidowany, a bledny JSON powinien dawac `400 Bad Request`.

### 4. Error format

Proponowany format:

```json
{
  "error": {
    "message": "Not found",
    "status": 404
  }
}
```

Dla `display_errors=On` mozna dodac `file`, `line` i `trace`, ale tylko w srodowisku developerskim.

### 5. Dependency injection

Container powinien byc uzywany do budowy kontrolerow. Docelowo:

- kontroler moze dostac serwisy w konstruktorze,
- container potrafi zbudowac klase po typach,
- request jest rejestrowany per request, nie jako globalny singleton na zawsze.

### 6. CLI

CLI jest osobnym tematem, ale warto poprawic:

- `serve` nie powinno sklejac komendy shellowej z argumentu uzytkownika,
- komendy powinny uzywac jednego namespace,
- help/description powinny byc wymagane i faktycznie wypelnione.

## Priorytet prac

1. Usunac pozostale zaleznosci od widokow z runtime.
2. Dodac obiekt odpowiedzi i testy dla JSON.
3. Wydzielic router z `Request`.
4. Ustandaryzowac bledy HTTP i JSON body.
5. Dopiero potem rozwijac middleware, eventy, cache i database layer.

## Kryterium gotowosci

Pierwszy etap API-only mozna uznac za zamkniety, gdy:

- zaden kontroler nie wywoluje `render()`,
- `composer.json` nie laduje namespace `View\\`,
- request do istniejacego endpointu zwraca JSON,
- request do nieistniejacego endpointu zwraca JSON error,
- testy lub smoke test pokrywaja przynajmniej jeden happy path i jeden error path.
