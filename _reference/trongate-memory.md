# Trongate Coding Agent Guide

This file is for coding agents working in this Trongate project. Treat it as the local operating manual before editing code.

## Framework Philosophy

Trongate is intentionally simple, native PHP. The official documentation frames the project around speed, ownership, and low abstraction:

- Prefer plain PHP over framework ceremony.
- Keep application code inside `modules/`.
- Avoid unnecessary dependencies, package managers, hidden autoloading, and broad abstractions.
- Copyable modules are a feature: a module should be self-contained and portable.
- Use direct, readable code. Do not introduce a service container, ORM, template engine, build step, or Composer dependency unless the user explicitly asks and the tradeoff is unavoidable.

Primary documentation reviewed:

- `https://trongate.io/documentation/trongate_php_framework/introduction/forward-by-claude`
- `https://trongate.io/documentation/trongate_php_framework/basic-concepts/trongates-directory-structure`
- `https://trongate.io/documentation/trongate_php_framework/basic-concepts/modular-mvc`
- `https://trongate.io/documentation/trongate_php_framework/module-fundamentals/view-files`
- `https://trongate.io/documentation/display/php_framework/understanding-routing/routing-an-overview`
- `https://trongate.io/documentation/display/php_framework/understanding-routing/custom-routing-patterns`
- `https://trongate.io/docs/database-operations/introducing-the-model-class.html`
- `https://trongate.io/documentation/trongate_php_framework/tips-and-best-practices/pre-launch-checklist`
- `https://trongate.io/documentation/trongate_mx/introduction/introduction-to-trongate-mx`
- `https://trongate.io/documentation/trongate_mx/core-http-operations/http-methods-in-trongate-mx`
- `https://trongate.io/docs/trongate-mx/trongate-mx-attribute-reference.html`

## Project Structure

Current root folders:

```text
config/
engine/
modules/
public/
```

Important local files:

- `public/index.php`: front controller.
- `engine/ignition.php`: framework bootstrap.
- `engine/Core.php`: request dispatcher, routing, module asset serving.
- `engine/Trongate.php`: base controller class.
- `engine/Model.php`: model gateway and database/module loader.
- `engine/tg_helpers/*.php`: global helper functions.
- `config/config.php`: `BASE_URL`, `ENV`, defaults, asset trigger, 404 handler.
- `config/database.php`: database groups.
- `config/custom_routing.php`: custom route map.
- `modules/templates/`: public/admin/error templates.
- `modules/welcome/`: default public homepage module.
- `modules/setup/`: setup flow.
- `modules/login/`, `modules/trongate_administrators/`, `modules/trongate_tokens/`, `modules/trongate_security/`: auth-related modules.

Do not edit `engine/` unless the user specifically asks for framework-level changes. Most work belongs in `modules/`, `config/`, or `public/`.

## Naming And Style

- Use `snake_case` for module folders, methods, variables, database columns, and helper-style functions.
- Module controller class names are `Upperfirst` versions of the folder name.
  - Folder: `modules/products/`
  - Controller file: `modules/products/Products.php`
  - Controller class: `Products`
- Module model class names append `_model`.
  - File: `modules/products/Products_model.php`
  - Class: `Products_model`
- Keep controller methods public only when they are intended to be URL-callable or used by other modules.
- Prefix private/internal controller methods with `_` if they should not be invoked by URL. `Core.php` blocks URL access to methods beginning with `_`.
- Use strict, modern PHP signatures where the surrounding code does.
- Use curly braces in PHP control structures. Avoid PHP alternative template syntax such as `endforeach`.
- Escape output in views with `out($value)` for user-generated or database-derived strings.

## Module Anatomy

A normal module looks like this:

```text
modules/products/
  Products.php
  Products_model.php        optional
  views/
    manage.php
    create.php
  css/                      optional
  js/                       optional
  images/                   optional
  sql/ or *.sql             optional import assets
```

Controller skeleton:

```php
<?php
class Products extends Trongate {

    public function index(): void {
        $data = [
            'view_module' => 'products',
            'view_file' => 'manage'
        ];

        $this->templates->public($data);
    }

    public function create(): void {
        $data = [];
        $this->view('create', $data);
    }
}
```

If defining a constructor in a controller, call the parent constructor:

```php
public function __construct(?string $module_name = null) {
    parent::__construct($module_name);
}
```

## Routing

Trongate uses URL segments:

