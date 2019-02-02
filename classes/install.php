<?php
if ( ! class_exists( 'FikenDB' ) ) {

    include_once FIKEN_PLUGIN_DIR . 'classes/utils.php';

    class FikenDB
    {

        public static function install()
        {
            global $wpdb;
            $wpdb->hide_errors();
            $collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset)) {
                    $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
                }
                if (!empty($wpdb->collate)) {
                    $collate .= " COLLATE $wpdb->collate";
                }
            }

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $tables = "
                   CREATE TABLE {$wpdb->prefix}fiken_orders (
					id_order INT(10) NOT NULL,
					current_state INT(10) NOT NULL,
					mes TEXT,
					last_update DATETIME NOT NULL,
	    			PRIMARY KEY  (id_order)
				    )$collate;
			      CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fiken_order_history (
                    id_history INT(10) NOT NULL AUTO_INCREMENT,
					id_order INT(10) NOT NULL,
					id_state INT(10) NOT NULL,
					mes TEXT,
					last_update DATETIME NOT NULL,
	    			PRIMARY KEY  (id_history),
	    			KEY  id_order (id_order)
				  )$collate;
                  CREATE TABLE {$wpdb->prefix}fiken_states (
					id_state INT(10) NOT NULL,
					name VARCHAR(50) NOT NULL,
					PRIMARY KEY  (id_state)
                  )$collate;
                  ";

            dbDelta($tables);

            $wpdb->query("INSERT INTO `{$wpdb->prefix}fiken_states` (`id_state`, `name`)
                                    SELECT * FROM (SELECT 1, 'Not  transferred') AS tmp
                                    WHERE NOT EXISTS ( SELECT `id_state` FROM `{$wpdb->prefix}fiken_states` WHERE id_state = 1) LIMIT 1");

            $wpdb->query("INSERT INTO `{$wpdb->prefix}fiken_states` (`id_state`, `name`)
                                      SELECT * FROM (SELECT 2, 'Transferred') AS tmp
                                      WHERE NOT EXISTS ( SELECT `id_state` FROM `{$wpdb->prefix}fiken_states` WHERE id_state = 2) LIMIT 1");

            $wpdb->query("INSERT INTO `{$wpdb->prefix}fiken_states` (`id_state`, `name`)
                                    SELECT * FROM (SELECT 3, 'Failed') AS tmp
                                    WHERE NOT EXISTS ( SELECT `id_state` FROM `{$wpdb->prefix}fiken_states` WHERE id_state = 3) LIMIT 1");

            $wpdb->query("INSERT INTO `{$wpdb->prefix}fiken_states` (`id_state`, `name`)
                                    SELECT * FROM (SELECT 4, 'Skipped') AS tmp
                                    WHERE NOT EXISTS ( SELECT `id_state` FROM `{$wpdb->prefix}fiken_states` WHERE id_state = 4) LIMIT 1");

            $wpdb->query("INSERT INTO `{$wpdb->prefix}fiken_states` (`id_state`, `name`)
                                    SELECT * FROM (SELECT 5, 'Partially-skipped') AS tmp
                                    WHERE NOT EXISTS ( SELECT `id_state` FROM `{$wpdb->prefix}fiken_states` WHERE id_state = 5) LIMIT 1");


            $wpdb->query("INSERT INTO `{$wpdb->prefix}fiken_order_history` (`id_order`, `id_state`, `mes`, `last_update`)
                                    SELECT o.`id`, 1 , '' as mes, NOW()
                                    FROM `{$wpdb->prefix}posts` o
                                    WHERE o.`post_type` = 'shop_order' and o.`id` not in (select `id_order` from `{$wpdb->prefix}fiken_orders`)");

            $wpdb->query("INSERT INTO `{$wpdb->prefix}fiken_orders` (`id_order`, `current_state`, `mes`, `last_update`)
                                    SELECT o.`id`, 1 , '' as mes, NOW()
                                    FROM `{$wpdb->prefix}posts` o
                                    WHERE o.`post_type` = 'shop_order' and o.`id` not in (select `id_order` from `{$wpdb->prefix}fiken_orders`)");
        }

        public static function uninstall()
        {
        /*NOP*/
        }

    }
}