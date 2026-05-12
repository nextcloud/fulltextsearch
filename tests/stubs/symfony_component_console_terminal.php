<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console;

class Terminal
{

    /**
     * Gets the terminal width.
     */
    public function getWidth(): int { }

    /**
     * Gets the terminal height.
     */
    public function getHeight(): int
    {
    }
}
