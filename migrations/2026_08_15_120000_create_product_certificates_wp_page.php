<?php
return [
    'description' => 'Create the WP-wrapped "Product Certificates" page (/product-certificates/) so the Certificate Dashboard link on the customer-facing certificate lookup page opens with the normal site header/footer instead of the bare hcert_data.php endpoint',
    'up' => [
        // Table prefix assumed to be `wp_` (matches wp-config.php locally). If a
        // target environment uses a different prefix, every `wp_` reference below
        // needs adjusting.
        "SET @author = (SELECT post_author FROM wp_posts WHERE post_name = 'product-certificate' AND post_type = 'page' LIMIT 1)",
        "SET @author = COALESCE(@author, (SELECT post_author FROM wp_posts WHERE post_name = 'dashboard' AND post_type = 'page' LIMIT 1))",
        "SET @home = (SELECT option_value FROM wp_options WHERE option_name = 'home' LIMIT 1)",
        "SET @now = NOW()",
        "SET @now_gmt = UTC_TIMESTAMP()",

        // 1. Create the page (no-ops if the slug already exists)
        "INSERT INTO wp_posts
            (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,
             post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged,
             post_modified, post_modified_gmt, post_content_filtered, post_parent, guid,
             menu_order, post_type, post_mime_type, comment_count)
         SELECT @author, @now, @now_gmt, '', 'Product Certificates', '',
                'publish', 'closed', 'closed', '', 'product-certificates', '', '',
                @now, @now_gmt, '', 0, '',
                0, 'page', '', 0
         WHERE NOT EXISTS (
             SELECT 1 FROM wp_posts WHERE post_name = 'product-certificates' AND post_type = 'page'
         )",

        "SET @new_id = (SELECT ID FROM wp_posts WHERE post_name = 'product-certificates' AND post_type = 'page' LIMIT 1)",

        // 2. Fix up the guid now that we know the real ID
        "UPDATE wp_posts SET guid = CONCAT(@home, '/?page_id=', @new_id) WHERE ID = @new_id",

        // 3. Attach the scscpq-hcert_data template + the standard ACF/theme
        //    defaults every other scscpq page carries
        "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
         SELECT @new_id, k.meta_key, k.meta_value FROM (
             SELECT 'meta-checkbox' AS meta_key, '' AS meta_value
             UNION ALL SELECT '_wp_page_template', 'layouts/template-scscpq-hcert_data.php'
             UNION ALL SELECT 'owl_slider', '0'
             UNION ALL SELECT '_owl_slider', 'field_5b9a7fb203358'
             UNION ALL SELECT 'excerpt', ''
             UNION ALL SELECT '_excerpt', 'field_5d9ce29c8c639'
             UNION ALL SELECT 'cover_type', 'standard'
             UNION ALL SELECT '_cover_type', 'field_5e01098ae0ec3'
             UNION ALL SELECT 'cmplz_hide_cookiebanner', ''
         ) k
         WHERE NOT EXISTS (
             SELECT 1 FROM wp_postmeta WHERE post_id = @new_id AND meta_key = '_wp_page_template'
         )",

        // 4. Register with WPML. WPML inner-joins wp_icl_translations on every
        //    front-end query -- without this row the page 404s even though it
        //    exists and its template renders fine.
        "SET @new_trid = (SELECT COALESCE(MAX(trid), 0) + 1 FROM wp_icl_translations)",
        "INSERT INTO wp_icl_translations
            (element_type, element_id, trid, language_code, source_language_code)
         SELECT 'post_page', @new_id, @new_trid, 'en', NULL
         WHERE NOT EXISTS (
             SELECT 1 FROM wp_icl_translations WHERE element_type = 'post_page' AND element_id = @new_id
         )",

        // 5. Force WP to regenerate rewrite rules on next load, so the pretty
        //    permalink /product-certificates/ resolves immediately instead of 404
        "DELETE FROM wp_options WHERE option_name = 'rewrite_rules'",
    ],
    'down' => [
        "DELETE FROM wp_icl_translations WHERE element_type = 'post_page' AND element_id = (
            SELECT ID FROM wp_posts WHERE post_name = 'product-certificates' AND post_type = 'page' LIMIT 1
        )",
        "DELETE FROM wp_postmeta WHERE post_id = (
            SELECT ID FROM wp_posts WHERE post_name = 'product-certificates' AND post_type = 'page' LIMIT 1
        )",
        "DELETE FROM wp_posts WHERE post_name = 'product-certificates' AND post_type = 'page'",
        "DELETE FROM wp_options WHERE option_name = 'rewrite_rules'",
    ],
];
