<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Action-Priority Matrix (Effort × Impact) — shared quadrant definitions.
 * Layout: Quick Wins | Major Projects / Fill-ins | Hard Slogs
 */

if (!function_exists('apm_quadrants')) {
    function apm_quadrants()
    {
        return array(
            'quick_wins' => array(
                'label'    => 'Quick Wins',
                'subtitle' => 'Low effort · High impact',
                'tone'     => 'green',
            ),
            'major_projects' => array(
                'label'    => 'Major Projects',
                'subtitle' => 'High effort · High impact',
                'tone'     => 'yellow',
            ),
            'fill_ins' => array(
                'label'    => 'Fill-ins',
                'subtitle' => 'Low effort · Low impact',
                'tone'     => 'yellow-soft',
            ),
            'hard_slogs' => array(
                'label'    => 'Hard Slogs',
                'subtitle' => 'High effort · Low impact',
                'tone'     => 'red',
            ),
        );
    }
}

if (!function_exists('apm_grid_order')) {
    /** Top row high impact, bottom row low impact; left low effort, right high effort. */
    function apm_grid_order()
    {
        return array('quick_wins', 'major_projects', 'fill_ins', 'hard_slogs');
    }
}

if (!function_exists('apm_quadrant_from_axes')) {
    function apm_quadrant_from_axes($high_impact, $high_effort)
    {
        if (!$high_effort && $high_impact) {
            return 'quick_wins';
        }
        if ($high_effort && $high_impact) {
            return 'major_projects';
        }
        if (!$high_effort && !$high_impact) {
            return 'fill_ins';
        }
        return 'hard_slogs';
    }
}
