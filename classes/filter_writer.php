<?php

use core\output\html_writer;
use core\url as moodle_url;

class filter_writer {
    private static function render_pagination_button(string $display, moodle_url $url, bool $isactive) {
        return html_writer::tag('li',
            html_writer::link($url, $display, ['class' => 'page-link ' . ($isactive ? ' active' : '')]),
            ['class' => 'page-item']
        );
    }
    public static function render_name_filters(string $filtername, string $displayname) {
        global $PAGE;

        $current = optional_param($filtername, '', PARAM_ALPHA);

        $params = $PAGE->url->params();

        $html = '';

        $html .= html_writer::start_div('d-flex flex-row');
            $html .= html_writer::tag('span', $displayname, ['class' => 'initialbarlabel me-3']);

            $html .= html_writer::start_tag('ul', ['class' => 'pagination pagination-sm']);
                $params[$filtername] = null;
                $html .= self::render_pagination_button(
                    'All',
                    new moodle_url($PAGE->url, $params),
                    empty($current)
                );
                
                foreach (range('a', 'z') as $letter) {
                    $params[$filtername] = $letter;
                    $html .= self::render_pagination_button(
                        strtoupper($letter),
                        new moodle_url($PAGE->url, $params),
                        $current === $letter
                    );
                }
            $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_div();
        return $html;
    }
}