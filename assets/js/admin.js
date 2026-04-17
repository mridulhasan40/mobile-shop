/**
 * MobileShop — Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // Sidebar Toggle (Mobile)
    // ============================================
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('admin-sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        // Close sidebar on click outside (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!e.target.closest('#admin-sidebar') && !e.target.closest('#sidebar-toggle')) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }

    // ============================================
    // User Dropdown
    // ============================================
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu')) {
                userDropdown.classList.remove('active');
            }
        });
    }

    // ============================================
    // Image Preview on File Select
    // ============================================
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const preview = document.getElementById('image-preview');
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // ============================================
    // Confirm Delete Actions
    // ============================================
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ============================================
    // Flash Auto-dismiss
    // ============================================
    const flashMsg = document.getElementById('flash-message');
    if (flashMsg) {
        setTimeout(() => {
            flashMsg.style.opacity = '0';
            flashMsg.style.transform = 'translateY(-10px)';
            setTimeout(() => flashMsg.remove(), 300);
        }, 5000);
    }

    // ============================================
    // Stat Card Counter Animation
    // ============================================
    document.querySelectorAll('.stat-card-value').forEach(function(el) {
        const text = el.textContent.trim();
        const match = text.match(/[\d,.]+/);
        if (match) {
            const target = parseFloat(match[0].replace(/,/g, ''));
            if (!isNaN(target) && target > 0 && target < 100000) {
                const prefix = text.substring(0, text.indexOf(match[0]));
                const suffix = text.substring(text.indexOf(match[0]) + match[0].length);
                const decimals = match[0].includes('.') ? 2 : 0;
                let current = 0;
                const duration = 1000;
                const step = target / (duration / 16);

                const animate = () => {
                    current += step;
                    if (current >= target) {
                        el.textContent = text;
                        return;
                    }
                    el.textContent = prefix + current.toLocaleString('en-US', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals
                    }) + suffix;
                    requestAnimationFrame(animate);
                };
                animate();
            }
        }
    });
});