```text
BASE_URL/module/method/extra/segments
```

Examples:

- `/products` calls `modules/products/Products.php`, method `index()`.
- `/products/create` calls `Products::create()`.
- Extra URL values are not automatically injected into method parameters. Read them with `segment()`.

Useful URL helpers:

```php
segment(1);              // first URL segment after BASE_URL
segment(3, 'int');       // cast segment to int
current_url();
previous_url();
redirect('products');
anchor('products/create', 'Create Product', ['class' => 'button']);
```

Custom routes live in `config/custom_routing.php`:

```php
<?php
$routes = [
    'shop' => 'products/index',
    'product/(:num)' => 'products/show/$1',
    'docs/(:all)' => 'documentation/show/$1'
];
define('CUSTOM_ROUTES', $routes);
```

Wildcard notes:

- `(:num)` matches numeric segments.
- `(:any)` matches one non-slash segment.
- `(:all)` captures remaining segments and must be last.
- Put specific routes before broad routes.
- Custom routing maps what content is served; it does not redirect or change the browser address bar.

Current local custom route:

```php
'tg-admin' => 'login/login/tg-admin'
```

## Controllers And Module Loading

All controllers extend `Trongate`. The base class lazy-loads framework services and modules through `__get()`.

Common controller properties:

```php
$this->model;        // engine Model instance scoped to current module
$this->validation;   // validation module
$this->templates;    // templates module, lazy-loaded
$this->some_module;  // module loaded on demand
```

To explicitly load and use another module:

```php
$this->module('email_sender');
$this->email_sender->send($to, $subject, $message);
```

Child module loading uses `parent-child` naming:

```php
$this->module('shop-products');
$this->products->index();
```

Child module path:

```text
modules/shop/products/Products.php
```

## Models And Database

`engine/Model.php` delegates module-specific model methods and exposes database connections.

Model skeleton:

```php
<?php
class Products_model extends Model {

    public function get_active_products(): array {
        return $this->db->query_bind(
            'SELECT * FROM products WHERE is_active = :is_active ORDER BY title',
            ['is_active' => 1],
            'object'
        );
    }
}
```

Call module model methods from the controller through `$this->model`:

```php
$products = $this->model->get_active_products();
```

Database access:

```php
$this->model->db;          // default database group
$this->model->analytics;   // alternative database group named "analytics" in config/database.php
```

Available local `Db` methods include:

```php
insert(array $data, string $table): int
update(int $id, array $data, string $table): bool
delete(int $id, string $table): bool
query(string $sql, ?string $return_type = null): mixed
query_bind(string $sql, array $data, ?string $return_type = null): mixed
count(string $table): int
insert_batch(array $records, string $table): int
get(string $order_by, string $table, string $order_direction = 'ASC', ?string $return_type = 'object'): array
get_where(int $id, string $table): object|bool
get_one_where(string $column, mixed $value, string $table): object|bool
get_where_in(string $table, string $column, array $values, array $options = []): array
resequence_ids(string $table_name): bool
table_exists(string $table): bool
get_tables(): array
describe_table(string $table, bool $names_only = false): array|bool
attempt_truncate(string $table, bool $validate_table = true): bool
```

Database rules:

- Prefer `query_bind()` for custom SQL with user input.
- Do not concatenate untrusted values into SQL.
- Use the local `Db` methods before introducing another database abstraction.
- Check `config/database.php` before assuming a database name or alternate group exists.

## Views And Templates

Views are plain PHP files under a module's `views/` directory.

Render directly:

```php
$this->view('create', $data);
```

Render as a string:

```php
$html = $this->view('summary', $data, true);
```

Render through a template:

```php
$data = [
    'view_module' => 'products',
    'view_file' => 'manage',
    'headline' => 'Products'
];

$this->templates->public($data);
$this->templates->admin($data);
```

View rules:

- Views may contain display conditionals and loops.
- Do not put queries, authentication decisions, redirects, or business workflows in views.
- Escape display values:

```php
<h1><?= out($headline) ?></h1>
```

## Forms, Validation, And CSRF

Form helpers are in `engine/tg_helpers/form_helper.php`.

Common helpers:

