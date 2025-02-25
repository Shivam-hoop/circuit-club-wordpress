<?php

class Migration {

    //cbe stands for Custom BuddyPress Endpoints
    private $migration_option = 'cbe_migrations';

    // Store all migrations
    public function get_migrations() {
        $migrations = get_option( $this->migration_option, [] );
        return is_array( $migrations ) ? $migrations : [];
    }

    // Mark a migration as completed
    public function mark_migration_as_done( $migration ) {
        $migrations = $this->get_migrations();
        if ( ! in_array( $migration, $migrations ) ) {
            $migrations[] = $migration;
            update_option( $this->migration_option, $migrations );
        }
    }

    // Run all pending migrations
    public function run_migrations() {
        error_log("initiated");
        $migrations = $this->get_migrations();
        $migration_files = $this->get_migration_files();

        foreach ( $migration_files as $file ) {
            $migration_name = basename( $file, '.php' );
            if ( ! in_array( $migration_name, $migrations ) ) {
                require_once $file;
                $this->mark_migration_as_done( $migration_name );
            }
        }
    }

    // Get all migration files in the migrations folder
    private function get_migration_files() {
        error_log('runs');
        // $migrations_dir = plugin_dir_path( __FILE__ ) . 'migrations/';
        $migrations_dir = ABSPATH . 'wp-content/plugins/buddypress-custom-endpoints/migrations/';
        error_log($migrations_dir);
        $files = glob( $migrations_dir . '*.php' );
        sort( $files ); // Ensure migrations run in the correct order
        return $files;
    }
}
