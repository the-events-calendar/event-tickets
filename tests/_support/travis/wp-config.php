<?php
/**
 * A Travis CI-dedicated WordPress configuration file.
 */

define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'db' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
define( 'AUTH_KEY', 'NCNF0pITDn4VOnm6KqaEsLdwXW3+W5tOcukneXEcY8ulmcQ37TTTWMTJ4OPIap8dBNF9rlPsKZMEkEEVFvdQpw==' );
define( 'SECURE_AUTH_KEY', 'a9eWKBy7NVAvy79s2ZhC5/OwrDOC5HQ26kmA/8UU6mvf5wlIT+fLcDV14Lwq+62OMSW17StwrJRZ28v3QG5Yyw==' );
define( 'LOGGED_IN_KEY', 'xLn+m/miKa4JVhBiFbJMeF+JElMlHPn9NupGd0V8OdhOW+3582oJjtzeG5D7ohghiV/9YaDELk2D99iAWDmPnA==' );
define( 'NONCE_KEY', 'Uqvuwz/X+fcceLwI+7IyT0R1SVFtK2mkzqCB1V0bHdgvKjpsyn/NCuIRSlM5punQaeFDqysvhwHFkkb90sST/A==' );
define( 'AUTH_SALT', 'BKpp6s7lFgwdBgJ2gd2Ah+3uWef2kLmrGEYCfu+xrCRBkk0YhOFt4Ugx0kd3DDKlIxlB5eqwhjCYj9C4jroDoQ==' );
define( 'SECURE_AUTH_SALT', 'F6t/m1a8vzaXPfipJAblNugcEs0+dGvKVlPd+S919NQKSfHRpa9BBpzg2F+vH/CTceXWNmIDzaJU+JjOerie0g==' );
define( 'LOGGED_IN_SALT', 'VxEdyiiyvNOc0dgJKmqNYDIStAxBDtfG+u994PWMCGo2kM6Bd/pIv+BaUTJ9crLVdnVkdeegF3IfU3pa09QaBg==' );
define( 'NONCE_SALT', 'WWKTx46HtsPcdDjUFMkZ1Fo/R/vDKnHVov3Odfsg7itWSZgf2+KwOPdOB39EhN0p3n9EacfpZ3n018THVHRukg==' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );

$table_prefix = 'wp_';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

require_once ABSPATH . 'wp-settings.php';
