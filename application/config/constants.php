<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Role Constants
|--------------------------------------------------------------------------
|
| Define role IDs as constants to avoid magic numbers throughout the codebase
|
*/
define('ROLE_ADMIN', 1);
define('ROLE_MANAGER', 2);
define('ROLE_LEAD', 3);
define('ROLE_STAFF', 4);
/** Alias: end-user / self-service tier (same id as ROLE_STAFF; not the same as ROLE_MANAGER). */
define('ROLE_USER', ROLE_STAFF);

/*
|--------------------------------------------------------------------------
| Permission Constants
|--------------------------------------------------------------------------
|
| Define permission action constants
|
*/
define('PERMISSION_VIEW', 'view');
define('PERMISSION_CREATE', 'create');
define('PERMISSION_EDIT', 'edit');
define('PERMISSION_DELETE', 'delete');

/*
|--------------------------------------------------------------------------
| Status Constants
|--------------------------------------------------------------------------
|
| Common status values
|
*/
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_PENDING', 'pending');
define('STATUS_COMPLETED', 'completed');
define('STATUS_CANCELLED', 'cancelled');
