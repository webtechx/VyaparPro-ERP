# Cube Loader Component - Usage Guide

## Installation

### 1. Include CSS in your page header
```html
<link rel="stylesheet" href="<?= $basePath ?>/public/assets/css/cube-loader.css">
```

### 2. Include JavaScript before closing body tag
```html
<script src="<?= $basePath ?>/public/assets/js/cube-loader.js"></script>
```

---

## Basic Usage

### Show Loader
```javascript
// Simple usage with default message
CubeLoader.show();

// With custom message
CubeLoader.show('Saving data...');

// With custom minimum display time (3 seconds)
CubeLoader.show('Processing...', 3000);
```

### Hide Loader
```javascript
// Simple hide
CubeLoader.hide();

// With callback function
CubeLoader.hide(function() {
    console.log('Loader hidden!');
    // Do something after loader is hidden
});
```

### Update Message While Loading
```javascript
CubeLoader.updateMessage('Almost done...');
```

---

## Common Use Cases

### 1. AJAX Submit/Update/Delete Operations
```javascript
// Example: Save Invoice
$('#save_invoice_btn').on('click', function() {
    CubeLoader.show('Saving Invoice...');
    
    $.ajax({
        url: 'controller/save_invoice.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            CubeLoader.hide(function() {
                // Show success message
                alert('Invoice saved successfully!');
            });
        },
        error: function() {
            CubeLoader.hide(function() {
                alert('Error saving invoice!');
            });
        }
    });
});
```

### 2. Form Submission
```javascript
$('#myForm').on('submit', function(e) {
    e.preventDefault();
    
    CubeLoader.show('Submitting form...');
    
    // Your form submission logic
    setTimeout(function() {
        CubeLoader.hide();
    }, 2000);
});
```

### 3. Delete Operation
```javascript
function deleteRecord(id) {
    if (confirm('Are you sure?')) {
        CubeLoader.show('Deleting record...');
        
        $.ajax({
            url: 'controller/delete.php',
            method: 'POST',
            data: { id: id },
            success: function() {
                CubeLoader.hide(function() {
                    location.reload();
                });
            }
        });
    }
}
```

### 4. Fetch/Load Data
```javascript
function fetchProforma() {
    let proformaNo = $('#proforma_fetch_no').val();
    
    CubeLoader.show('Fetching Proforma Data...');
    
    $.ajax({
        url: 'controller/get_proforma.php',
        method: 'GET',
        data: { proforma_number: proformaNo },
        success: function(response) {
            CubeLoader.hide(function() {
                // Process response
                populateForm(response);
            });
        },
        error: function() {
            CubeLoader.hide(function() {
                alert('Error fetching data!');
            });
        }
    });
}
```

### 5. Multi-step Process
```javascript
function processData() {
    CubeLoader.show('Step 1: Validating...');
    
    setTimeout(function() {
        CubeLoader.updateMessage('Step 2: Processing...');
        
        setTimeout(function() {
            CubeLoader.updateMessage('Step 3: Finalizing...');
            
            setTimeout(function() {
                CubeLoader.hide(function() {
                    alert('Process complete!');
                });
            }, 1000);
        }, 1000);
    }, 1000);
}
```

---

## API Reference

### Methods

#### `CubeLoader.show(message, minTime)`
Shows the loader with optional message and minimum display time.
- **message** (string, optional): Loading message. Default: "Loading..."
- **minTime** (number, optional): Minimum display time in milliseconds. Default: 2000

#### `CubeLoader.hide(callback)`
Hides the loader after minimum display time.
- **callback** (function, optional): Function to execute after loader is hidden

#### `CubeLoader.updateMessage(message)`
Updates the loading message while loader is visible.
- **message** (string): New message to display

#### `CubeLoader.setMinDisplayTime(time)`
Sets the default minimum display time.
- **time** (number): Time in milliseconds

---

## Customization

### Change Minimum Display Time Globally
```javascript
// Set to 3 seconds for all loaders
CubeLoader.setMinDisplayTime(3000);
```

### Disable Minimum Display Time
```javascript
// Show loader without minimum time
CubeLoader.show('Quick operation...', 0);
```

---

## Example: Complete CRUD Operation

```javascript
// CREATE
$('#createBtn').on('click', function() {
    CubeLoader.show('Creating record...');
    $.post('create.php', data, function() {
        CubeLoader.hide(() => alert('Created!'));
    });
});

// READ/FETCH
$('#fetchBtn').on('click', function() {
    CubeLoader.show('Loading data...');
    $.get('read.php', function(data) {
        CubeLoader.hide(() => displayData(data));
    });
});

// UPDATE
$('#updateBtn').on('click', function() {
    CubeLoader.show('Updating record...');
    $.post('update.php', data, function() {
        CubeLoader.hide(() => alert('Updated!'));
    });
});

// DELETE
$('#deleteBtn').on('click', function() {
    CubeLoader.show('Deleting record...');
    $.post('delete.php', {id: id}, function() {
        CubeLoader.hide(() => location.reload());
    });
});
```

---

## Notes
- Loader automatically initializes on page load
- Minimum display time ensures users see the animation (default: 2 seconds)
- Loader uses z-index: 99999 to appear above all content
- Dark overlay with blur effect for better visibility
- White cube animation with smooth folding effect