```php
form_open('products/submit');
form_open_upload('products/upload');
form_input('title', $title ?? '', ['id' => 'title']);
form_email('email', $email ?? '');
form_password('password');
form_number('price', $price ?? null);
form_hidden('id', $id ?? null);
form_textarea('description', $description ?? '');
form_checkbox('is_active', 1, $is_active ?? false);
form_radio('status', 'draft', ($status ?? '') === 'draft');
form_label('Title', ['for' => 'title']);
form_close(); // includes CSRF token and closing tag
```

Validation:

```php
$this->validation->set_rules('title', 'Title', 'required|min_length[3]|max_length[255]');

if ($this->validation->run() === false) {
    $this->create();
    return;
}
```

Array-style validation is supported:

```php
$is_valid = $this->validation->run([
    'title' => [
        'label' => 'Title',
        'required' => true,
        'min_length' => 3,
        'max_length' => 255
    ]
]);
```

Validation callbacks:

```php
$this->validation->set_rules('slug', 'Slug', 'required|callback_slug_available');

public function slug_available(string $slug): bool|string {
    return $this->model->slug_exists($slug) ? 'The slug is already in use.' : true;
}
```

Important: `Validation::run()` checks CSRF first. Use `form_close()` in forms so the token is included.

## Input, Output, And String Helpers

Use helpers instead of raw superglobals where available.

String helpers include:

```php
out($input, 'html');              // safe HTML output
out($input, 'attribute');         // safe attribute output
filter_str($str, $allowed_tags);
filter_name($name);
url_title($value);
sanitize_filename($filename);
make_rand_str(32);
truncate_str($value, 100);
truncate_words($value, 20);
nice_price(19.99, '$');
```

Legacy note:

- `filter_string()` is deprecated in this codebase and says it will be removed on June 3, 2026. Use `filter_str()`.

## Security Rules

- Never launch with `ENV` set to `dev`. Local `config/config.php` currently has `define('ENV', 'dev');` for development.
- Change default admin credentials before production.
- Keep `public/` as the only web-exposed directory.
- Use `form_close()` for CSRF token output.
- Use `query_bind()` for custom queries with variables.
- Use `out()` when rendering user-generated or database values.
- Prevent direct URL access for sensitive public methods by using method signatures that require parameters, private/protected methods, or underscore-prefixed methods when appropriate.
- For modules that must never be hit directly, use the local pattern `block_url('module_name')` as seen in framework service modules.
- Do not expose files from module directories directly unless going through Trongate's module asset mechanism.

## Assets

Public assets live in `public/`.

Module assets can live beside their module:

```text
modules/products/css/products.css
modules/products/js/products.js
modules/products/images/example.png
```

The module asset trigger is configured in `config/config.php`:

```php
define('MODULE_ASSETS_TRIGGER', '_module');
```

Before changing asset URL conventions, inspect `engine/Core.php` and existing views/templates.

## Trongate MX

Trongate MX is the bundled JavaScript layer for server-driven interactivity. It lets views use HTML attributes such as `mx-get` and `mx-post` to perform AJAX requests, swap returned HTML into the page, submit forms, show modals, run loading states, animate success/error responses, poll endpoints, push browser URLs, and attach security headers/tokens.

Local files:

```text
public/js/trongate-mx.js
public/js/trongate-mx.min.js
public/css/trongate.css
```

Template loading:

- `modules/templates/views/admin.php` already includes `js/trongate-mx.min.js` and `css/trongate.css`.
- `modules/templates/views/public.php` includes `css/trongate.css` but does not directly include `trongate-mx.min.js`; add it through template includes or the template when a public page needs MX.
- Both templates include `<base href="<?= BASE_URL ?>">`, which MX uses to normalize relative request URLs.
- The official quick start recommends including `trongate.css` in the head and `trongate-mx.js` either in the head or before `</body>`.

Core HTTP attributes:

```html
<button mx-get="products/list" mx-target="#products-list">Load</button>
<form mx-post="products/submit" mx-target="#form-result">...</form>
<button mx-put="products/update/12">Update</button>
<button mx-patch="products/patch/12">Patch</button>
<button mx-delete="products/delete/12">Delete</button>
```

Supported request attributes in the local JS:

```text
mx-get
mx-post
mx-put
mx-delete
mx-patch
mx-remove
```

Common companion attributes:

