<h1 class="mt-2">Enjoy Trongate!</h1>
<h2>The Native PHP Framework</h2>
<p class="mt-2">You have successfully installed Trongate. You're now ready to start building fast, efficient web applications.</p>

<div class="mt-3">
    <?php
    if (strtolower(ENV) === 'dev') {
        echo anchor('trongate_administrators/manage', 'Admin Panel', ['class' => 'button']);
    } else {
        echo anchor('https://trongate.io', 'Visit Trongate.io', ['class' => 'button', 'target' => '_blank']);
    }
    echo anchor('https://trongate.io/docs', 'View Documentation', ['class' => 'button alt', 'target' => '_blank']);
    ?>
</div>

<hr class="mt-5 mb-5">

<div class="flex-row justify-between align-center">
    <h1 style="margin: 0;">Component Showcase</h1>
    <?= anchor(BASE_URL, 'Back to Home', ['class' => 'button alt']) ?>
</div>
<p class="text-muted">A live reference of the styled components available in this project.</p>

<hr class="mt-3 mb-3">

<h3>Buttons</h3>
<div class="flex-row flex-wrap gap-1">
    <button>Primary</button>
    <button class="success">Success</button>
    <button class="danger">Danger</button>
    <button class="warning">Warning</button>
    <button class="inverse">Inverse</button>
    <button class="alt">Alternative</button>
    <button disabled>Disabled</button>
</div>

<hr>

<h3>Badges and Pills</h3>
<div class="flex-row flex-wrap gap-1 mt-1">
    <span class="badge">Default</span>
    <span class="badge primary">Primary</span>
    <span class="badge success">Active</span>
    <span class="badge warning">Pending</span>
    <span class="badge danger">Urgent</span>
</div>

<div class="flex-row flex-wrap gap-1 mt-2">
    <span class="pill">Default</span>
    <span class="pill primary">New</span>
    <span class="pill success">Live</span>
    <span class="pill warning">Draft</span>
    <span class="pill danger">Error</span>
</div>

<hr>

<h3>Alerts</h3>
<div class="alert alert-success">Your changes have been saved successfully.</div>
<div class="alert alert-danger">Something went wrong. Please try again.</div>
<div class="alert alert-warning">This action cannot be undone.</div>
<div class="alert alert-info">A new version is available.</div>

<hr>

<h3>Cards</h3>
<div class="grid-3 gap-2 mt-2">
    <div class="card">
        <div class="card-heading">Default Card</div>
        <div class="card-body">
            <p>Standard card with the primary color heading.</p>
        </div>
    </div>
    <div class="card">
        <div class="card-heading success">Success Card</div>
        <div class="card-body">
            <p>Card with a success-colored heading.</p>
        </div>
    </div>
    <div class="card">
        <div class="card-heading danger">Danger Card</div>
        <div class="card-body">
            <p>Card with a danger-colored heading.</p>
        </div>
    </div>
</div>

<hr>

<h3>Elevation Scale</h3>
<div class="grid-auto gap-2 mt-2">
    <div class="card-body elev-1 text-center">elev-1</div>
    <div class="card-body elev-2 text-center">elev-2</div>
    <div class="card-body elev-3 text-center">elev-3</div>
    <div class="card-body elev-4 text-center">elev-4</div>
    <div class="card-body elev-5 text-center">elev-5</div>
</div>

<hr>

<h3>Tables</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>001</td>
            <td>Sample Item One</td>
            <td><span class="pill success">Active</span></td>
            <td>2026-01-15</td>
        </tr>
        <tr>
            <td>002</td>
            <td>Sample Item Two</td>
            <td><span class="pill warning">Pending</span></td>
            <td>2026-01-18</td>
        </tr>
        <tr>
            <td>003</td>
            <td>Sample Item Three</td>
            <td><span class="pill danger">Inactive</span></td>
            <td>2026-01-22</td>
        </tr>
        <tr>
            <td>004</td>
            <td>Sample Item Four</td>
            <td><span class="pill primary">New</span></td>
            <td>2026-01-25</td>
        </tr>
    </tbody>
