// Thêm đoạn mã này vào file functions.php của theme

function load_more_products_shortcode() {
    ob_start();
    ?>
    <div id="product-list"></div>
    <button id="load-more-button">Load More Products</button>

    <style>
        #product-list {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        @media (max-width: 768px) {
            #product-list {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            #product-list {
                grid-template-columns: 1fr;
            }
        }

        .product-item {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .product-item img {
            max-width: 100%;
            height: auto;
        }

        .product-title {
            font-size: 16px;
            margin: 10px 0;
        }

        .product-price {
            color: #333;
            font-weight: bold;
        }
    </style>

    <script>
        let page = 1;
        const productsPerPage = 8;

        function loadProducts() {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=load_more_products&page=' + page)
                .then(response => response.json())
                .then(data => {
                    const productList = document.getElementById('product-list');
                    data.products.forEach(product => {
                        const productItem = document.createElement('div');
                        productItem.classList.add('product-item');
                        productItem.innerHTML = `
                            <img src="${product.image}" alt="${product.title}">
                            <div class="product-title">${product.title}</div>
                            <div class="product-price">${product.price}</div>
                        `;
                        productList.appendChild(productItem);
                    });
                    if (data.products.length < productsPerPage) {
                        document.getElementById('load-more-button').style.display = 'none';
                    }
                    page++;
                });
        }

        document.getElementById('load-more-button').addEventListener('click', loadProducts);

        // Load initial products
        loadProducts();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('load_more_products', 'load_more_products_shortcode');

function load_more_products_ajax() {
    $page = intval($_GET['page']);
    $products_per_page = 8;

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $products_per_page,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'ASC',
        'meta_key' => '_price',
        'orderby' => array(
            'meta_value_num' => 'ASC',
            'date' => 'DESC',
        ),
    );

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $products[] = array(
                'title' => $product->get_name(),
                'price' => $product->get_price_html(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'full'),
            );
        }
    }

    wp_reset_postdata();

    wp_send_json(array('products' => $products));
}
add_action('wp_ajax_load_more_products', 'load_more_products_ajax');
add_action('wp_ajax_nopriv_load_more_products', 'load_more_products_ajax');