```text
mx-target              target selector; supports this, body, closest <selector>, find <selector>, none
mx-swap                innerHTML, outerHTML, textContent, beforebegin, afterbegin, beforeend, afterend, delete, value, none
mx-select              select part of the server response before swapping
mx-select-oob          perform out-of-band swaps into other targets
mx-trigger             click, submit, change, input, load, every 5s, load delay:5s, etc.
mx-on-trigger          run a global JS function before the request
mx-after-swap          run a global JS function after a successful swap
mx-on-success          initialize/update another element after success
mx-on-error            initialize/update another element after error
mx-on-timeout          run a function on request timeout
mx-timeout             request timeout in ms, or none/0 to disable
mx-indicator           selector for loading indicator
mx-target-loading      cloak or selector for temporary loading content
mx-animate-success     draw success animation on 2xx response
mx-animate-error       draw error animation on non-2xx response
mx-close-on-success    close modal after 2xx response
mx-close-on-error      close modal after non-2xx response
mx-build-modal         dynamically create a modal and load response into it
mx-push-url            push request URL into browser history when true
mx-redirect-on-success redirect to plain-text response URL on 2xx response when true
mx-redirect-on-error   redirect to plain-text response URL on non-2xx response when true
mx-token               attach Trongate token as the trongateToken header
mx-headers             JSON object of custom request headers
mx-vals                JSON object of extra form/request values
mx-dom-vals            JSON object for attaching DOM values to request data
mx-throttle            minimum milliseconds between requests
```

Basic endpoint pattern:

```php
public function list(): void {
    $data['products'] = $this->model->get_active_products();
    $this->view('list_partial', $data);
}
```

Basic MX view pattern:

```php
<button
    mx-get="products/list"
    mx-target="#products-list"
    mx-swap="innerHTML">
    Load Products
</button>

<div id="products-list"></div>
```

Trongate MX works well when endpoints return HTML partials. Do not default to JSON unless the consuming UI specifically needs JSON. For content swaps, return exactly the markup that should be inserted.

Form submission pattern:

```php
<?= form_open('products/submit', [
    'mx-post' => 'products/submit',
    'mx-target' => '#product-form-result',
    'mx-swap' => 'innerHTML',
    'class' => 'highlight-errors'
]) ?>
    <?= form_input('title', $title ?? '', ['required' => true]) ?>
    <button type="submit">Save</button>
<?= form_close() ?>

<div id="product-form-result"></div>
```

Controller response pattern for MX forms:

```php
public function submit(): void {
    $this->validation->set_rules('title', 'Title', 'required|min_length[3]');

    if ($this->validation->run() === false) {
        echo validation_errors(422);
        return;
    }

    $this->model->db->insert([
        'title' => post('title', true)
    ], 'products');

    echo '<div class="alert success">Saved.</div>';
}
```

Notes for MX validation:

- `form_close()` still matters because it outputs the CSRF token.
- The local MX script can highlight validation errors on forms with `class="highlight-errors"` when a 4xx JSON validation response is returned.
- Keep validation server-side even when using browser `required`, `minlength`, or other HTML validation attributes.

Modal pattern:

```html
<button
    mx-get="products/create"
    mx-build-modal='{"id":"product-modal","modalHeading":"Create Product","width":"640px","showCloseButton":true}'
    mx-target="#product-modal .modal-body">
    Create
</button>
```

Polling pattern:

```html
<div
    mx-get="dashboard/stats"
    mx-target="this"
    mx-trigger="every 10s">
</div>
```

Dynamic URL pattern:

```html
<select mx-get="products/by_category/${this.value}" mx-target="#products-list" mx-trigger="change">
    <option value="1">Category 1</option>
</select>
```

Security and endpoint rules for MX:

- MX sends `Trongate-MX-Request: true`; endpoints can inspect this header when they should only serve partial/AJAX content.
- Use `mx-token` when the endpoint expects a Trongate token.
- Use `mx-headers` only with valid JSON, for example `mx-headers='{"X-Feature":"products"}'`.
- Treat MX endpoints like normal controller endpoints: validate authorization, validate input, bind SQL values, and escape rendered output.
- For destructive actions, prefer POST/DELETE with CSRF/token checks over GET.
- Avoid putting sensitive IDs or secrets in `mx-vals` or URLs.

Agent rules for Trongate MX:

- Prefer MX attributes for lightweight interactivity instead of adding a JavaScript framework.
- Return small HTML partials from controller methods for swaps.
- Keep partial views in the module's `views/` folder and name them clearly, such as `_row.php`, `list_partial.php`, or `form_partial.php`.
- Confirm the template includes `trongate-mx.min.js` before adding MX attributes to a page.
- Use `mx-indicator`, disabled form states, and success/error responses for user-facing submissions.
- Keep custom JavaScript functions small and global only when MX needs to call them through `mx-on-trigger`, `mx-after-swap`, `mx-on-success`, `mx-on-error`, or `mx-on-timeout`.

