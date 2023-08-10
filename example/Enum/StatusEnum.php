<?php

namespace HyperfExample\ApiDocs\Enum;
// PHP>=8.1
//if (PHP_VERSION_ID >= 80100) {
    enum StatusEnum: string
    {
        case SUCCESS = 'success';

        case CLOSED = 'closed';
    }
//} else {
//    class StatusEnum
//    {
//
//    }
//}


