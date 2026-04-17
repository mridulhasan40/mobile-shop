/**
 * MobileShop — Main JavaScript
 * Cart AJAX, Live Search, UI Interactions
 */

const SITE_URL = (document.querySelector('meta[name="site-url"]')?.content || window.location.origin + '/mobile-shop').replace(/\/+$/, '');
const API_URL = SITE_URL + '/api';

// ============================================
// Toast Notifications
// ============================================
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// ============================================
// Cart Operations
// ============================================
async function addToCart(productId, quantity = 1) {
    try {
        const response = await fetch(API_URL + '/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: parseInt(quantity) })
        });

        const data = await response.json();

        if (data.requireLogin) {
            window.location.href = SITE_URL + '/pages/login.php';
            return;
        }

        if (data.success) {
            showToast(data.message, 'success');
            updateCartBadge(data.cartCount);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Something went wrong. Please try again.', 'error');
    }
}

async function updateCartQty(productId, quantity) {
    if (quantity < 1) {
        removeFromCart(productId);
        return;
    }

    try {
        const response = await fetch(API_URL + '/cart.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: quantity })
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Failed to update cart.', 'error');
    }
}

async function removeFromCart(productId) {
    try {
        const response = await fetch(API_URL + '/cart.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });

        const data = await response.json();

        if (data.success) {
            const item = document.getElementById(`cart-item-${productId}`);
            if (item) {
                item.style.animation = 'fadeOut 0.3s ease forwards';
                setTimeout(() => {
                    item.remove();
                    // Check if cart is empty
                    const remaining = document.querySelectorAll('[id^="cart-item-"]');
                    if (remaining.length === 0) {
                        location.reload();
                    }
                }, 300);
            }
            showToast(data.message, 'success');
            updateCartBadge(data.cartCount);
        }
    } catch (error) {
        showToast('Failed to remove item.', 'error');
    }
}

function updateCartBadge(count) {
    let badge = document.getElementById('cart-badge');
    const cartBtn = document.getElementById('cart-btn');

    if (count > 0) {
        if (!badge && cartBtn) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.id = 'cart-badge';
            cartBtn.appendChild(badge);
        }
        if (badge) {
            badge.textContent = count;
            badge.style.animation = 'pulse 0.3s ease';
        }
    } else {
        if (badge) badge.remove();
    }
}

// ============================================
// Live Search
// ============================================
let searchTimeout;

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.classList.remove('active');
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(API_URL + `/search.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.success && data.products.length > 0) {
                        searchResults.innerHTML = data.products.map(p => `
                            <a href="${p.url}" class="search-result-item">
                                <img src="${p.image_url}" alt="${p.name}">
                                <div class="search-result-info">
                                    <h4>${p.name}</h4>
                                    <span>${p.price_formatted}</span>
                                </div>
                            </a>
                        `).join('');
                        searchResults.classList.add('active');
                    } else {
                        searchResults.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--text-muted); font-size: 14px;">No results found</div>';
                        searchResults.classList.add('active');
                    }
                } catch (error) {
                    searchResults.classList.remove('active');
                }
            }, 300);
        });

        // Submit search on enter
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query) {
                    window.location.href = SITE_URL + '/pages/products.php?search=' + encodeURIComponent(query);
                }
            }
        });

        // Close search results on click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#header-search')) {
                searchResults.classList.remove('active');
            }
        });
    }

    // ============================================
    // Mobile Menu Toggle
    // ============================================
    const menuToggle = document.getElementById('menu-toggle');
    const navLinks = document.getElementById('nav-links');

    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
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
    // Flash Message Auto-dismiss
    // ============================================
    const flashMsg = document.getElementById('flash-message');
    if (flashMsg) {
        setTimeout(() => {
            flashMsg.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => flashMsg.remove(), 300);
        }, 5000);
    }

    // ============================================
    // Scroll Animations
    // ============================================
    const animateElements = document.querySelectorAll('.product-card, .category-card, .stat-card');
    
    if (animateElements.length > 0 && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 50);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        animateElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(el);
        });
    }

    // ============================================
    // Header Scroll Effect
    // ============================================
    const header = document.getElementById('main-header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.style.background = 'rgba(255, 255, 255, 0.97)';
                header.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.08)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.92)';
                header.style.boxShadow = 'none';
            }
        });
    }
});
