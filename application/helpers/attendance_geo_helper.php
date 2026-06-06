<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Geo helpers for attendance (geofence + reverse geocoding).
 */

if (!function_exists('attendance_geo_calculate_distance')) {
    /**
     * Haversine distance in meters.
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float
     */
    function attendance_geo_calculate_distance($lat1, $lon1, $lat2, $lon2)
    {
        $lat1 = (float) $lat1;
        $lon1 = (float) $lon1;
        $lat2 = (float) $lat2;
        $lon2 = (float) $lon2;

        $earth_radius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth_radius * $c;
    }
}

if (!function_exists('attendance_geo_reverse_geocode')) {
    /**
     * @param mixed $lat
     * @param mixed $lng
     * @return string|null
     */
    function attendance_geo_reverse_geocode($lat, $lng)
    {
        $lat = trim((string) $lat);
        $lng = trim((string) $lng);
        if ($lat === '' || $lng === '') {
            return null;
        }

        $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
            . rawurlencode($lat) . '&lon=' . rawurlencode($lng);
        $opts = array(
            'http' => array(
                'method'  => 'GET',
                'header'  => 'User-Agent: ' . get_company_name() . "/1.0\r\n",
                'timeout' => 5,
            ),
        );
        $resp = @file_get_contents($url, false, stream_context_create($opts));
        if ($resp === false) {
            return null;
        }

        $j = json_decode($resp, true);
        if (!is_array($j)) {
            return null;
        }
        if (!empty($j['display_name'])) {
            return (string) $j['display_name'];
        }
        if (!empty($j['address']) && is_array($j['address'])) {
            $parts = array();
            foreach (array('road', 'suburb', 'city', 'state', 'country') as $k) {
                if (!empty($j['address'][$k])) {
                    $parts[] = $j['address'][$k];
                }
            }
            if (!empty($parts)) {
                return implode(', ', $parts);
            }
        }

        return null;
    }
}
