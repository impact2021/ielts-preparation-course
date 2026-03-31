<?php
/**
 * Face-to-Face Courses Manager
 *
 * Manages physical/face-to-face courses with date scheduling,
 * attendee registration, and payment integration.
 *
 * Shortcode: [fc_course_calendar course_id="x"]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FC_Courses {

    /** @var string Database table names */
    private $courses_table;
    private $dates_table;
    private $registrations_table;

    /** @var string Option keys */
    const OPTION_SETTINGS       = 'fc_courses_settings';
    const OPTION_ATTENDEE_TYPES = 'fc_courses_attendee_types';

    /** @var string Nonce actions */
    const NONCE_SAVE_COURSE    = 'fc_save_course';
    const NONCE_SAVE_DATE      = 'fc_save_date';
    const NONCE_REGISTER       = 'fc_register_course';
    const NONCE_SETTINGS       = 'fc_courses_settings_nonce';

    public function __construct() {
        global $wpdb;
        $this->courses_table       = $wpdb->prefix . 'fc_courses';
        $this->dates_table         = $wpdb->prefix . 'fc_course_dates';
        $this->registrations_table = $wpdb->prefix . 'fc_registrations';
    }

    /**
     * Boot all hooks.
     */
    public function init() {
        // Database / activation
        add_action( 'plugins_loaded', array( $this, 'maybe_create_tables' ) );

        // Admin
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_admin_forms' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Shortcode
        add_shortcode( 'fc_course_calendar', array( $this, 'shortcode_calendar' ) );

        // Frontend scripts / AJAX
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'wp_ajax_fc_submit_registration',          array( $this, 'ajax_submit_registration' ) );
        add_action( 'wp_ajax_nopriv_fc_submit_registration',   array( $this, 'ajax_submit_registration' ) );
    }

    // -------------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------------

    /**
     * Create tables if they do not exist yet.
     */
    public function maybe_create_tables() {
        $version_key = 'fc_courses_db_version';
        $db_version  = '1.0';

        if ( get_option( $version_key ) === $db_version ) {
            return;
        }

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        $sql[] = "CREATE TABLE IF NOT EXISTS {$this->courses_table} (
            id          bigint(20) NOT NULL AUTO_INCREMENT,
            name        varchar(255) NOT NULL DEFAULT '',
            description longtext,
            location    varchar(255),
            price       decimal(10,2) DEFAULT 0.00,
            created_at  datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE IF NOT EXISTS {$this->dates_table} (
            id          bigint(20) NOT NULL AUTO_INCREMENT,
            course_id   bigint(20) NOT NULL,
            date        date NOT NULL,
            time        varchar(50),
            capacity    int(11) DEFAULT 0,
            created_at  datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY course_id (course_id)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE IF NOT EXISTS {$this->registrations_table} (
            id              bigint(20) NOT NULL AUTO_INCREMENT,
            course_date_id  bigint(20) NOT NULL,
            first_name      varchar(100) NOT NULL DEFAULT '',
            last_name       varchar(100) NOT NULL DEFAULT '',
            email           varchar(200) NOT NULL DEFAULT '',
            phone           varchar(50),
            attendee_type   varchar(100),
            payment_status  varchar(50) DEFAULT 'pending',
            created_at      datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY course_date_id (course_date_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $sql as $query ) {
            dbDelta( $query );
        }

        update_option( $version_key, $db_version );
    }

    // -------------------------------------------------------------------------
    // Admin menu
    // -------------------------------------------------------------------------

    public function add_admin_menu() {
        add_menu_page(
            __( 'FC Courses', 'ielts-course-manager' ),
            __( 'FC Courses', 'ielts-course-manager' ),
            'manage_options',
            'fc-courses-courses',
            array( $this, 'page_courses' ),
            'dashicons-calendar-alt',
            58
        );

        add_submenu_page(
            'fc-courses-courses',
            __( 'Courses', 'ielts-course-manager' ),
            __( 'Courses', 'ielts-course-manager' ),
            'manage_options',
            'fc-courses-courses',
            array( $this, 'page_courses' )
        );

        add_submenu_page(
            'fc-courses-courses',
            __( 'Course Dates', 'ielts-course-manager' ),
            __( 'Course Dates', 'ielts-course-manager' ),
            'manage_options',
            'fc-courses-course-dates',
            array( $this, 'page_course_dates' )
        );

        add_submenu_page(
            'fc-courses-courses',
            __( 'Settings', 'ielts-course-manager' ),
            __( 'Settings', 'ielts-course-manager' ),
            'manage_options',
            'fc-courses-settings',
            array( $this, 'page_settings' )
        );
    }

    // -------------------------------------------------------------------------
    // Admin form handling (save / delete)
    // -------------------------------------------------------------------------

    public function handle_admin_forms() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Save course
        if ( isset( $_POST['fc_save_course'] ) ) {
            check_admin_referer( self::NONCE_SAVE_COURSE );
            $this->save_course();
        }

        // Delete course
        if ( isset( $_GET['fc_delete_course'] ) && isset( $_GET['_wpnonce'] ) ) {
            if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'fc_delete_course_' . absint( $_GET['fc_delete_course'] ) ) ) {
                $this->delete_course( absint( $_GET['fc_delete_course'] ) );
                wp_redirect( admin_url( 'admin.php?page=fc-courses-courses&deleted=1' ) );
                exit;
            }
        }

        // Save date
        if ( isset( $_POST['fc_save_date'] ) ) {
            check_admin_referer( self::NONCE_SAVE_DATE );
            $this->save_course_date();
        }

        // Delete date
        if ( isset( $_GET['fc_delete_date'] ) && isset( $_GET['_wpnonce'] ) ) {
            if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'fc_delete_date_' . absint( $_GET['fc_delete_date'] ) ) ) {
                $this->delete_course_date( absint( $_GET['fc_delete_date'] ) );
                wp_redirect( admin_url( 'admin.php?page=fc-courses-course-dates&deleted=1' ) );
                exit;
            }
        }

        // Save settings
        if ( isset( $_POST['fc_save_settings'] ) ) {
            check_admin_referer( self::NONCE_SETTINGS );
            $this->save_settings();
        }
    }

    // -------------------------------------------------------------------------
    // CRUD helpers
    // -------------------------------------------------------------------------

    private function save_course() {
        global $wpdb;

        $id          = absint( $_POST['fc_course_id'] ?? 0 );
        $name        = sanitize_text_field( wp_unslash( $_POST['fc_course_name'] ?? '' ) );
        $description = wp_kses_post( wp_unslash( $_POST['fc_course_description'] ?? '' ) );
        $location    = sanitize_text_field( wp_unslash( $_POST['fc_course_location'] ?? '' ) );
        $price       = (float) ( $_POST['fc_course_price'] ?? 0 );

        $data = array(
            'name'        => $name,
            'description' => $description,
            'location'    => $location,
            'price'       => $price,
        );

        if ( $id > 0 ) {
            $wpdb->update( $this->courses_table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        } else {
            $wpdb->insert( $this->courses_table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }

    private function delete_course( $id ) {
        global $wpdb;
        $wpdb->delete( $this->courses_table, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        // Also delete dates
        $wpdb->delete( $this->dates_table, array( 'course_id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    private function save_course_date() {
        global $wpdb;

        $id        = absint( $_POST['fc_date_id'] ?? 0 );
        $course_id = absint( $_POST['fc_date_course_id'] ?? 0 );
        $date      = sanitize_text_field( wp_unslash( $_POST['fc_date_date'] ?? '' ) );
        $time      = sanitize_text_field( wp_unslash( $_POST['fc_date_time'] ?? '' ) );
        $capacity  = absint( $_POST['fc_date_capacity'] ?? 0 );

        $data = array(
            'course_id' => $course_id,
            'date'      => $date,
            'time'      => $time,
            'capacity'  => $capacity,
        );

        if ( $id > 0 ) {
            $wpdb->update( $this->dates_table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        } else {
            $wpdb->insert( $this->dates_table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }

    private function delete_course_date( $id ) {
        global $wpdb;
        $wpdb->delete( $this->dates_table, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    private function save_settings() {
        $success_page_id = absint( $_POST['fc_success_page_id'] ?? 0 );
        update_option( self::OPTION_SETTINGS, array(
            'success_page_id' => $success_page_id,
        ) );

        // Attendee types: one per line
        $raw_types      = sanitize_textarea_field( wp_unslash( $_POST['fc_attendee_types'] ?? '' ) );
        $attendee_types = array_filter( array_map( 'trim', explode( "\n", $raw_types ) ) );
        update_option( self::OPTION_ATTENDEE_TYPES, array_values( $attendee_types ) );
    }

    // -------------------------------------------------------------------------
    // Admin pages
    // -------------------------------------------------------------------------

    public function enqueue_admin_scripts( $hook ) {
        $fc_pages = array(
            'toplevel_page_fc-courses-courses',
            'fc-courses_page_fc-courses-course-dates',
            'fc-courses_page_fc-courses-settings',
        );
        if ( ! in_array( $hook, $fc_pages, true ) ) {
            return;
        }

        wp_add_inline_style( 'wp-admin', $this->admin_inline_css() );
        wp_add_inline_script( 'jquery', $this->admin_inline_js() );
    }

    private function admin_inline_css() {
        return '
        .fc-toggle-form { display:none; }
        .fc-add-new-btn { margin-bottom:12px; }
        .fc-courses-table th, .fc-courses-table td { padding: 8px 12px; }
        .fc-courses-table { border-collapse: collapse; width: 100%; }
        .fc-courses-table th { background: #f0f0f1; }
        .fc-form-box { background: #fff; border: 1px solid #c3c4c7; padding: 20px; margin-bottom: 20px; max-width: 700px; }
        .fc-form-box h3 { margin-top:0; }
        ';
    }

    private function admin_inline_js() {
        return '
        jQuery(function($){
            $(".fc-add-new-btn").on("click", function(e){
                e.preventDefault();
                var target = $(this).data("target");
                $(target).slideToggle(200);
            });
        });
        ';
    }

    /**
     * Page: Manage Courses
     */
    public function page_courses() {
        global $wpdb;

        $courses = $wpdb->get_results( "SELECT * FROM {$this->courses_table} ORDER BY id DESC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // Determine edit mode
        $editing  = null;
        $edit_id  = absint( $_GET['fc_edit_course'] ?? 0 );
        if ( $edit_id > 0 ) {
            $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->courses_table} WHERE id = %d", $edit_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        $deleted = isset( $_GET['deleted'] );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'FC Courses', 'ielts-course-manager' ); ?></h1>

            <?php if ( $deleted ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Course deleted.', 'ielts-course-manager' ); ?></p></div>
            <?php endif; ?>

            <a href="#" class="button button-primary fc-add-new-btn" data-target="#fc-add-course-form">
                <?php esc_html_e( '+ Add New Course', 'ielts-course-manager' ); ?>
            </a>

            <div id="fc-add-course-form" class="fc-toggle-form fc-form-box" <?php echo ( $editing ? 'style="display:block"' : '' ); ?>>
                <h3><?php echo $editing ? esc_html__( 'Edit Course', 'ielts-course-manager' ) : esc_html__( 'Add New Course', 'ielts-course-manager' ); ?></h3>
                <form method="post">
                    <?php wp_nonce_field( self::NONCE_SAVE_COURSE ); ?>
                    <input type="hidden" name="fc_course_id" value="<?php echo $editing ? esc_attr( $editing->id ) : '0'; ?>">

                    <table class="form-table">
                        <tr>
                            <th><label for="fc_course_name"><?php esc_html_e( 'Course Name', 'ielts-course-manager' ); ?></label></th>
                            <td><input type="text" id="fc_course_name" name="fc_course_name" class="regular-text" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="fc_course_description"><?php esc_html_e( 'Description', 'ielts-course-manager' ); ?></label></th>
                            <td><textarea id="fc_course_description" name="fc_course_description" rows="4" class="large-text"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="fc_course_location"><?php esc_html_e( 'Location', 'ielts-course-manager' ); ?></label></th>
                            <td><input type="text" id="fc_course_location" name="fc_course_location" class="regular-text" value="<?php echo $editing ? esc_attr( $editing->location ) : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="fc_course_price"><?php esc_html_e( 'Price (NZ$)', 'ielts-course-manager' ); ?></label></th>
                            <td><input type="number" id="fc_course_price" name="fc_course_price" step="0.01" min="0" class="small-text" value="<?php echo $editing ? esc_attr( $editing->price ) : '0'; ?>"></td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" name="fc_save_course" class="button button-primary">
                            <?php echo $editing ? esc_html__( 'Update Course', 'ielts-course-manager' ) : esc_html__( 'Save Course', 'ielts-course-manager' ); ?>
                        </button>
                        <?php if ( $editing ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=fc-courses-courses' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'ielts-course-manager' ); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <?php if ( ! empty( $courses ) ) : ?>
                <table class="wp-list-table widefat fixed striped fc-courses-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'ID', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Name', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Location', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Price', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'ielts-course-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $courses as $course ) : ?>
                            <tr>
                                <td><?php echo esc_html( $course->id ); ?></td>
                                <td><?php echo esc_html( $course->name ); ?></td>
                                <td><?php echo esc_html( $course->location ); ?></td>
                                <td>NZ$<?php echo esc_html( number_format( (float) $course->price, 2 ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=fc-courses-courses&fc_edit_course=' . $course->id ) ); ?>">
                                        <?php esc_html_e( 'Edit', 'ielts-course-manager' ); ?>
                                    </a>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=fc-courses-courses&fc_delete_course=' . $course->id ), 'fc_delete_course_' . $course->id ) ); ?>"
                                       onclick="return confirm('<?php esc_attr_e( 'Delete this course and all its dates?', 'ielts-course-manager' ); ?>');">
                                        <?php esc_html_e( 'Delete', 'ielts-course-manager' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No courses found. Click "Add New Course" to create one.', 'ielts-course-manager' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Page: Manage Course Dates
     */
    public function page_course_dates() {
        global $wpdb;

        $dates = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT d.*, c.name AS course_name
             FROM {$this->dates_table} d
             LEFT JOIN {$this->courses_table} c ON c.id = d.course_id
             ORDER BY d.date DESC"
        );

        $courses = $wpdb->get_results( "SELECT id, name FROM {$this->courses_table} ORDER BY name ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $editing = null;
        $edit_id = absint( $_GET['fc_edit_date'] ?? 0 );
        if ( $edit_id > 0 ) {
            $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->dates_table} WHERE id = %d", $edit_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        $deleted = isset( $_GET['deleted'] );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Course Dates', 'ielts-course-manager' ); ?></h1>

            <?php if ( $deleted ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Date deleted.', 'ielts-course-manager' ); ?></p></div>
            <?php endif; ?>

            <a href="#" class="button button-primary fc-add-new-btn" data-target="#fc-add-date-form">
                <?php esc_html_e( '+ Add New Date', 'ielts-course-manager' ); ?>
            </a>

            <div id="fc-add-date-form" class="fc-toggle-form fc-form-box" <?php echo ( $editing ? 'style="display:block"' : '' ); ?>>
                <h3><?php echo $editing ? esc_html__( 'Edit Date', 'ielts-course-manager' ) : esc_html__( 'Add New Date', 'ielts-course-manager' ); ?></h3>
                <form method="post">
                    <?php wp_nonce_field( self::NONCE_SAVE_DATE ); ?>
                    <input type="hidden" name="fc_date_id" value="<?php echo $editing ? esc_attr( $editing->id ) : '0'; ?>">

                    <table class="form-table">
                        <tr>
                            <th><label for="fc_date_course_id"><?php esc_html_e( 'Course', 'ielts-course-manager' ); ?></label></th>
                            <td>
                                <select id="fc_date_course_id" name="fc_date_course_id" required>
                                    <option value=""><?php esc_html_e( '— Select Course —', 'ielts-course-manager' ); ?></option>
                                    <?php foreach ( $courses as $c ) : ?>
                                        <option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $editing ? $editing->course_id : '', $c->id ); ?>>
                                            <?php echo esc_html( $c->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="fc_date_date"><?php esc_html_e( 'Date', 'ielts-course-manager' ); ?></label></th>
                            <td><input type="date" id="fc_date_date" name="fc_date_date" value="<?php echo $editing ? esc_attr( $editing->date ) : ''; ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="fc_date_time"><?php esc_html_e( 'Time', 'ielts-course-manager' ); ?></label></th>
                            <td><input type="text" id="fc_date_time" name="fc_date_time" class="regular-text" placeholder="e.g. 9:00am – 5:00pm" value="<?php echo $editing ? esc_attr( $editing->time ) : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="fc_date_capacity"><?php esc_html_e( 'Capacity', 'ielts-course-manager' ); ?></label></th>
                            <td><input type="number" id="fc_date_capacity" name="fc_date_capacity" min="0" class="small-text" value="<?php echo $editing ? esc_attr( $editing->capacity ) : '20'; ?>"></td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" name="fc_save_date" class="button button-primary">
                            <?php echo $editing ? esc_html__( 'Update Date', 'ielts-course-manager' ) : esc_html__( 'Save Date', 'ielts-course-manager' ); ?>
                        </button>
                        <?php if ( $editing ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=fc-courses-course-dates' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'ielts-course-manager' ); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <?php if ( ! empty( $dates ) ) : ?>
                <table class="wp-list-table widefat fixed striped fc-courses-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Course', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Time', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Capacity', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'ielts-course-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $dates as $date ) : ?>
                            <tr>
                                <td><?php echo esc_html( $date->course_name ); ?></td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date->date ) ) ); ?></td>
                                <td><?php echo esc_html( $date->time ); ?></td>
                                <td><?php echo esc_html( $date->capacity ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=fc-courses-course-dates&fc_edit_date=' . $date->id ) ); ?>">
                                        <?php esc_html_e( 'Edit', 'ielts-course-manager' ); ?>
                                    </a>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=fc-courses-course-dates&fc_delete_date=' . $date->id ), 'fc_delete_date_' . $date->id ) ); ?>"
                                       onclick="return confirm('<?php esc_attr_e( 'Delete this date?', 'ielts-course-manager' ); ?>');">
                                        <?php esc_html_e( 'Delete', 'ielts-course-manager' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No dates found. Click "Add New Date" to add one.', 'ielts-course-manager' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Page: Settings
     * Only "Payment success page" and configurable attendee types.
     */
    public function page_settings() {
        $settings       = get_option( self::OPTION_SETTINGS, array() );
        $success_page   = isset( $settings['success_page_id'] ) ? (int) $settings['success_page_id'] : 0;
        $attendee_types = get_option( self::OPTION_ATTENDEE_TYPES, array( 'Clinician / Professional', 'Whanau Member', 'Other' ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'FC Courses Settings', 'ielts-course-manager' ); ?></h1>
            <form method="post">
                <?php wp_nonce_field( self::NONCE_SETTINGS ); ?>

                <h2><?php esc_html_e( 'Payment', 'ielts-course-manager' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fc_success_page_id"><?php esc_html_e( 'Payment Success Page', 'ielts-course-manager' ); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages( array(
                                'name'              => 'fc_success_page_id',
                                'id'                => 'fc_success_page_id',
                                'selected'          => $success_page,
                                'show_option_none'  => __( '— Select Page —', 'ielts-course-manager' ),
                                'option_none_value' => '0',
                            ) );
                            ?>
                            <p class="description"><?php esc_html_e( 'Users will be redirected to this page after successful payment.', 'ielts-course-manager' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Registration Options', 'ielts-course-manager' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fc_attendee_types"><?php esc_html_e( 'Attendee Types', 'ielts-course-manager' ); ?></label>
                        </th>
                        <td>
                            <textarea id="fc_attendee_types" name="fc_attendee_types" rows="6" class="large-text"><?php echo esc_textarea( implode( "\n", $attendee_types ) ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'One option per line. These appear in the attendee type dropdown on the registration form.', 'ielts-course-manager' ); ?></p>
                        </td>
                    </tr>
                </table>

                <p><button type="submit" name="fc_save_settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'ielts-course-manager' ); ?></button></p>
            </form>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Frontend scripts
    // -------------------------------------------------------------------------

    public function enqueue_frontend_scripts() {
        wp_add_inline_style( 'wp-block-library', $this->frontend_inline_css() );
    }

    private function frontend_inline_css() {
        return '
        .fc-calendar-wrap { margin: 20px 0; }
        .fc-course-header { margin-bottom: 16px; }
        .fc-dates-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .fc-dates-table th, .fc-dates-table td { padding: 10px 14px; border: 1px solid #ddd; text-align: left; }
        .fc-dates-table th { background: #f5f5f5; font-weight: 600; }
        .fc-register-btn { background: #0073aa; color: #fff; padding: 6px 14px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; }
        .fc-register-btn:hover { background: #005177; color: #fff; }
        .fc-reg-form-wrap { display: none; background: #f9f9f9; border: 1px solid #ddd; padding: 20px; margin-top: 10px; }
        .fc-reg-form-wrap.active { display: block; }
        .fc-form-row { margin-bottom: 14px; }
        .fc-form-row label { display: block; font-weight: 600; margin-bottom: 4px; }
        .fc-form-row input, .fc-form-row select { width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 3px; }
        .fc-form-submit { background: #0073aa; color: #fff; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; }
        .fc-form-submit:hover { background: #005177; }
        .fc-form-notice { padding: 10px; margin-top: 10px; border-radius: 3px; }
        .fc-form-notice.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .fc-form-notice.error   { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .fc-price { font-size: 1.1em; font-weight: 600; }
        ';
    }

    // -------------------------------------------------------------------------
    // Shortcode: [fc_course_calendar course_id="x"]
    // -------------------------------------------------------------------------

    public function shortcode_calendar( $atts ) {
        $atts = shortcode_atts( array( 'course_id' => 0 ), $atts, 'fc_course_calendar' );
        $course_id = absint( $atts['course_id'] );

        if ( ! $course_id ) {
            return '<p>' . esc_html__( 'Please specify a valid course_id.', 'ielts-course-manager' ) . '</p>';
        }

        global $wpdb;

        // Fetch course
        $course = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->courses_table} WHERE id = %d", $course_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ( ! $course ) {
            return '<p>' . esc_html__( 'Course not found.', 'ielts-course-manager' ) . '</p>';
        }

        // Fetch upcoming dates
        $dates = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT * FROM {$this->dates_table} WHERE course_id = %d AND date >= CURDATE() ORDER BY date ASC",
            $course_id
        ) );

        // Get attendee types from settings
        $attendee_types = get_option( self::OPTION_ATTENDEE_TYPES, array( 'Clinician / Professional', 'Whanau Member', 'Other' ) );

        ob_start();
        ?>
        <div class="fc-calendar-wrap" id="fc-calendar-<?php echo esc_attr( $course_id ); ?>">
            <div class="fc-course-header">
                <h2><?php echo esc_html( $course->name ); ?></h2>
                <?php if ( $course->description ) : ?>
                    <div class="fc-course-description"><?php echo wp_kses_post( $course->description ); ?></div>
                <?php endif; ?>
                <?php if ( $course->location ) : ?>
                    <p><strong><?php esc_html_e( 'Location:', 'ielts-course-manager' ); ?></strong> <?php echo esc_html( $course->location ); ?></p>
                <?php endif; ?>
                <p class="fc-price"><strong><?php esc_html_e( 'Price:', 'ielts-course-manager' ); ?></strong> NZ$<?php echo esc_html( number_format( (float) $course->price, 2 ) ); ?></p>
            </div>

            <?php if ( empty( $dates ) ) : ?>
                <p><?php esc_html_e( 'No upcoming dates available. Please check back later.', 'ielts-course-manager' ); ?></p>
            <?php else : ?>
                <table class="fc-dates-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Time', 'ielts-course-manager' ); ?></th>
                            <th><?php esc_html_e( 'Register', 'ielts-course-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $dates as $date ) : ?>
                            <tr>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date->date ) ) ); ?></td>
                                <td><?php echo esc_html( $date->time ); ?></td>
                                <td>
                                    <a href="#" class="fc-register-btn"
                                       data-date-id="<?php echo esc_attr( $date->id ); ?>"
                                       data-date-label="<?php echo esc_attr( date_i18n( get_option( 'date_format' ), strtotime( $date->date ) ) . ( $date->time ? ' ' . $date->time : '' ) ); ?>"
                                       data-course-name="<?php echo esc_attr( $course->name ); ?>"
                                       data-price="<?php echo esc_attr( number_format( (float) $course->price, 2 ) ); ?>">
                                        <?php esc_html_e( 'Register', 'ielts-course-manager' ); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr class="fc-form-row-container" style="display:none;">
                                <td colspan="3">
                                    <div class="fc-reg-form-wrap" id="fc-form-<?php echo esc_attr( $date->id ); ?>">
                                        <h3>
                                            <?php
                                            echo esc_html( sprintf(
                                                /* translators: 1: course name, 2: date/time label */
                                                __( 'Register for %1$s – %2$s', 'ielts-course-manager' ),
                                                $course->name,
                                                date_i18n( get_option( 'date_format' ), strtotime( $date->date ) ) . ( $date->time ? ' ' . $date->time : '' )
                                            ) );
                                            ?>
                                        </h3>
                                        <form class="fc-registration-form" data-date-id="<?php echo esc_attr( $date->id ); ?>">
                                            <?php wp_nonce_field( self::NONCE_REGISTER, 'fc_reg_nonce_' . $date->id ); ?>

                                            <div class="fc-form-row">
                                                <label for="fc_first_name_<?php echo esc_attr( $date->id ); ?>"><?php esc_html_e( 'First Name', 'ielts-course-manager' ); ?> <span aria-hidden="true">*</span></label>
                                                <input type="text" id="fc_first_name_<?php echo esc_attr( $date->id ); ?>" name="fc_first_name" aria-required="true" required>
                                            </div>

                                            <div class="fc-form-row">
                                                <label for="fc_last_name_<?php echo esc_attr( $date->id ); ?>"><?php esc_html_e( 'Last Name', 'ielts-course-manager' ); ?> <span aria-hidden="true">*</span></label>
                                                <input type="text" id="fc_last_name_<?php echo esc_attr( $date->id ); ?>" name="fc_last_name" aria-required="true" required>
                                            </div>

                                            <div class="fc-form-row">
                                                <label for="fc_email_<?php echo esc_attr( $date->id ); ?>"><?php esc_html_e( 'Email Address', 'ielts-course-manager' ); ?> <span aria-hidden="true">*</span></label>
                                                <input type="email" id="fc_email_<?php echo esc_attr( $date->id ); ?>" name="fc_email" aria-required="true" required>
                                            </div>

                                            <div class="fc-form-row">
                                                <label for="fc_phone_<?php echo esc_attr( $date->id ); ?>"><?php esc_html_e( 'Phone Number', 'ielts-course-manager' ); ?></label>
                                                <input type="tel" id="fc_phone_<?php echo esc_attr( $date->id ); ?>" name="fc_phone">
                                            </div>

                                            <?php if ( ! empty( $attendee_types ) ) : ?>
                                            <div class="fc-form-row">
                                                <label for="fc_attendee_type_<?php echo esc_attr( $date->id ); ?>"><?php esc_html_e( 'I am a', 'ielts-course-manager' ); ?> <span aria-hidden="true">*</span></label>
                                                <select id="fc_attendee_type_<?php echo esc_attr( $date->id ); ?>" name="fc_attendee_type" aria-required="true" required>
                                                    <option value=""><?php esc_html_e( '— Please select —', 'ielts-course-manager' ); ?></option>
                                                    <?php foreach ( $attendee_types as $type ) : ?>
                                                        <option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type ); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <?php endif; ?>

                                            <p class="fc-price-line"><?php esc_html_e( 'Amount due:', 'ielts-course-manager' ); ?> <strong>NZ$<?php echo esc_html( number_format( (float) $course->price, 2 ) ); ?></strong></p>

                                            <button type="submit" class="fc-form-submit">
                                                <?php esc_html_e( 'Register & Pay', 'ielts-course-manager' ); ?>
                                            </button>
                                            <div class="fc-form-response" style="margin-top:10px;"></div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        $this->print_frontend_script();

        return ob_get_clean();
    }

    /**
     * Print the JS for the shortcode only once per page load.
     */
    private static $script_printed = false;

    private function print_frontend_script() {
        if ( self::$script_printed ) {
            return;
        }
        self::$script_printed = true;
        ?>
        <script>
        (function($){
            $(document).on('click', '.fc-register-btn', function(e){
                e.preventDefault();
                var $row  = $(this).closest('tr');
                var $next = $row.next('.fc-form-row-container');

                // Close any other open forms
                $('.fc-form-row-container').not($next).hide();
                $('.fc-reg-form-wrap').not($next.find('.fc-reg-form-wrap')).removeClass('active');

                // Toggle this form
                if ( $next.is(':hidden') ) {
                    $next.show();
                    $next.find('.fc-reg-form-wrap').addClass('active').attr('tabindex', '-1').focus();
                } else {
                    $next.hide();
                    $next.find('.fc-reg-form-wrap').removeClass('active');
                }
            });

            $(document).on('submit', '.fc-registration-form', function(e){
                e.preventDefault();
                var $form    = $(this);
                var dateId   = $form.data('date-id');
                var $resp    = $form.find('.fc-form-response');
                var nonce    = $form.find('[name="fc_reg_nonce_' + dateId + '"]').val();
                var $submit  = $form.find('.fc-form-submit');

                $resp.html('');
                $submit.prop('disabled', true).text('<?php echo esc_js( __( 'Submitting…', 'ielts-course-manager' ) ); ?>');

                $.ajax({
                    url:  '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                    type: 'POST',
                    data: {
                        action:         'fc_submit_registration',
                        fc_date_id:     dateId,
                        fc_first_name:  $form.find('[name="fc_first_name"]').val(),
                        fc_last_name:   $form.find('[name="fc_last_name"]').val(),
                        fc_email:       $form.find('[name="fc_email"]').val(),
                        fc_phone:       $form.find('[name="fc_phone"]').val(),
                        fc_attendee_type: $form.find('[name="fc_attendee_type"]').val(),
                        nonce:          nonce
                    },
                    success: function(res){
                        $submit.prop('disabled', false).text('<?php echo esc_js( __( 'Register & Pay', 'ielts-course-manager' ) ); ?>');
                        if ( res.success ) {
                            $resp.html('<div class="fc-form-notice success">' + res.data.message + '</div>');
                            $form[0].reset();
                            if ( res.data.redirect ) {
                                window.location.href = res.data.redirect;
                            }
                        } else {
                            $resp.html('<div class="fc-form-notice error">' + res.data.message + '</div>');
                        }
                    },
                    error: function(){
                        $submit.prop('disabled', false).text('<?php echo esc_js( __( 'Register & Pay', 'ielts-course-manager' ) ); ?>');
                        $resp.html('<div class="fc-form-notice error"><?php echo esc_js( __( 'An error occurred. Please try again.', 'ielts-course-manager' ) ); ?></div>');
                    }
                });
            });
        }(jQuery));
        </script>
        <?php
    }

    // -------------------------------------------------------------------------
    // AJAX: submit registration
    // -------------------------------------------------------------------------

    public function ajax_submit_registration() {
        // Verify nonce
        $date_id = absint( $_POST['fc_date_id'] ?? 0 );
        $nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );

        if ( ! wp_verify_nonce( $nonce, self::NONCE_REGISTER ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ielts-course-manager' ) ) );
        }

        if ( ! $date_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid course date.', 'ielts-course-manager' ) ) );
        }

        $first_name    = sanitize_text_field( wp_unslash( $_POST['fc_first_name'] ?? '' ) );
        $last_name     = sanitize_text_field( wp_unslash( $_POST['fc_last_name'] ?? '' ) );
        $email         = sanitize_email( wp_unslash( $_POST['fc_email'] ?? '' ) );
        $phone         = sanitize_text_field( wp_unslash( $_POST['fc_phone'] ?? '' ) );
        $attendee_type = sanitize_text_field( wp_unslash( $_POST['fc_attendee_type'] ?? '' ) );

        if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'ielts-course-manager' ) ) );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'ielts-course-manager' ) ) );
        }

        // Validate attendee type against allowed options
        $allowed_types = get_option( self::OPTION_ATTENDEE_TYPES, array( 'Clinician / Professional', 'Whanau Member', 'Other' ) );
        if ( ! empty( $attendee_type ) && ! in_array( $attendee_type, $allowed_types, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid attendee type selected.', 'ielts-course-manager' ) ) );
        }

        global $wpdb;

        // Verify the date exists
        $date_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->dates_table} WHERE id = %d", $date_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ( ! $date_row ) {
            wp_send_json_error( array( 'message' => __( 'Course date not found.', 'ielts-course-manager' ) ) );
        }

        // Insert registration
        $inserted = $wpdb->insert( $this->registrations_table, array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            'course_date_id' => $date_id,
            'first_name'     => $first_name,
            'last_name'      => $last_name,
            'email'          => $email,
            'phone'          => $phone,
            'attendee_type'  => $attendee_type,
            'payment_status' => 'pending',
        ) );

        if ( ! $inserted ) {
            wp_send_json_error( array( 'message' => __( 'Registration could not be saved. Please try again.', 'ielts-course-manager' ) ) );
        }

        $registration_id = $wpdb->insert_id;

        // Build success response
        $settings      = get_option( self::OPTION_SETTINGS, array() );
        $redirect_url  = '';
        if ( ! empty( $settings['success_page_id'] ) ) {
            $redirect_url = get_permalink( (int) $settings['success_page_id'] );
            if ( $redirect_url ) {
                $redirect_url = add_query_arg( 'fc_reg', $registration_id, $redirect_url );
            }
        }

        wp_send_json_success( array(
            'message'  => __( 'Thank you! Your registration has been received. You will receive a confirmation email shortly.', 'ielts-course-manager' ),
            'redirect' => ( $redirect_url ? $redirect_url : '' ),
        ) );
    }
}
