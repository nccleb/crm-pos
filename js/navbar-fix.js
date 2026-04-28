// Add this script at the bottom of call_history.php before </body>


// Fix for navbar dropdowns - ensures both modern navbar and Bootstrap work together
document.addEventListener('DOMContentLoaded', function() {
    // Fix for modern navbar dropdowns
    const modernDropdowns = document.querySelectorAll('.navbar-menu .dropdown-item button.nav-link, .modern-navbar .dropdown-item button.nav-link');
    
    modernDropdowns.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdownMenu = this.nextElementSibling;
            const parentItem = this.closest('.dropdown-item');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
                if (menu !== dropdownMenu) {
                    menu.classList.remove('active');
                    menu.previousElementSibling?.closest('.dropdown-item')?.classList.remove('active');
                }
            });
            
            // Toggle current dropdown
            if (dropdownMenu) {
                dropdownMenu.classList.toggle('active');
                parentItem?.classList.toggle('active');
            }
        });
    });
    
    // Fix Bootstrap standard dropdowns (for pages without modern navbar)
    const standardDropdowns = document.querySelectorAll('.navbar .dropdown-toggle');
    
    standardDropdowns.forEach(toggle => {
        // If it's NOT part of modern navbar, use Bootstrap behavior
        if (!toggle.closest('.modern-navbar')) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const dropdownMenu = this.nextElementSibling;
                
                // Close other Bootstrap dropdowns
                document.querySelectorAll('.navbar .dropdown-menu.show').forEach(menu => {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove('show');
                    }
                });
                
                // Toggle current
                dropdownMenu?.classList.toggle('show');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        // Close modern navbar dropdowns
        if (!e.target.closest('.dropdown-item')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
                menu.classList.remove('active');
                menu.previousElementSibling?.closest('.dropdown-item')?.classList.remove('active');
            });
        }
        
        // Close standard Bootstrap dropdowns
        if (!e.target.closest('.navbar .dropdown')) {
            document.querySelectorAll('.navbar .dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
    // Ensure navbar links are clickable
    document.querySelectorAll('.navbar a[href], .navbar button[onclick]').forEach(element => {
        if (!element.classList.contains('dropdown-toggle')) {
            element.style.pointerEvents = 'auto';
            element.style.cursor = 'pointer';
        }
    });
});

// Additional fix: Make onclick functions work properly
function ensureOnclickWorks() {
    const onclickElements = document.querySelectorAll('[onclick]');
    onclickElements.forEach(el => {
        const onclick = el.getAttribute('onclick');
        if (onclick && !el.hasAttribute('data-onclick-fixed')) {
            el.setAttribute('data-onclick-fixed', 'true');
            el.addEventListener('click', function(e) {
                // Don't prevent default for links, only for buttons
                if (this.tagName !== 'A') {
                    e.preventDefault();
                }
                e.stopPropagation();
                try {
                    eval(onclick);
                } catch (error) {
                    console.error('Error executing onclick:', error);
                }
            });
        }
    });
}

// Run on load and after any dynamic content changes
document.addEventListener('DOMContentLoaded', ensureOnclickWorks);
setTimeout(ensureOnclickWorks, 500); // Delayed check
