<?php
namespace TEC\Tickets\phpqrcode;

/*
 * PHP QR Code encoder
 *
 * Config file, feel free to modify
 */

    define('TEC_TICKETS_QR_CACHEABLE', false);                                                               // use cache - more disk reads but less CPU power, masks and format templates are stored there
    define('TEC_TICKETS_QR_CACHE_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR);  // used when TEC_TICKETS_QR_CACHEABLE === true
    define('TEC_TICKETS_QR_LOG_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR);                                // default error logs dir

    define('TEC_TICKETS_QR_FIND_BEST_MASK', true);                                                          // if true, estimates best mask (spec. default, but extremally slow; set to false to significant performance boost but (propably) worst quality code
    define('TEC_TICKETS_QR_FIND_FROM_RANDOM', false);                                                       // if false, checks all masks available, otherwise value tells count of masks need to be checked, mask id are got randomly
    define('TEC_TICKETS_QR_DEFAULT_MASK', 2);                                                               // when TEC_TICKETS_QR_FIND_BEST_MASK === false

    define('TEC_TICKETS_QR_PNG_MAXIMUM_SIZE',  1024);                                                       // maximum allowed png image width (in pixels), tune to make sure GD and PHP can handle such big images
