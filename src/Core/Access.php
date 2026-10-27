<?php

declare(strict_types=1);

namespace Vsm\Core;

enum Access
{
    case Public; // app Check-In / Check-Out (tanpa login)
    case Auth;   // petugas security unit 1 / unit 2
    case Admin;  // admin lintas unit
}
