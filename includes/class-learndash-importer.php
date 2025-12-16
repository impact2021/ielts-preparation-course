<?php
/**
 * LearnDash to IELTS Course Manager Importer
 * 
 * Handles importing LearnDash XML exports into IELTS Course Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_LearnDash_Importer {
    
    /**
     * Imported items tracking
     */
    private $imported_courses = array();
    private $imported_lessons = array();
    private $imported_topics = array();
    private $imported_quizzes = array();
    private $import_log = array();
    
    /**
     * LearnDash to IELTS CM post type mapping
     */
    private $post_type_map = array(
        'sfwd-courses' => 'ielts_course',
        'sfwd-lessons' => 'ielts_lesson',
        'sfwd-topic' => 'ielts_resource',
        'sfwd-quiz' => 'ielts_quiz'
    );
    
    /**
     * Import XML file
     * 
     * @param string $file_path Path to XML file
     * @param array $options Import options
     * @return array Import results
     */
    public function import_xml($file_path, $options = array()) {
        $this->import_log = array();
        $this->log('Starting LearnDash import from: ' . basename($file_path));
        
        // Validate file exists
        if (!file_exists($file_path)) {
            $this->log('Error: File not found', 'error');
            return $this->get_results();
        }
        
        // Pre-process XML to fix common issues
        $xml_content = $this->preprocess_xml($file_path);
        if ($xml_content === false) {
            $this->log('Error: Unable to read XML file', 'error');
            return $this->get_results();
        }
        
        // Load XML with error handling
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $this->log('XML Error: ' . trim($error->message), 'error');
            }
            libxml_clear_errors();
            return $this->get_results();
        }
        
        // Register namespaces
        $namespaces = $xml->getNamespaces(true);
        
        // Process items
        $items = $xml->channel->item;
        $total_items = count($items);
        $this->log("Found {$total_items} items to process");
        
        // Process in two passes to handle dependencies
        // Pass 1: Import courses, lessons, topics (lesson pages), quizzes
        foreach ($items as $item) {
            $this->process_item($item, $namespaces, $options);
        }
        
        // Pass 2: Update relationships and metadata
        $this->update_relationships();
        
        $this->log('Import completed successfully');
        return $this->get_results();
    }
    
    /**
     * Pre-process XML content to fix common issues
     * 
     * @param string $file_path Path to XML file
     * @return string|false Processed XML content or false on failure
     */
    private function preprocess_xml($file_path) {
        // Read file content
        $content = file_get_contents($file_path);
        if ($content === false) {
            return false;
        }
        
        // Define HTML entities that are not valid in XML
        // These need to be converted to their numeric equivalents
        $html_entities = array(
            '&nbsp;'     => '&#160;',
            '&iexcl;'    => '&#161;',
            '&cent;'     => '&#162;',
            '&pound;'    => '&#163;',
            '&curren;'   => '&#164;',
            '&yen;'      => '&#165;',
            '&brvbar;'   => '&#166;',
            '&sect;'     => '&#167;',
            '&uml;'      => '&#168;',
            '&copy;'     => '&#169;',
            '&ordf;'     => '&#170;',
            '&laquo;'    => '&#171;',
            '&not;'      => '&#172;',
            '&shy;'      => '&#173;',
            '&reg;'      => '&#174;',
            '&macr;'     => '&#175;',
            '&deg;'      => '&#176;',
            '&plusmn;'   => '&#177;',
            '&sup2;'     => '&#178;',
            '&sup3;'     => '&#179;',
            '&acute;'    => '&#180;',
            '&micro;'    => '&#181;',
            '&para;'     => '&#182;',
            '&middot;'   => '&#183;',
            '&cedil;'    => '&#184;',
            '&sup1;'     => '&#185;',
            '&ordm;'     => '&#186;',
            '&raquo;'    => '&#187;',
            '&frac14;'   => '&#188;',
            '&frac12;'   => '&#189;',
            '&frac34;'   => '&#190;',
            '&iquest;'   => '&#191;',
            '&times;'    => '&#215;',
            '&divide;'   => '&#247;',
            '&Agrave;'   => '&#192;',
            '&Aacute;'   => '&#193;',
            '&Acirc;'    => '&#194;',
            '&Atilde;'   => '&#195;',
            '&Auml;'     => '&#196;',
            '&Aring;'    => '&#197;',
            '&AElig;'    => '&#198;',
            '&Ccedil;'   => '&#199;',
            '&Egrave;'   => '&#200;',
            '&Eacute;'   => '&#201;',
            '&Ecirc;'    => '&#202;',
            '&Euml;'     => '&#203;',
            '&Igrave;'   => '&#204;',
            '&Iacute;'   => '&#205;',
            '&Icirc;'    => '&#206;',
            '&Iuml;'     => '&#207;',
            '&ETH;'      => '&#208;',
            '&Ntilde;'   => '&#209;',
            '&Ograve;'   => '&#210;',
            '&Oacute;'   => '&#211;',
            '&Ocirc;'    => '&#212;',
            '&Otilde;'   => '&#213;',
            '&Ouml;'     => '&#214;',
            '&Oslash;'   => '&#216;',
            '&Ugrave;'   => '&#217;',
            '&Uacute;'   => '&#218;',
            '&Ucirc;'    => '&#219;',
            '&Uuml;'     => '&#220;',
            '&Yacute;'   => '&#221;',
            '&THORN;'    => '&#222;',
            '&szlig;'    => '&#223;',
            '&agrave;'   => '&#224;',
            '&aacute;'   => '&#225;',
            '&acirc;'    => '&#226;',
            '&atilde;'   => '&#227;',
            '&auml;'     => '&#228;',
            '&aring;'    => '&#229;',
            '&aelig;'    => '&#230;',
            '&ccedil;'   => '&#231;',
            '&egrave;'   => '&#232;',
            '&eacute;'   => '&#233;',
            '&ecirc;'    => '&#234;',
            '&euml;'     => '&#235;',
            '&igrave;'   => '&#236;',
            '&iacute;'   => '&#237;',
            '&icirc;'    => '&#238;',
            '&iuml;'     => '&#239;',
            '&eth;'      => '&#240;',
            '&ntilde;'   => '&#241;',
            '&ograve;'   => '&#242;',
            '&oacute;'   => '&#243;',
            '&ocirc;'    => '&#244;',
            '&otilde;'   => '&#245;',
            '&ouml;'     => '&#246;',
            '&oslash;'   => '&#248;',
            '&ugrave;'   => '&#249;',
            '&uacute;'   => '&#250;',
            '&ucirc;'    => '&#251;',
            '&uuml;'     => '&#252;',
            '&yacute;'   => '&#253;',
            '&thorn;'    => '&#254;',
            '&yuml;'     => '&#255;',
            '&fnof;'     => '&#402;',
            '&Alpha;'    => '&#913;',
            '&Beta;'     => '&#914;',
            '&Gamma;'    => '&#915;',
            '&Delta;'    => '&#916;',
            '&Epsilon;'  => '&#917;',
            '&Zeta;'     => '&#918;',
            '&Eta;'      => '&#919;',
            '&Theta;'    => '&#920;',
            '&Iota;'     => '&#921;',
            '&Kappa;'    => '&#922;',
            '&Lambda;'   => '&#923;',
            '&Mu;'       => '&#924;',
            '&Nu;'       => '&#925;',
            '&Xi;'       => '&#926;',
            '&Omicron;'  => '&#927;',
            '&Pi;'       => '&#928;',
            '&Rho;'      => '&#929;',
            '&Sigma;'    => '&#931;',
            '&Tau;'      => '&#932;',
            '&Upsilon;'  => '&#933;',
            '&Phi;'      => '&#934;',
            '&Chi;'      => '&#935;',
            '&Psi;'      => '&#936;',
            '&Omega;'    => '&#937;',
            '&alpha;'    => '&#945;',
            '&beta;'     => '&#946;',
            '&gamma;'    => '&#947;',
            '&delta;'    => '&#948;',
            '&epsilon;'  => '&#949;',
            '&zeta;'     => '&#950;',
            '&eta;'      => '&#951;',
            '&theta;'    => '&#952;',
            '&iota;'     => '&#953;',
            '&kappa;'    => '&#954;',
            '&lambda;'   => '&#955;',
            '&mu;'       => '&#956;',
            '&nu;'       => '&#957;',
            '&xi;'       => '&#958;',
            '&omicron;'  => '&#959;',
            '&pi;'       => '&#960;',
            '&rho;'      => '&#961;',
            '&sigmaf;'   => '&#962;',
            '&sigma;'    => '&#963;',
            '&tau;'      => '&#964;',
            '&upsilon;'  => '&#965;',
            '&phi;'      => '&#966;',
            '&chi;'      => '&#967;',
            '&psi;'      => '&#968;',
            '&omega;'    => '&#969;',
            '&thetasym;' => '&#977;',
            '&upsih;'    => '&#978;',
            '&piv;'      => '&#982;',
            '&bull;'     => '&#8226;',
            '&hellip;'   => '&#8230;',
            '&prime;'    => '&#8242;',
            '&Prime;'    => '&#8243;',
            '&oline;'    => '&#8254;',
            '&frasl;'    => '&#8260;',
            '&weierp;'   => '&#8472;',
            '&image;'    => '&#8465;',
            '&real;'     => '&#8476;',
            '&trade;'    => '&#8482;',
            '&alefsym;'  => '&#8501;',
            '&larr;'     => '&#8592;',
            '&uarr;'     => '&#8593;',
            '&rarr;'     => '&#8594;',
            '&darr;'     => '&#8595;',
            '&harr;'     => '&#8596;',
            '&crarr;'    => '&#8629;',
            '&lArr;'     => '&#8656;',
            '&uArr;'     => '&#8657;',
            '&rArr;'     => '&#8658;',
            '&dArr;'     => '&#8659;',
            '&hArr;'     => '&#8660;',
            '&forall;'   => '&#8704;',
            '&part;'     => '&#8706;',
            '&exist;'    => '&#8707;',
            '&empty;'    => '&#8709;',
            '&nabla;'    => '&#8711;',
            '&isin;'     => '&#8712;',
            '&notin;'    => '&#8713;',
            '&ni;'       => '&#8715;',
            '&prod;'     => '&#8719;',
            '&sum;'      => '&#8721;',
            '&minus;'    => '&#8722;',
            '&lowast;'   => '&#8727;',
            '&radic;'    => '&#8730;',
            '&prop;'     => '&#8733;',
            '&infin;'    => '&#8734;',
            '&ang;'      => '&#8736;',
            '&and;'      => '&#8743;',
            '&or;'       => '&#8744;',
            '&cap;'      => '&#8745;',
            '&cup;'      => '&#8746;',
            '&int;'      => '&#8747;',
            '&there4;'   => '&#8756;',
            '&sim;'      => '&#8764;',
            '&cong;'     => '&#8773;',
            '&asymp;'    => '&#8776;',
            '&ne;'       => '&#8800;',
            '&equiv;'    => '&#8801;',
            '&le;'       => '&#8804;',
            '&ge;'       => '&#8805;',
            '&sub;'      => '&#8834;',
            '&sup;'      => '&#8835;',
            '&nsub;'     => '&#8836;',
            '&sube;'     => '&#8838;',
            '&supe;'     => '&#8839;',
            '&oplus;'    => '&#8853;',
            '&otimes;'   => '&#8855;',
            '&perp;'     => '&#8869;',
            '&sdot;'     => '&#8901;',
            '&lceil;'    => '&#8968;',
            '&rceil;'    => '&#8969;',
            '&lfloor;'   => '&#8970;',
            '&rfloor;'   => '&#8971;',
            '&lang;'     => '&#9001;',
            '&rang;'     => '&#9002;',
            '&loz;'      => '&#9674;',
            '&spades;'   => '&#9824;',
            '&clubs;'    => '&#9827;',
            '&hearts;'   => '&#9829;',
            '&diams;'    => '&#9830;',
            '&OElig;'    => '&#338;',
            '&oelig;'    => '&#339;',
            '&Scaron;'   => '&#352;',
            '&scaron;'   => '&#353;',
            '&Yuml;'     => '&#376;',
            '&circ;'     => '&#710;',
            '&tilde;'    => '&#732;',
            '&ensp;'     => '&#8194;',
            '&emsp;'     => '&#8195;',
            '&thinsp;'   => '&#8201;',
            '&zwnj;'     => '&#8204;',
            '&zwj;'      => '&#8205;',
            '&lrm;'      => '&#8206;',
            '&rlm;'      => '&#8207;',
            '&ndash;'    => '&#8211;',
            '&mdash;'    => '&#8212;',
            '&lsquo;'    => '&#8216;',
            '&rsquo;'    => '&#8217;',
            '&sbquo;'    => '&#8218;',
            '&ldquo;'    => '&#8220;',
            '&rdquo;'    => '&#8221;',
            '&bdquo;'    => '&#8222;',
            '&dagger;'   => '&#8224;',
            '&Dagger;'   => '&#8225;',
            '&permil;'   => '&#8240;',
            '&lsaquo;'   => '&#8249;',
            '&rsaquo;'   => '&#8250;',
            '&euro;'     => '&#8364;',
        );
        
        // Replace HTML entities with numeric equivalents
        // Only replace entities that are not already inside CDATA sections
        $content = preg_replace_callback(
            '/<!\[CDATA\[(.*?)\]\]>/s',
            function($matches) {
                // Mark CDATA sections to protect them
                return '___CDATA_START___' . base64_encode($matches[1]) . '___CDATA_END___';
            },
            $content
        );
        
        // Now replace entities outside CDATA
        $content = str_replace(array_keys($html_entities), array_values($html_entities), $content);
        
        // Restore CDATA sections
        $content = preg_replace_callback(
            '/___CDATA_START___(.*?)___CDATA_END___/s',
            function($matches) {
                return '<![CDATA[' . base64_decode($matches[1]) . ']]>';
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Process a single item from XML
     */
    private function process_item($item, $namespaces, $options) {
        $post_type = (string)$item->children($namespaces['wp'])->post_type;
        
        // Skip if not a LearnDash post type
        if (!isset($this->post_type_map[$post_type])) {
            return;
        }
        
        $old_id = (int)$item->children($namespaces['wp'])->post_id;
        $title = (string)$item->title;
        $content = (string)$item->children($namespaces['content'])->encoded;
        $post_status = (string)$item->children($namespaces['wp'])->status;
        
        // Map to new post type
        $new_post_type = $this->post_type_map[$post_type];
        
        $this->log("Processing {$post_type} (ID: {$old_id}): {$title}");
        
        // Create the post
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $post_status === 'publish' ? 'publish' : 'draft',
            'post_type' => $new_post_type,
            'post_date' => (string)$item->children($namespaces['wp'])->post_date
        );
        
        // Check if already exists (by title)
        if (!empty($options['skip_duplicates'])) {
            global $wpdb;
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_status != 'trash' LIMIT 1",
                $title,
                $new_post_type
            ));
            if ($existing_id) {
                $this->log("Skipping duplicate: {$title}", 'warning');
                return;
            }
        }
        
        $new_id = wp_insert_post($post_data);
        
        if (is_wp_error($new_id)) {
            $this->log("Error creating post: " . $new_id->get_error_message(), 'error');
            return;
        }
        
        $this->log("Created {$new_post_type} with ID: {$new_id}");
        
        // Store mapping
        switch ($post_type) {
            case 'sfwd-courses':
                $this->imported_courses[$old_id] = $new_id;
                break;
            case 'sfwd-lessons':
                $this->imported_lessons[$old_id] = $new_id;
                break;
            case 'sfwd-topic':
                $this->imported_topics[$old_id] = $new_id;
                break;
            case 'sfwd-quiz':
                $this->imported_quizzes[$old_id] = $new_id;
                break;
        }
        
        // Process metadata
        $this->process_postmeta($item, $namespaces, $old_id, $new_id, $post_type);
        
        // Process taxonomies
        $this->process_taxonomies($item, $new_id);
    }
    
    /**
     * Process post meta data
     */
    private function process_postmeta($item, $namespaces, $old_id, $new_id, $post_type) {
        foreach ($item->children($namespaces['wp'])->postmeta as $meta) {
            $key = (string)$meta->meta_key;
            $value = (string)$meta->meta_value;
            
            // Skip internal WordPress meta
            if (strpos($key, '_edit_') === 0 || strpos($key, '_wp_') === 0) {
                continue;
            }
            
            // Map LearnDash meta keys to IELTS CM meta keys
            $mapped_key = $this->map_meta_key($key, $post_type);
            
            if ($mapped_key) {
                // Unserialize if needed (using WordPress built-in function)
                $value = maybe_unserialize($value);
                update_post_meta($new_id, $mapped_key, $value);
            }
            
            // Store original meta with prefix for reference
            update_post_meta($new_id, '_ld_original_' . $key, $value);
        }
        
        // Store original LearnDash ID for relationship mapping
        update_post_meta($new_id, '_ld_original_id', $old_id);
    }
    
    /**
     * Process taxonomies
     */
    private function process_taxonomies($item, $new_id) {
        foreach ($item->category as $category) {
            $domain = (string)$category['domain'];
            $term_name = (string)$category;
            
            if ($domain === 'ld_course_category' || $domain === 'category') {
                // Map to course category
                wp_set_object_terms($new_id, $term_name, 'ielts_course_category', true);
            }
        }
    }
    
    /**
     * Update relationships between imported items
     */
    private function update_relationships() {
        $this->log('Updating relationships between imported items');
        
        // Link lessons to courses
        foreach ($this->imported_lessons as $old_lesson_id => $new_lesson_id) {
            $original_course_id = get_post_meta($new_lesson_id, '_ld_original_course_id', true);
            
            if ($original_course_id && isset($this->imported_courses[$original_course_id])) {
                $new_course_id = $this->imported_courses[$original_course_id];
                $course_ids = get_post_meta($new_lesson_id, '_ielts_cm_course_ids', true);
                if (!is_array($course_ids)) {
                    $course_ids = array();
                }
                $course_ids[] = $new_course_id;
                update_post_meta($new_lesson_id, '_ielts_cm_course_ids', array_unique($course_ids));
                update_post_meta($new_lesson_id, '_ielts_cm_course_id', $new_course_id);
            }
        }
        
        // Link topics (lesson pages) to lessons
        foreach ($this->imported_topics as $old_topic_id => $new_topic_id) {
            $original_lesson_id = get_post_meta($new_topic_id, '_ld_original_lesson_id', true);
            
            if ($original_lesson_id && isset($this->imported_lessons[$original_lesson_id])) {
                $new_lesson_id = $this->imported_lessons[$original_lesson_id];
                $lesson_ids = get_post_meta($new_topic_id, '_ielts_cm_lesson_ids', true);
                if (!is_array($lesson_ids)) {
                    $lesson_ids = array();
                }
                $lesson_ids[] = $new_lesson_id;
                update_post_meta($new_topic_id, '_ielts_cm_lesson_ids', array_unique($lesson_ids));
                update_post_meta($new_topic_id, '_ielts_cm_lesson_id', $new_lesson_id);
            }
        }
        
        // Link quizzes to courses and lessons
        foreach ($this->imported_quizzes as $old_quiz_id => $new_quiz_id) {
            // Link to course
            $original_course_id = get_post_meta($new_quiz_id, '_ld_original_course_id', true);
            if ($original_course_id && isset($this->imported_courses[$original_course_id])) {
                $new_course_id = $this->imported_courses[$original_course_id];
                $course_ids = get_post_meta($new_quiz_id, '_ielts_cm_course_ids', true);
                if (!is_array($course_ids)) {
                    $course_ids = array();
                }
                $course_ids[] = $new_course_id;
                update_post_meta($new_quiz_id, '_ielts_cm_course_ids', array_unique($course_ids));
                update_post_meta($new_quiz_id, '_ielts_cm_course_id', $new_course_id);
            }
            
            // Link to lesson
            $original_lesson_id = get_post_meta($new_quiz_id, '_ld_original_lesson_id', true);
            if ($original_lesson_id && isset($this->imported_lessons[$original_lesson_id])) {
                $new_lesson_id = $this->imported_lessons[$original_lesson_id];
                $lesson_ids = get_post_meta($new_quiz_id, '_ielts_cm_lesson_ids', true);
                if (!is_array($lesson_ids)) {
                    $lesson_ids = array();
                }
                $lesson_ids[] = $new_lesson_id;
                update_post_meta($new_quiz_id, '_ielts_cm_lesson_ids', array_unique($lesson_ids));
                update_post_meta($new_quiz_id, '_ielts_cm_lesson_id', $new_lesson_id);
            }
        }
    }
    
    /**
     * Map LearnDash meta keys to IELTS CM meta keys
     */
    private function map_meta_key($key, $post_type) {
        // Common mappings
        $mappings = array(
            // Course mappings
            'course_id' => '_ielts_cm_course_id',
            'ld_course_' => '_ld_original_course_id',
            
            // Lesson mappings
            'lesson_id' => '_ielts_cm_lesson_id',
            'course' => '_ld_original_course_id',
            
            // Quiz mappings
            'quiz_pass_percentage' => '_ielts_cm_pass_percentage',
        );
        
        foreach ($mappings as $old_key => $new_key) {
            if (strpos($key, $old_key) !== false) {
                return $new_key;
            }
        }
        
        return null;
    }
    
    /**
     * Log a message
     */
    private function log($message, $level = 'info') {
        $this->import_log[] = array(
            'message' => $message,
            'level' => $level,
            'time' => current_time('mysql')
        );
    }
    
    /**
     * Get import results
     */
    public function get_results() {
        return array(
            'success' => true,
            'courses' => count($this->imported_courses),
            'lessons' => count($this->imported_lessons),
            'topics' => count($this->imported_topics),
            'quizzes' => count($this->imported_quizzes),
            'log' => $this->import_log
        );
    }
    
    /**
     * Get import log
     */
    public function get_log() {
        return $this->import_log;
    }
}