## Included Framework Modules

Useful built-in/local modules:

- `db`: PDO-backed database layer.
- `form`: form element generation.
- `validation`: validation and CSRF.
- `templates`: public/admin/error templates.
- `flashdata`: session flash messages.
- `pagination`: pagination helpers/views.
- `image`: image manipulation.
- `file`: file handling.
- `language`: language support.
- `trongate_email`: email.
- `trongate_tokens`: token auth.
- `trongate_security`: authorization checks.
- `login`: login flows.
- `trongate_administrators`: administrator management.
- `trongate_control`: setup/import tooling.

Prefer these modules over new dependencies.

## Auth And Tokens

Token module methods available locally:

```php
$this->trongate_tokens->attempt_get_valid_token($user_levels);
$this->trongate_tokens->generate_token($data);
$this->trongate_tokens->get_user_id($token);
$this->trongate_tokens->get_user_obj($token);
$this->trongate_tokens->get_user_level($token);
$this->trongate_tokens->destroy();
$this->trongate_tokens->regenerate();
$this->trongate_tokens->delete_old_tokens($user_id);
```

Security module:

```php
$this->trongate_security->make_sure_allowed('admin panel', $params);
```

Before altering auth behavior, inspect:

```text
config/login.php
modules/login/
modules/trongate_tokens/
modules/trongate_security/
modules/trongate_administrators/
```

## Error Handling And Environment

Current config:

```php
define('BASE_URL', '****');
define('ENV', 'dev');
define('DEFAULT_MODULE', 'setup');
define('DEFAULT_METHOD', 'index');
define('MODULE_ASSETS_TRIGGER', '_module');
define('ERROR_404', 'templates/error_404');
```

In `dev`, the framework may show more detailed errors and run setup/import behavior. In production:

- Set `ENV` to something other than `dev` or `DEV`.
- Configure `BASE_URL`.
- Configure database credentials.
- Hide/change admin routes if needed.
- Confirm `ERROR_404` points to a valid module/method.

## Agent Workflow For Changes

Before editing:

1. Inspect the target module and neighboring modules.
2. Check helper functions before writing new utility code.
3. Check existing templates/views for styling conventions.
4. Check `config/custom_routing.php` before adding URL assumptions.
5. Check `config/database.php` and table names before database work.

When implementing:

1. Keep changes inside the relevant module when possible.
2. Put request handling and orchestration in controllers.
3. Put database queries in module model files unless the surrounding module already uses a different pattern.
4. Put presentation in views.
5. Use built-in helpers and modules.
6. Keep public methods URL-safe.
7. Avoid unrelated refactors.

When verifying:

1. Run `php -l` on edited PHP files.
2. If a route is involved, test it in a browser or with a local HTTP request when the server is available.
3. For database changes, verify table names and SQL bindings.
4. For forms, verify CSRF token output, validation failure path, and success path.
5. For output changes, verify escaping with `out()`.

## Minimal Examples

Create/read page:

```php
class Products extends Trongate {

    public function index(): void {
        $data = [
            'view_module' => 'products',
            'view_file' => 'manage',
            'products' => $this->model->get_all_products()
        ];

        $this->templates->admin($data);
    }

    public function show(): void {
        $id = segment(3, 'int');
        $data['product'] = $this->model->db->get_where($id, 'products');

        if ($data['product'] === false) {
            redirect('products');
            return;
        }

        $this->view('show', $data);
    }
}
```

Submit form:

```php
public function submit(): void {
    $this->validation->set_rules('title', 'Title', 'required|min_length[3]|max_length[255]');

    if ($this->validation->run() === false) {
        $this->create();
        return;
    }

    $data = [
        'title' => post('title', true),
        'slug' => url_title(post('title', true)),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $this->model->db->insert($data, 'products');
    redirect('products');
}
```

View:

```php
<h1><?= out($headline ?? 'Products') ?></h1>

<?php foreach ($products as $product) { ?>
    <article>
        <h2><?= out($product->title) ?></h2>
        <?= anchor('products/show/' . (int) $product->id, 'View') ?>
    </article>
<?php } ?>
```