</table>

<hr>

<h3>Forms</h3>
<div class="grid-2 gap-3 mt-2">
    <div>
        <div class="field">
            <label>Text Input</label>
            <input type="text" placeholder="Enter some text">
            <div class="field-help">A standard text input with help text below.</div>
        </div>
        <div class="field">
            <label>Email</label>
            <input type="email" placeholder="you@example.com">
        </div>
        <div class="field">
            <label>Dropdown</label>
            <select>
                <option>Choose an option</option>
                <option>Option One</option>
                <option>Option Two</option>
                <option>Option Three</option>
            </select>
        </div>
        <div class="field">
            <label>Textarea</label>
            <textarea placeholder="Enter a longer message..." rows="3"></textarea>
        </div>
    </div>
    <div>
        <div class="field">
            <label>Field with Error</label>
            <input type="text" class="form-field-validation-error" value="Invalid input">
            <div class="field-error">This field is required.</div>
        </div>
        <div class="field">
            <label>Input Group (Currency)</label>
            <div class="input-group">
                <span class="input-group-addon">$</span>
                <input type="number" placeholder="0.00">
                <span class="input-group-addon">.00</span>
            </div>
        </div>
        <div class="field">
            <label>Date Picker</label>
            <input type="date">
        </div>
        <div class="field">
            <label>
                <input type="checkbox"> Checkbox option
            </label>
            <label>
                <input type="radio" name="demo-radio"> Radio option one
            </label>
            <label>
                <input type="radio" name="demo-radio"> Radio option two
            </label>
        </div>
    </div>
</div>

<hr>

<h3>Breadcrumbs</h3>
<nav class="breadcrumbs">
    <a href="#">Home</a>
    <span class="separator">/</span>
    <a href="#">Section</a>
    <span class="separator">/</span>
    <span class="current">Current Page</span>
</nav>

<h3 class="mt-3">Pagination</h3>
<div class="pagination">
    <a href="#">&laquo;</a>
    <a href="#">1</a>
    <a href="#" class="active">2</a>
    <a href="#">3</a>
    <a href="#">4</a>
    <a href="#">5</a>
    <a href="#">&raquo;</a>
</div>

<h3 class="mt-3">Tabs</h3>
<div class="tabs">
    <a class="tab active">Overview</a>
    <a class="tab">Details</a>
    <a class="tab">Settings</a>
</div>
<div class="tab-panel active">
    <p>This is the active tab panel content. Add JavaScript to toggle between panels.</p>
</div>

<hr>

<h3>Tooltips</h3>
<p>Hover over the items below to see CSS-only tooltips.</p>
<div class="flex-row gap-3 mt-1">
    <span data-tooltip="This is a helpful tooltip">Hover for info</span>
    <span data-tooltip="Click to learn more">Another tooltip</span>
    <span data-tooltip="No JavaScript required">Pure CSS</span>
</div>

<hr>

<h3>Loading States</h3>
<div class="flex-row gap-3 align-center mt-2">
    <div class="spinner"></div>
    <span>Loading content...</span>
</div>

<div class="card mt-3">
    <div class="card-body">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-text"></div>
        <div class="skeleton skeleton-text"></div>
        <div class="skeleton skeleton-line"></div>
    </div>
</div>

<hr>

<h3>Brand Colors</h3>
<div class="grid-auto gap-1 mt-2">
    <div class="bg-primary p-1 text-center">bg-primary</div>
    <div class="bg-secondary p-1 text-center">bg-secondary</div>
    <div class="bg-success p-1 text-center">bg-success</div>
    <div class="bg-danger p-1 text-center">bg-danger</div>
    <div class="bg-warning p-1 text-center">bg-warning</div>
    <div class="bg-info p-1 text-center">bg-info</div>
    <div class="bg-neutral p-1 text-center">bg-neutral</div>
    <div class="bg-inverse p-1 text-center">bg-inverse</div>
</div>

<div class="mt-3 mb-3 text-center">
    <?= anchor(BASE_URL, 'Back to Home', ['class' => 'button alt']) ?>
</div>