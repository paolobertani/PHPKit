<?php



/*
 *
 *  Pinaxo API Php Library
 *
 *
 *  API Version         v1
 *
 *  Library version     3.0
 *
 *  Copyright 2013-2026 Kalei
 *
 */



namespace Kalei\PinaxoAPI
{

    require_once __DIR__ . '/include/base.php';
    require_once __DIR__ . '/include/brands.php';
    require_once __DIR__ . '/include/documents.php';
    require_once __DIR__ . '/include/etim.php';
    require_once __DIR__ . '/include/status.php';

    class Session
    {
        use base;
        use brands;
        use documents;
        use etim;
        use status;
    }

}
