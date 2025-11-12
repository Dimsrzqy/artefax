(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);


    // Fixed Navbar
    $(window).scroll(function () {
        if ($(window).width() < 992) {
            if ($(this).scrollTop() > 55) {
                $('.fixed-top').addClass('shadow');
            } else {
                $('.fixed-top').removeClass('shadow');
            }
        } else {
            if ($(this).scrollTop() > 55) {
                $('.fixed-top').addClass('shadow').css('top', -55);
            } else {
                $('.fixed-top').removeClass('shadow').css('top', 0);
            }
        } 
    });
    
    
   // Back to top button
   $(window).scroll(function () {
    if ($(this).scrollTop() > 300) {
        $('.back-to-top').fadeIn('slow');
    } else {
        $('.back-to-top').fadeOut('slow');
    }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    // Testimonial carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 2000,
        center: false,
        dots: true,
        loop: true,
        margin: 25,
        nav : true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0:{
                items:1
            },
            576:{
                items:1
            },
            768:{
                items:1
            },
            992:{
                items:2
            },
            1200:{
                items:2
            }
        }
    });


    // vegetable carousel
    $(".vegetable-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1500,
        center: false,
        dots: true,
        loop: true,
        margin: 25,
        nav : true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0:{
                items:1
            },
            576:{
                items:1
            },
            768:{
                items:2
            },
            992:{
                items:3
            },
            1200:{
                items:4
            }
        }
    });


    // Modal Video
    $(document).ready(function () {
        var $videoSrc;
        $('.btn-play').click(function () {
            $videoSrc = $(this).data("src");
        });
        console.log($videoSrc);

        $('#videoModal').on('shown.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc + "?autoplay=1&amp;modestbranding=1&amp;showinfo=0");
        })

        $('#videoModal').on('hide.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc);
        })
    });



    // Product Quantity
    $('.quantity button').on('click', function () {
        var button = $(this);
        var oldValue = button.parent().parent().find('input').val();
        if (button.hasClass('btn-plus')) {
            var newVal = parseFloat(oldValue) + 1;
        } else {
            if (oldValue > 0) {
                var newVal = parseFloat(oldValue) - 1;
            } else {
                newVal = 0;
            }
        }
        button.parent().parent().find('input').val(newVal);
    });


    // Search functionality
    function initializeSearch() {
        let searchTimeout;
        const searchInput = $('#searchInput');
        const searchResults = $('#searchResults');

        searchInput.on('keyup', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val().trim();

            if (query.length < 2) {
                searchResults.hide();
                return;
            }

            searchTimeout = setTimeout(() => {
                $.ajax({
                    url: 'api/search.php',
                    method: 'GET',
                    data: { query: query },
                    success: function(response) {
                        if (response.length > 0) {
                            const html = response.map(item => `
                                <div class="p-3 border-bottom search-item" data-id="${item.id}">
                                    <div class="d-flex align-items-center">
                                        <img src="${item.image}" alt="${item.name}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                        <div class="ms-3">
                                            <h6 class="mb-1">${item.name}</h6>
                                            <span class="text-primary">$${item.price}</span>
                                            <span class="text-muted ms-2">${item.category}</span>
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                            searchResults.html(html).show();
                        } else {
                            searchResults.html('<div class="p-3">No products found</div>').show();
                        }
                    },
                    error: function() {
                        searchResults.html('<div class="p-3">Error occurred while searching</div>').show();
                    }
                });
            }, 300);
        });
    }

    // Initialize search when document is ready
    $(document).ready(function() {
        initializeSearch();
    });

})(jQuery);
// chart
document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls
    document.querySelectorAll('.btn-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentNode.parentNode.querySelector('input[name="quantity"]');
            const value = parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
                input.form.submit();
            }
        });
    });

    document.querySelectorAll('.btn-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentNode.parentNode.querySelector('input[name="quantity"]');
            input.value = parseInt(input.value) + 1;
            input.form.submit();
        });
    });
});

function searchProducts() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    
    if (searchInput.value.length < 2) {
        searchResults.style.display = 'none';
        return;
    }

    fetch(`api/search.php?q=${encodeURIComponent(searchInput.value)}`)
        .then(response => response.json())
        .then(data => {
            searchResults.innerHTML = data.map(product => `
                <a href="shop-detail.php?id=${product.id}" class="d-block p-2 text-dark text-decoration-none">
                    ${product.name} - $${product.price}
                </a>
            `).join('');
            searchResults.style.display = 'block';
        })
        .catch(error => console.error('Error:', error));
}

// Cart functionality
function initializeCart() {
    // Quantity controls with auto-update
    $('.quantity button').on('click', function() {
        var button = $(this);
        var form = button.closest('form');
        var input = form.find('input[name="quantity"]');
        var oldValue = parseInt(input.val());
        
        if (button.hasClass('btn-plus')) {
            input.val(oldValue + 1);
        } else {
            if (oldValue > 1) {
                input.val(oldValue - 1);
            }
        }
        
        // Auto submit form when quantity changes
        if (form.length) {
            form.submit();
        }
        
        updateCartTotal();
    });
    
    // Update cart total
    function updateCartTotal() {
        var total = 0;
        $('.cart-item').each(function() {
            var price = parseFloat($(this).data('price'));
            var qty = parseInt($(this).find('input[name="quantity"]').val());
            total += price * qty;
        });
        
        $('#cart-subtotal').text('$' + total.toFixed(2));
        var shipping = parseFloat($('#shipping-cost').data('cost')) || 0;
        $('#cart-total').text('$' + (total + shipping).toFixed(2));
    }
}

// Initialize cart when document is ready
$(document).ready(function() {
    initializeCart();
});

// Checkout form validation
function initializeCheckout() {
    $('#checkout-form').on('submit', function(e) {
        var form = $(this);
        var valid = true;
        
        // Reset errors
        $('.form-error').remove();
        
        // Validate required fields
        form.find('[required]').each(function() {
            if (!$(this).val()) {
                valid = false;
                $(this).addClass('is-invalid')
                    .after('<div class="form-error text-danger">This field is required</div>');
            }
        });
        
        // Validate email
        var email = form.find('input[type="email"]');
        if (email.length && !isValidEmail(email.val())) {
            valid = false;
            email.addClass('is-invalid')
                .after('<div class="form-error text-danger">Please enter a valid email</div>');
        }
        
        if (!valid) {
            e.preventDefault();
        }
    });
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
}

// Initialize checkout when document is ready 
$(document).ready(function() {
    if ($('#checkout-form').length) {
        initializeCheckout();
    }
});
