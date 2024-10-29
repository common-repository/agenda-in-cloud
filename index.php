<?php
/*
Plugin Name: Agenda in cloud
Plugin URI: https://www.agendaincloud.it
Description: Agenda in cloud shortcodes 
Version: 1.1
Author: Luca Fabbri 
Author URI: https://github.com/lucafabbri
License: GPLv2
*/
/* Agenda in cloud – Integrazione palinsesto corsi*/

if (!class_exists('AgendaInCloud')) {
    class AgendaInCloud
    {

        private $version = "1.1";

        private $shortcode_name = 'agendaincloud';

        public function register()
        {
            add_shortcode($this->shortcode_name, [$this, 'shortcode']);
            add_action('wp_enqueue_scripts', [$this, 'scripts']);
            add_action('admin_menu', [$this, 'admin']);
        }

        public function admin()
        {
            add_menu_page('Agenda in Cloud', 'Agenda in Cloud', 'manage_options', 'agenda-in-cloud', [$this, 'admin_init']);
        }

        public function admin_init()
        {
            $html = "<h1>Agenda in Cloud</h1>";
            $html .= "<p>Agenda in Cloud wordpress plugin v" . $this->version . "</p>";
            $html .= "<div><p>Hai installato correttamente il plugin di <b>Agenda in Cloud</b> per wordpress. <br>Collegati alla tua Agenda in Cloud per trovare le istruzioni di utilizzo e per creare i tuoi shortcodes da inserire nel sito.</p><p><a href='https://www.agendaincloud.it' target='_blank'>vai ad Agenda in Cloud</a></p></div>";

            echo $html;
        }

        public function shortcode($atts)
        {
            extract(shortcode_atts(array(
                "organization" => "agendaincloud",
                "mode" => "workweek",
                "day" => date('Y-m-d'),
                "booking" => "false",
                "showoperator" => "false",
                "showstructure" => "false"
            ), $atts));

            $response = wp_remote_get("https://api.agendaincloud.it/api/v2/agenda/courses/" . $atts['organization'] . "/" . $atts['mode'] . "/" . $atts['day']);
            $body = wp_remote_retrieve_body($response);

            $groups = json_decode($body);

            function cmp($a, $b)
            {
                return strcmp($a->from, $b->from);
            }

            $result = "<div class='agenda'>";
            foreach ($groups as $group => $bookings) {
                $result .= "<div><div class='agenda-header'>" . $group . "</div>";
                $sorted = usort($bookings, function ($a, $b) {
                    return strcmp($a->from, $b->from);
                });
                foreach ($bookings as $booking) {
                    if ($atts["booking"] === "true") {
                        $result .= "<a class='booking' style='background-color:" . $booking->color . ";' href='https://www.appuntamentincloud.it/store/" . $atts['organization'] . "' target='_blank'>";
                    } else {
                        $result .= "<div class='booking' style='background-color:" . $booking->color . ";'>";
                    }
                    $result .= "<div class='booking-title'>" . $booking->serviceName . "</div>";
                    $result .= "<div class='booking-time'>" . get_date_from_gmt($booking->from, 'H:i') . " - " . get_date_from_gmt($booking->to, 'H:i') . "</div>";
                    if ($atts["showoperator"] === "true") {
                        foreach ($booking->resources as $operator) {
                            if ($operator->discrimininator == 'Operator') {
                                $result .= "<div class='booking-operator'>" . $operator->name . "</div>";
                            }
                        }
                    }
                    if ($atts["showstructure"] === "true") {
                        foreach ($booking->resources as $structure) {
                            if ($operator->discrimininator == 'Structure') {
                                $result .= "<div class='booking-structure'>(" . $structure->name . ")</div>";
                            }
                        }
                    }
                    if ($atts["booking"] === "true") {
                        $result .= "</a>";
                    } else {
                        $result .= "</div>";
                    }
                }
                $result .= "</div>";
            }
            $result .= "</div>";
            $result .= "<div class='agenda-footer'><p>powered by <a href='https://www.agendaincloud.it' target='blank'>Agenda in Cloud</a></p></div>";

            return $result;
        }

        public function scripts()
        {
            global $post;
            // Only enqueue scripts if we're displaying a post that contains the shortcode
            if (has_shortcode($post->post_content, $this->shortcode_name)) {
                wp_enqueue_style('agenda-in-cloud', plugin_dir_url(__FILE__) . 'css/agenda-in-cloud.css', [], '0.1');
            }
        }
    }
    (new AgendaInCloud())->register();
}
