<?php
/**
 * This is a wrapper that sanitizes user input before sending the input
 * to the actual page being requested.
 *
 * How the wrapper works:
 * 1. There is a .htaccess file in the same directory. The .htaccess file tells
 *    Apache to reroute all requests for .php pages to point to this page.
 * 2. The wrapper checks filter.json for any rules that apply to the page being
 *    requested. The wrapper then sanitizes the appropriate inputs according to
 *    the rules.
 * 3. After sanitizing the inputs, the wrapper includes the actual requested
 *    page and runs it.
 *
 */

// The user should not request this page directly.
if (!isset($_SERVER['REDIRECT_URL'])) {
    exit;
}

require_once('xss_sql_filter.php');
$xss_sql_filter = new xss_sql_filter();

// Where the filter configurations are stored.
$FILTER_JSON_PATH = 'filter.json';

$json = file_get_contents($FILTER_JSON_PATH);
$filters = json_decode($json, true);
$page = $_SERVER['REDIRECT_URL'];

$allowed_types = [
    'cookie', 'env', 'files', 'get', 'post', 'request', 'server', 'session'
];

if (isset($filters[$page])) {
    foreach ($filters[$page] as $type => $v1) {
        if (!in_array($type, $allowed_types)) {
            // Only need to modify the superglobals.
            continue;
        }
        $superglobal_name = "_".strtoupper($type);
        $superglobal = &${$superglobal_name};
        foreach ($v1 as $variable => $rule_id) {
            // Only change the variable if it's already set.
            if (isset($superglobal[$variable])) {
                $superglobal[$variable] = applyRule($superglobal[$variable], $rule_id);
            }
        }
    }
}

// Include the actual requested page if it is accessible.
if (is_file($_SERVER['DOCUMENT_ROOT'].$page) && is_readable($_SERVER['DOCUMENT_ROOT'].$page)) {
    include($_SERVER['DOCUMENT_ROOT'].$page);
}

/**
 * Sanitizes the input according to the rule with the given id.
 */
function applyRule($value, $rule_id) {
    global $xss_sql_filter;
    $rule_map = [
        0 => 'filter_Disallow',
        1 => 'filter_HTMLEntityEncode',
        2 => 'filter_For_Attributes',
        3 => 'filter_For_Javascript',
        4 => 'filter_For_CSS',
        5 => 'filter_For_URL',
        6 => 'filter_SQL_Escaping',
        7 => 'filter_SQL_CompleteEscape',
        8 => 'filter_Char2Num_id'
    ];
    if (array_key_exists($rule_id, $rule_map)) {
        $rule_function = $rule_map[$rule_id];
        $new_value = $xss_sql_filter->$rule_function($value);
    } else {
        $new_value = $value;
    }
    return $new_value;
}
